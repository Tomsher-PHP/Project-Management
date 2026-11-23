<?php

namespace Tests\Feature;

use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncOutboundConnectionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $authorizedUser;
    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        foreach (['data_sync_settings.view', 'data_sync_settings.create', 'data_sync_settings.edit', 'data_sync_settings.delete'] as $perm) {
            $permission = Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
            $this->authorizedUser->givePermissionTo($permission);
        }

        $this->unauthorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);
    }

    public function test_authorized_user_can_view_connections_listing(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Test CRM System',
            'base_url' => 'https://api.crm.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'secret-test-token'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/connections');
        $response->assertStatus(200);
        $response->assertSee('Test CRM System');
        $response->assertSee('https://api.crm.example.com');
        $response->assertSee('Bearer Token');
        $response->assertDontSee('secret-test-token');
    }

    public function test_can_create_connection_with_bearer_auth(): void
    {
        $response = $this->actingAs($this->authorizedUser)->postJson('/settings/data-sync/outbound/connections', [
            'name' => 'External ERP',
            'base_url' => 'https://erp.example.com/api',
            'auth_type' => 'bearer',
            'token' => 'my-bearer-secret-token',
            'is_active' => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'connection' => [
                'name' => 'External ERP',
                'base_url' => 'https://erp.example.com/api',
                'auth_type' => 'bearer',
                'is_active' => true,
            ],
        ]);

        $conn = DataSyncOutboundConnection::where('name', 'External ERP')->firstOrFail();
        $this->assertSame('my-bearer-secret-token', $conn->auth_config['token']);

        // Check raw DB row is encrypted and does not contain plaintext token
        $raw = \DB::table('data_sync_outbound_connections')->where('id', $conn->id)->value('auth_config');
        $this->assertStringNotContainsString('my-bearer-secret-token', $raw);
    }

    public function test_can_create_connection_with_api_key_auth(): void
    {
        $response = $this->actingAs($this->authorizedUser)->postJson('/settings/data-sync/outbound/connections', [
            'name' => 'Partner API',
            'base_url' => 'https://partner.example.com',
            'auth_type' => 'api_key',
            'header_name' => 'X-Custom-API-Key',
            'api_key' => 'partner_secret_123',
            'is_active' => 1,
        ]);

        $response->assertStatus(201);
        $conn = DataSyncOutboundConnection::where('name', 'Partner API')->firstOrFail();
        $this->assertSame('partner_secret_123', $conn->auth_config['api_key']);
        $this->assertSame('X-Custom-API-Key', $conn->auth_config['header_name']);
    }

    public function test_can_create_connection_with_basic_auth(): void
    {
        $response = $this->actingAs($this->authorizedUser)->postJson('/settings/data-sync/outbound/connections', [
            'name' => 'Legacy Service',
            'base_url' => 'https://legacy.example.com',
            'auth_type' => 'basic',
            'username' => 'api_user',
            'password' => 'super_secret_pw',
            'is_active' => 1,
        ]);

        $response->assertStatus(201);
        $conn = DataSyncOutboundConnection::where('name', 'Legacy Service')->firstOrFail();
        $this->assertSame('api_user', $conn->auth_config['username']);
        $this->assertSame('super_secret_pw', $conn->auth_config['password']);
    }

    public function test_can_create_connection_with_none_auth(): void
    {
        $response = $this->actingAs($this->authorizedUser)->postJson('/settings/data-sync/outbound/connections', [
            'name' => 'Public Webhook',
            'base_url' => 'https://webhook.example.com',
            'auth_type' => 'none',
            'is_active' => 1,
        ]);

        $response->assertStatus(201);
        $conn = DataSyncOutboundConnection::where('name', 'Public Webhook')->firstOrFail();
        $this->assertNull($conn->auth_config);
    }

    public function test_ssrf_prevention_rejects_internal_and_loopback_hosts(): void
    {
        $blockedUrls = [
            'http://localhost/api',
            'http://127.0.0.1:8000',
            'http://0.0.0.0',
            'http://169.254.169.254/latest/meta-data',
            'http://10.0.0.5/sync',
            'http://192.168.1.1',
            'http://172.16.0.1',
            'ftp://api.example.com',
            'not-a-valid-url',
        ];

        foreach ($blockedUrls as $badUrl) {
            $response = $this->actingAs($this->authorizedUser)->postJson('/settings/data-sync/outbound/connections', [
                'name' => 'Invalid Connection',
                'base_url' => $badUrl,
                'auth_type' => 'none',
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors('base_url');
        }
    }

    public function test_update_connection_preserves_credentials_when_blank(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Original Name',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'original-bearer-token'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authorizedUser)->putJson("/settings/data-sync/outbound/connections/{$conn->id}", [
            'name' => 'Updated Connection Name',
            'base_url' => 'https://new-api.example.com',
            'auth_type' => 'bearer',
            'token' => '', // Left empty to preserve
        ]);

        $response->assertStatus(200);
        $conn->refresh();
        $this->assertSame('Updated Connection Name', $conn->name);
        $this->assertSame('https://new-api.example.com', $conn->base_url);
        $this->assertSame('original-bearer-token', $conn->auth_config['token']);
    }

    public function test_toggle_connection_status(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Toggle Conn',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authorizedUser)->patchJson("/settings/data-sync/outbound/connections/{$conn->id}/toggle-status");
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);

        $conn->refresh();
        $this->assertFalse($conn->is_active);
    }

    public function test_soft_delete_connection(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Delete Me',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authorizedUser)->deleteJson("/settings/data-sync/outbound/connections/{$conn->id}");
        $response->assertStatus(200);

        $this->assertSoftDeleted('data_sync_outbound_connections', ['id' => $conn->id]);
    }

    public function test_can_add_multiple_apis_to_connection(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Multi-API System',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'test-token'],
            'is_active' => true,
        ]);

        // Add API 1: Projects Create
        $response1 = $this->actingAs($this->authorizedUser)->postJson("/settings/data-sync/outbound/connections/{$conn->id}/apis", [
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/projects',
            'is_active' => 1,
        ]);
        $response1->assertStatus(201);

        // Add API 2: Projects Update
        $response2 = $this->actingAs($this->authorizedUser)->postJson("/settings/data-sync/outbound/connections/{$conn->id}/apis", [
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/projects',
            'is_active' => 1,
        ]);
        $response2->assertStatus(201);

        // Add API 3: Customers Create
        $response3 = $this->actingAs($this->authorizedUser)->postJson("/settings/data-sync/outbound/connections/{$conn->id}/apis", [
            'module' => 'customers',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/customers',
            'is_active' => 1,
        ]);
        $response3->assertStatus(201);

        $this->assertSame(3, $conn->apis()->count());
    }

    public function test_duplicate_module_and_operation_for_same_connection_is_rejected(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Duplicate Test',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $conn->apis()->create([
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/projects',
            'is_active' => true,
        ]);

        // Attempt to create duplicate (projects + create) under the same connection
        $response = $this->actingAs($this->authorizedUser)->postJson("/settings/data-sync/outbound/connections/{$conn->id}/apis", [
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/projects/duplicate',
            'is_active' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('operation');
    }

    public function test_can_update_and_delete_api(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'API CRUD Test',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $api = $conn->apis()->create([
            'module' => 'tasks',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/tasks',
            'is_active' => true,
        ]);

        // Update API
        $updateResp = $this->actingAs($this->authorizedUser)->putJson("/settings/data-sync/outbound/connections/{$conn->id}/apis/{$api->id}", [
            'module' => 'tasks',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/v2/tasks/create',
            'is_active' => 0,
        ]);
        $updateResp->assertStatus(200);

        $api->refresh();
        $this->assertSame('/api/v2/tasks/create', $api->endpoint);
        $this->assertFalse($api->is_active);

        // Delete API
        $deleteResp = $this->actingAs($this->authorizedUser)->deleteJson("/settings/data-sync/outbound/connections/{$conn->id}/apis/{$api->id}");
        $deleteResp->assertStatus(200);

        $this->assertDatabaseMissing('data_sync_outbound_apis', ['id' => $api->id]);
    }

    public function test_unauthorized_user_cannot_create_or_edit(): void
    {
        $conn = DataSyncOutboundConnection::create([
            'name' => 'Protected Conn',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $this->actingAs($this->unauthorizedUser)
            ->postJson('/settings/data-sync/outbound/connections', [
                'name' => 'Hacker Conn',
                'base_url' => 'https://evil.example.com',
                'auth_type' => 'none',
            ])
            ->assertStatus(403);

        $this->actingAs($this->unauthorizedUser)
            ->deleteJson("/settings/data-sync/outbound/connections/{$conn->id}")
            ->assertStatus(403);
    }
}

