<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiSecurityAndThrottlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer azo_live_invalid.azo_sec_invalid')
            ->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_malformed_authorization_header_returns_401(): void
    {
        // Basic auth instead of Bearer
        $this->withHeader('Authorization', 'Basic dXNlcjpwYXNz')
            ->getJson('/api/meetings/active')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        // Bearer prefix with empty token
        $this->withHeader('Authorization', 'Bearer ')
            ->getJson('/api/meetings/active')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_revoked_api_key_returns_401(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Revoked App');
        $client = $result['client'];
        $key = $result['plain_text_key'];

        $service->revokeClient($client->key_id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_expired_api_key_returns_401(): void
    {
        $keyId = 'azo_live_expired123';
        $secret = 'azo_sec_' . \Illuminate\Support\Str::random(40);
        $plainTextKey = $keyId . '.' . $secret;

        ApiClient::create([
            'name' => 'Expired Client',
            'key_id' => $keyId,
            'secret_hash' => hash('sha256', $plainTextKey),
            'scopes' => [ApiClient::SCOPE_MEETINGS_READ],
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_soft_deleted_api_key_returns_401(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Soft Deleted App');
        $client = $result['client'];
        $key = $result['plain_text_key'];

        $client->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_valid_api_key_without_meetings_read_scope_returns_403(): void
    {
        $keyId = 'azo_live_other123';
        $secret = 'azo_sec_' . \Illuminate\Support\Str::random(40);
        $plainTextKey = $keyId . '.' . $secret;

        ApiClient::create([
            'name' => 'Other Scope Client',
            'key_id' => $keyId,
            'secret_hash' => hash('sha256', $plainTextKey),
            'scopes' => ['unrelated.scope'],
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden.',
            ]);
    }

    public function test_last_used_at_updated_only_on_successful_authentication(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Tracking App');
        $client = $result['client'];
        $key = $result['plain_text_key'];

        $this->assertNull($client->fresh()->last_used_at);

        // Failed auth attempt using wrong token
        $this->withHeader('Authorization', 'Bearer azo_live_wrong.azo_sec_wrong')
            ->getJson('/api/meetings/active');

        $this->assertNull($client->fresh()->last_used_at);

        // Successful auth request
        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $this->assertNotNull($client->fresh()->last_used_at);
    }

    public function test_rate_limiting_enforced_per_api_client(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Throttled Client');
        $client = $result['client'];
        $key = $result['plain_text_key'];

        RateLimiter::clear('api-client:' . $client->id);

        // Make 60 successful requests
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $key)
                ->getJson('/api/meetings/active');
            $response->assertStatus(200);
        }

        // The 61st request must receive 429 Too Many Requests
        $response = $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active');

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too Many Requests.',
            ])
            ->assertHeader('Retry-After');
    }

    public function test_independent_rate_limit_buckets_for_different_api_clients(): void
    {
        $service = app(ApiClientService::class);

        $resultA = $service->createClient('Client A');
        $clientA = $resultA['client'];
        $keyA = $resultA['plain_text_key'];

        $resultB = $service->createClient('Client B');
        $clientB = $resultB['client'];
        $keyB = $resultB['plain_text_key'];

        RateLimiter::clear('api-client:' . $clientA->id);
        RateLimiter::clear('api-client:' . $clientB->id);

        // Consume all 60 requests for Client A
        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', 'Bearer ' . $keyA)->getJson('/api/meetings/active');
        }

        // Client A 61st request fails with 429
        $this->withHeader('Authorization', 'Bearer ' . $keyA)
            ->getJson('/api/meetings/active')
            ->assertStatus(429);

        // Client B is unaffected and receives 200 OK
        $this->withHeader('Authorization', 'Bearer ' . $keyB)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);
    }
}
