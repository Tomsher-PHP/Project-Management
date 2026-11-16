<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Industry;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Services\ApiClientService;
use App\Services\DataSync\Settings\ModuleResolver;
use App\Services\DataSync\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Tests\TestCase;

class DataSyncSettingsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');
    }

    public function test_module_resolver_recognizes_all_four_supported_modules(): void
    {
        $resolver = app(ModuleResolver::class);

        $this->assertTrue($resolver->isValid('technologies'));
        $this->assertTrue($resolver->isValid('industries'));
        $this->assertTrue($resolver->isValid('project_categories'));
        $this->assertTrue($resolver->isValid('project_statuses'));

        $this->assertSame(Technology::class, $resolver->resolve('technologies'));
        $this->assertSame(Industry::class, $resolver->resolve('industries'));
        $this->assertSame(ProjectCategory::class, $resolver->resolve('project_categories'));
        $this->assertSame(ProjectStatus::class, $resolver->resolve('project_statuses'));

        $this->assertFalse($resolver->isValid('users'));
        $this->assertFalse($resolver->isValid('App\\Models\\User'));
    }

    public function test_settings_service_resolves_via_dependency_injection(): void
    {
        $service = app(SettingsService::class);

        $this->assertInstanceOf(SettingsService::class, $service);

        $this->expectException(InvalidArgumentException::class);
        $service->list('invalid_module');
    }

    public function test_requests_without_api_key_return_401(): void
    {
        $this->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_requests_with_invalid_api_key_return_401(): void
    {
        $this->withHeader('Authorization', 'Bearer azo_live_invalid.azo_sec_invalid')
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_client_with_only_meetings_read_cannot_access_data_sync(): void
    {
        $result = app(ApiClientService::class)->createClient('Meeting Only Client', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Rust'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_client_with_settings_read_can_reach_read_endpoints_but_not_write_endpoints(): void
    {
        $result = app(ApiClientService::class)->createClient('Settings Read Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $key = $result['plain_text_key'];

        foreach (['technologies', 'industries', 'project_categories', 'project_statuses'] as $module) {
            $this->withHeader('Authorization', 'Bearer ' . $key)
                ->getJson("/api/data-sync/settings/{$module}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Records retrieved successfully.',
                ]);

            $this->withHeader('Authorization', 'Bearer ' . $key)
                ->getJson("/api/data-sync/settings/{$module}/123456")
                ->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Record not found.',
                ]);
        }

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->putJson('/api/data-sync/settings/technologies/123456')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->deleteJson('/api/data-sync/settings/technologies/123456')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_client_with_settings_write_can_reach_write_endpoints_but_not_read_endpoints(): void
    {
        $result = app(ApiClientService::class)->createClient('Settings Write Client', [
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies/123456')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Go'])
            ->assertStatus(201);

        $createdCode = $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Rust'])
            ->assertStatus(201)
            ->json('data.code');

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->putJson("/api/data-sync/settings/technologies/{$createdCode}", ['name' => 'Rust Lang'])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->deleteJson("/api/data-sync/settings/technologies/{$createdCode}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);
    }

    public function test_unsupported_module_slug_returns_404_when_authorized(): void
    {
        $result = app(ApiClientService::class)->createClient('Full Settings Client', [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/unsupported_module')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Data Sync module.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/unsupported_module')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Data Sync module.',
            ]);
    }

    public function test_same_api_key_can_be_granted_data_sync_scopes_without_rotating_secret(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Shared Integration App', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);

        $client = $result['client'];
        $originalSecretHash = $client->secret_hash;
        $key = $result['plain_text_key'];

        $updatedClient = $service->grantScopes($client->key_id, [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $this->assertSame($originalSecretHash, $updatedClient->secret_hash);
        $this->assertSame([
            ApiClient::SCOPE_MEETINGS_READ,
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ], $updatedClient->scopes);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'TypeScript'])
            ->assertStatus(201);
    }

    public function test_data_sync_and_meetings_share_api_client_rate_limiter(): void
    {
        $result = app(ApiClientService::class)->createClient('Rate Limited Shared App', [
            ApiClient::SCOPE_MEETINGS_READ,
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $client = $result['client'];
        $key = $result['plain_text_key'];

        $lastResponse = null;
        for ($i = 0; $i < 59; $i++) {
            $lastResponse = $this->withHeader('Authorization', 'Bearer ' . $key)
                ->getJson('/api/data-sync/settings/technologies')
                ->assertStatus(200);
        }

        $this->assertSame('1', (string) $lastResponse->headers->get('X-RateLimit-Remaining'));

        $meetingResponse = $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $this->assertSame('0', (string) $meetingResponse->headers->get('X-RateLimit-Remaining'));

        // 61st request must return 429 Too Many Requests
        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too Many Requests.',
            ])
            ->assertHeader('Retry-After');
    }
}

