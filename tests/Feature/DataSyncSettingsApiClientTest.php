<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\DataSyncEntityMapping;
use App\Models\User;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncSettingsApiClientTest extends TestCase
{
    use DatabaseTransactions;

    protected User $viewUser;
    protected User $createUser;
    protected User $editUser;
    protected User $deleteUser;
    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. View User
        $this->viewUser = User::factory()->create(['is_super_admin' => false]);
        $viewPermission = Permission::firstOrCreate(['name' => 'data_sync_settings.view', 'guard_name' => 'web']);
        $this->viewUser->givePermissionTo($viewPermission);

        // 2. Create User
        $this->createUser = User::factory()->create(['is_super_admin' => false]);
        $createPermission = Permission::firstOrCreate(['name' => 'data_sync_settings.create', 'guard_name' => 'web']);
        $this->createUser->givePermissionTo([$viewPermission, $createPermission]);

        // 3. Edit User
        $this->editUser = User::factory()->create(['is_super_admin' => false]);
        $editPermission = Permission::firstOrCreate(['name' => 'data_sync_settings.edit', 'guard_name' => 'web']);
        $this->editUser->givePermissionTo([$viewPermission, $editPermission]);

        // 4. Delete User
        $this->deleteUser = User::factory()->create(['is_super_admin' => false]);
        $deletePermission = Permission::firstOrCreate(['name' => 'data_sync_settings.delete', 'guard_name' => 'web']);
        $this->deleteUser->givePermissionTo([$viewPermission, $deletePermission]);

        // 5. Unauthorized User
        $this->unauthorizedUser = User::factory()->create(['is_super_admin' => false]);
    }

    public function test_1_authorized_user_can_view_api_clients(): void
    {
        $client = ApiClient::create([
            'name' => 'Test External App',
            'key_id' => 'azo_live_testclient1',
            'secret_hash' => hash('sha256', 'azo_live_testclient1.azo_sec_1234567890'),
            'scopes' => ['settings.read'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->viewUser)->get('/settings/data-sync/clients');
        $response->assertStatus(200);
        $response->assertSee('Test External App');
        $response->assertSee('azo_live_testclient1');
        $response->assertSee('settings.read');

        // Verify edit user can render status toggle component without UrlGenerationException
        $editResponse = $this->actingAs($this->editUser)->get('/settings/data-sync/clients');
        $editResponse->assertStatus(200);
        $editResponse->assertSee('data-id="' . $client->id . '"', false);
    }

    public function test_2_unauthorized_user_cannot_access_the_listing(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/clients');
        $response->assertStatus(403);
    }

    public function test_3_search_works(): void
    {
        $matching = ApiClient::create([
            'name' => 'Searchable Salesforce App',
            'key_id' => 'azo_live_sfmatching1',
            'secret_hash' => hash('sha256', 'azo_live_sfmatching1.azo_sec_abc'),
            'scopes' => ['settings.read'],
            'is_active' => true,
        ]);

        $other = ApiClient::create([
            'name' => 'Unrelated Zendesk App',
            'key_id' => 'azo_live_zdother2',
            'secret_hash' => hash('sha256', 'azo_live_zdother2.azo_sec_xyz'),
            'scopes' => ['settings.read'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->viewUser)->get('/settings/data-sync/clients?search=Salesforce');
        $response->assertStatus(200);
        $response->assertSee('Searchable Salesforce App');
        $response->assertDontSee('Unrelated Zendesk App');
    }

    public function test_4_pagination_works(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            ApiClient::create([
                'name' => "Batch Client {$i}",
                'key_id' => "azo_live_batch{$i}_" . uniqid(),
                'secret_hash' => hash('sha256', "token{$i}"),
                'scopes' => ['settings.read'],
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->viewUser)->get('/settings/data-sync/clients?per_page=5&page=1');
        $response->assertStatus(200);
        $response->assertSee('Showing 1 to 5 of');
    }

    public function test_5_create_permission_controls_key_generation(): void
    {
        // View user lacks create permission
        $forbiddenResponse = $this->actingAs($this->viewUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Unauthorized Client',
            'scopes' => ['settings.read'],
            'expiration_type' => 'never',
        ]);
        $forbiddenResponse->assertStatus(403);

        // Create user has permission
        $allowedResponse = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Allowed Client',
            'scopes' => ['settings.read'],
            'expiration_type' => 'never',
        ]);
        $allowedResponse->assertStatus(201);
    }

    public function test_6_and_7_api_key_is_created_with_standard_format(): void
    {
        $response = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Format Test CRM',
            'scopes' => ['settings.read', 'projects.write'],
            'expiration_type' => 'never',
        ]);

        $response->assertStatus(201);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['plain_text_key']);

        // Format: azo_live_<10chars>.azo_sec_<40chars>
        $plainKey = $data['plain_text_key'];
        $this->assertMatchesRegularExpression('/^azo_live_[a-z0-9]{10}\.azo_sec_[A-Za-z0-9]{40}$/', $plainKey);
    }

    public function test_8_and_9_database_stores_only_secret_hash_and_plain_key_is_not_persisted(): void
    {
        $response = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Security Test App',
            'scopes' => ['settings.read'],
            'expiration_type' => 'never',
        ]);

        $plainTextKey = $response->json('plain_text_key');
        $clientId = $response->json('client.id');

        $clientInDb = ApiClient::find($clientId);
        $this->assertNotNull($clientInDb);

        // secret_hash must match sha256 of plain_text_key
        $this->assertEquals(hash('sha256', $plainTextKey), $clientInDb->secret_hash);

        // Plaintext key must NOT exist anywhere in table row
        $this->assertStringNotContainsString('azo_sec_', $clientInDb->toJson());
        $this->assertNull($clientInDb->getOriginal('plain_text_key'));
    }

    public function test_10_multiple_scopes_are_saved_correctly(): void
    {
        $response = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Multi Scope App',
            'scopes' => ['settings.read', 'settings.write', 'projects.write'],
            'expiration_type' => 'never',
        ]);

        $response->assertStatus(201);
        $client = ApiClient::find($response->json('client.id'));
        $this->assertEquals(['settings.read', 'settings.write', 'projects.write'], $client->scopes);
    }

    public function test_11_and_12_invalid_or_missing_scopes_are_rejected(): void
    {
        // Empty scopes
        $resEmpty = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'No Scope App',
            'scopes' => [],
            'expiration_type' => 'never',
        ]);
        $resEmpty->assertStatus(422);
        $resEmpty->assertJsonValidationErrors('scopes');

        // Arbitrary / unsupported scope
        $resInvalid = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Hacker Scope App',
            'scopes' => ['arbitrary.admin.scope'],
            'expiration_type' => 'never',
        ]);
        $resInvalid->assertStatus(422);
        $resInvalid->assertJsonValidationErrors('scopes.0');
    }

    public function test_13_and_14_expiration_validation_and_never_stores_null(): void
    {
        // Past expiration date rejected
        $resPast = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Past App',
            'scopes' => ['settings.read'],
            'expiration_type' => 'custom',
            'expires_at' => now()->subDay()->format('Y-m-d'),
        ]);
        $resPast->assertStatus(422);
        $resPast->assertJsonValidationErrors('expires_at');

        // Never expires stores null
        $resNever = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Never App',
            'scopes' => ['settings.read'],
            'expiration_type' => 'never',
        ]);
        $resNever->assertStatus(201);
        $client = ApiClient::find($resNever->json('client.id'));
        $this->assertNull($client->expires_at);

        // Valid future expiration date
        $futureDate = now()->addDays(30)->format('Y-m-d');
        $resFuture = $this->actingAs($this->createUser)->postJson('/settings/data-sync/clients', [
            'name' => 'Future App',
            'scopes' => ['settings.read'],
            'expiration_type' => 'custom',
            'expires_at' => $futureDate,
        ]);
        $resFuture->assertStatus(201);
        $futureClient = ApiClient::find($resFuture->json('client.id'));
        $this->assertEquals($futureDate, $futureClient->expires_at->format('Y-m-d'));
    }

    public function test_15_16_and_17_edit_permission_updates_name_and_scopes_without_key_regeneration(): void
    {
        $client = ApiClient::create([
            'name' => 'Original Name',
            'key_id' => 'azo_live_orig123456',
            'secret_hash' => hash('sha256', 'azo_live_orig123456.azo_sec_initial'),
            'scopes' => ['settings.read'],
            'is_active' => true,
        ]);

        $initialHash = $client->secret_hash;

        // View user cannot update
        $forbidden = $this->actingAs($this->viewUser)->putJson("/settings/data-sync/clients/{$client->id}", [
            'name' => 'Hacked Name',
            'scopes' => ['settings.read'],
            'expiration_type' => 'never',
        ]);
        $forbidden->assertStatus(403);

        // Edit user can update
        $allowed = $this->actingAs($this->editUser)->putJson("/settings/data-sync/clients/{$client->id}", [
            'name' => 'Updated Application Name',
            'scopes' => ['settings.read', 'settings.write'],
            'expiration_type' => 'never',
        ]);
        $allowed->assertStatus(200);

        $client->refresh();
        $this->assertEquals('Updated Application Name', $client->name);
        $this->assertEquals(['settings.read', 'settings.write'], $client->scopes);
        // Secret hash must NOT change when only metadata/scopes are edited
        $this->assertEquals($initialHash, $client->secret_hash);
    }

    public function test_18_scope_changes_affect_api_authorization(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Scope Test', ['settings.read']);
        $plainTextKey = $res['plain_text_key'];
        $client = $res['client'];

        // Can read settings
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);

        // Cannot write settings (403 Forbidden)
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '888001',
                'name' => 'PHP Scope Test',
            ])
            ->assertStatus(403);

        // Grant settings.write scope via settings edit
        $this->actingAs($this->editUser)->putJson("/settings/data-sync/clients/{$client->id}", [
            'name' => 'Scope Test',
            'scopes' => ['settings.read', 'settings.write'],
            'expiration_type' => 'never',
        ]);

        // Now can write settings (or passes auth check)
        $writeRes = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '888002',
                'name' => 'PHP Scope Test ' . uniqid(),
            ]);
        // 201 Created or 422, but not 403 Insufficient permissions
        $this->assertNotEquals(403, $writeRes->status());
    }

    public function test_19_20_and_21_deactivation_rejects_with_401_and_reactivation_restores(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Toggle Test App', ['settings.read']);
        $plainTextKey = $res['plain_text_key'];
        $client = $res['client'];

        // Authenticates when active
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);

        // Deactivate via status toggle
        $toggleRes = $this->actingAs($this->editUser)->patchJson("/settings/data-sync/clients/{$client->id}/toggle-status");
        $toggleRes->assertStatus(200);
        $this->assertFalse($client->fresh()->is_active);

        // Deactivated key receives 401 Unauthorized
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(401);

        // Reactivate via toggle
        $this->actingAs($this->editUser)->patchJson("/settings/data-sync/clients/{$client->id}/toggle-status");
        $this->assertTrue($client->fresh()->is_active);

        // Authenticates again
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);
    }

    public function test_22_and_23_key_rotation_invalidates_old_key_and_authenticates_new_key(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Rotation App', ['settings.read']);
        $oldKey = $res['plain_text_key'];
        $client = $res['client'];

        // Old key works
        $this->withHeader('Authorization', 'Bearer ' . $oldKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);

        // Rotate key
        $rotateRes = $this->actingAs($this->editUser)->postJson("/settings/data-sync/clients/{$client->id}/rotate");
        $rotateRes->assertStatus(200);
        $newKey = $rotateRes->json('plain_text_key');

        $this->assertNotEmpty($newKey);
        $this->assertNotEquals($oldKey, $newKey);

        // Old key rejected with 401
        $this->withHeader('Authorization', 'Bearer ' . $oldKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(401);

        // New key authenticates with 200
        $this->withHeader('Authorization', 'Bearer ' . $newKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);
    }

    public function test_24_revocation_disables_authentication_permanently(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Revoke App', ['settings.read']);
        $plainTextKey = $res['plain_text_key'];
        $client = $res['client'];

        // Delete user revokes client
        $revokeRes = $this->actingAs($this->deleteUser)->postJson("/settings/data-sync/clients/{$client->id}/revoke");
        $revokeRes->assertStatus(200);

        $client->refresh();
        $this->assertFalse($client->is_active);

        // Authentication rejected with 401
        $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(401);
    }

    public function test_25_mapping_records_remain_unchanged_after_rotation_and_revocation(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Mapping Protected App', ['projects.write']);
        $client = $res['client'];

        // Create an entity mapping tied to this client
        $mapping = DataSyncEntityMapping::create([
            'api_client_id' => $client->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-TEST-999',
            'external_id' => 'EXT-999',
            'local_id' => 1234,
        ]);

        // 1. Rotate key
        $this->actingAs($this->editUser)->postJson("/settings/data-sync/clients/{$client->id}/rotate");

        // Mapping record still exists with identical attributes
        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'id' => $mapping->id,
            'api_client_id' => $client->id,
            'external_code' => 'CRM-CUS-TEST-999',
            'local_id' => 1234,
        ]);

        // 2. Revoke client
        $this->actingAs($this->deleteUser)->postJson("/settings/data-sync/clients/{$client->id}/revoke");

        // Mapping record is still intact
        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'id' => $mapping->id,
            'api_client_id' => $client->id,
            'external_code' => 'CRM-CUS-TEST-999',
            'local_id' => 1234,
        ]);
    }

    public function test_26_secret_hash_is_never_exposed_via_show_endpoint(): void
    {
        $client = ApiClient::create([
            'name' => 'Exposure Guard App',
            'key_id' => 'azo_live_exp1234567',
            'secret_hash' => hash('sha256', 'supersecretstring'),
            'scopes' => ['settings.read'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->viewUser)->getJson("/settings/data-sync/clients/{$client->id}");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayNotHasKey('secret_hash', $json['client']);
        $this->assertStringNotContainsString('supersecretstring', $response->getContent());
        $this->assertStringNotContainsString(hash('sha256', 'supersecretstring'), $response->getContent());
    }

    public function test_27_revoke_endpoint_supports_delete_method_and_renders_delete_form(): void
    {
        $clientService = app(ApiClientService::class);
        $res = $clientService->createClient('Delete Form App', ['settings.read']);
        $client = $res['client'];

        // 1. Verify view renders the delete form component
        $pageResponse = $this->actingAs($this->deleteUser)->get(route('settings.data-sync.clients'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee(route('settings.data-sync.clients.revoke', $client->id));
        $pageResponse->assertSee('delete-form');
        $pageResponse->assertSee('Revoke API Client?');

        // 2. Submit DELETE request (as sent by delete-form component)
        $deleteRes = $this->actingAs($this->deleteUser)->deleteJson(route('settings.data-sync.clients.revoke', $client->id));
        $deleteRes->assertStatus(200);

        $this->assertFalse($client->fresh()->is_active);
    }
}

