<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a temporary dummy route protected by the api.key middleware for testing
        Route::get('/api/test-protected', function (\Illuminate\Http\Request $request) {
            /** @var ApiClient $client */
            $client = $request->attributes->get('api_client');

            return response()->json([
                'success' => true,
                'client_id' => $client->id,
                'client_name' => $client->name,
                'key_id' => $client->key_id,
            ]);
        })->middleware('api.key');
    }

    public function test_can_create_api_client_via_service(): void
    {
        $service = app(ApiClientService::class);

        $result = $service->createClient(
            name: 'External System Test',
            scopes: [ApiClient::SCOPE_MEETINGS_READ],
            expiresAt: '2030-01-01 00:00:00'
        );

        $client = $result['client'];
        $plainTextKey = $result['plain_text_key'];

        $this->assertNotEmpty($plainTextKey);
        $this->assertStringStartsWith('azo_live_', $plainTextKey);

        $this->assertDatabaseHas('api_clients', [
            'id' => $client->id,
            'name' => 'External System Test',
            'key_id' => $client->key_id,
            'is_active' => true,
        ]);

        $this->assertNotEquals($plainTextKey, $client->secret_hash);
        $this->assertEquals(hash('sha256', $plainTextKey), $client->secret_hash);
        $this->assertTrue($client->hasScope(ApiClient::SCOPE_MEETINGS_READ));
        $this->assertNotNull($client->expires_at);
    }

    public function test_create_api_client_command(): void
    {
        $this->artisan('api-client:create', [
            '--name' => 'CLI External App',
            '--scope' => ['meetings.read'],
            '--expires-at' => 'never',
        ])
        ->expectsOutputToContain('API client created successfully.')
        ->expectsOutputToContain('Name:      CLI External App')
        ->expectsOutputToContain('azo_live_')
        ->expectsOutputToContain('STORE THIS API KEY SECURELY')
        ->assertExitCode(0);

        $this->assertDatabaseHas('api_clients', [
            'name' => 'CLI External App',
            'is_active' => true,
        ]);
    }

    public function test_generated_key_authenticates_successfully_with_middleware(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Integration App');
        $plainTextKey = $result['plain_text_key'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/test-protected');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'client_name' => 'Integration App',
                'key_id' => $result['client']->key_id,
            ]);
    }

    public function test_can_revoke_api_client_and_denies_authentication(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('App To Revoke');
        $client = $result['client'];
        $plainTextKey = $result['plain_text_key'];

        // Verify it works before revocation
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/test-protected')
            ->assertStatus(200);

        // Revoke via Artisan command
        $this->artisan('api-client:revoke', ['key_id' => $client->key_id])
            ->expectsOutputToContain('API client revoked successfully.')
            ->assertExitCode(0);

        // Record exists but is inactive
        $this->assertDatabaseHas('api_clients', [
            'id' => $client->id,
            'is_active' => false,
        ]);

        // Attempting auth with revoked key returns 401
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/test-protected')
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_cannot_revoke_already_revoked_or_non_existent_client(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Double Revoke App');
        $client = $result['client'];

        $service->revokeClient($client->key_id);

        $this->artisan('api-client:revoke', ['key_id' => $client->key_id])
            ->expectsOutputToContain('is already revoked')
            ->assertExitCode(1);

        $this->artisan('api-client:revoke', ['key_id' => 'azo_live_nonexistent'])
            ->expectsOutputToContain('was not found')
            ->assertExitCode(1);
    }

    public function test_can_rotate_api_client_key(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('App To Rotate');
        $client = $result['client'];
        $oldKey = $result['plain_text_key'];
        $oldHash = $client->secret_hash;

        // Rotate via CLI
        $this->artisan('api-client:rotate', ['key_id' => $client->key_id])
            ->expectsOutputToContain('API client key rotated successfully.')
            ->expectsOutputToContain('STORE THIS NEW API KEY SECURELY')
            ->assertExitCode(0);

        $freshClient = ApiClient::find($client->id);
        $this->assertNotEquals($oldHash, $freshClient->secret_hash);

        // Old key returns 401
        $this->withHeader('Authorization', 'Bearer ' . $oldKey)
            ->getJson('/api/test-protected')
            ->assertStatus(401);
    }

    public function test_rejects_invalid_scope_and_past_expiration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported API scope');

        $service = app(ApiClientService::class);
        $service->createClient('Bad Scope', ['invalid.scope']);
    }

    public function test_rejects_past_expiration_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be in the past');

        $service = app(ApiClientService::class);
        $service->createClient('Past Date', [ApiClient::SCOPE_MEETINGS_READ], '2020-01-01');
    }

    public function test_secret_is_not_stored_or_serialized(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Privacy Test App');
        $client = $result['client'];
        $plainTextKey = $result['plain_text_key'];

        $array = $client->toArray();

        $this->assertArrayNotHasKey('secret_hash', $array);
        $this->assertArrayNotHasKey('plain_text_key', $array);

        // Ensure raw key is not anywhere in database columns
        $dbRecord = \DB::table('api_clients')->where('id', $client->id)->first();
        $this->assertNotEquals($plainTextKey, $dbRecord->secret_hash);
        $this->assertStringNotContainsString('azo_sec_', $dbRecord->secret_hash);
    }
}
