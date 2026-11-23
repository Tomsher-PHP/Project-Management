<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncSettingsNavigationTest extends TestCase
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

        $permission = Permission::firstOrCreate([
            'name' => 'data_sync_settings.view',
            'guard_name' => 'web',
        ]);
        $this->authorizedUser->givePermissionTo($permission);

        $this->unauthorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/settings/data-sync');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_receives_403(): void
    {
        // When requested with JSON / expectsJson, PermissionByType middleware returns HTTP 403 Forbidden
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync');
        $response->assertStatus(403);

        $responseClients = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/clients');
        $responseClients->assertStatus(403);

        $responseExplorer = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/explorer');
        $responseExplorer->assertStatus(403);

        $responseMappings = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/mappings');
        $responseMappings->assertStatus(403);

        $responseOutbound = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/outbound');
        $responseOutbound->assertStatus(403);

        $responseOutboundConnections = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/outbound/connections');
        $responseOutboundConnections->assertStatus(403);

        $responseOutboundHistory = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/outbound/history');
        $responseOutboundHistory->assertStatus(403);

        // Standard browser GET redirects back with error message
        $webResponse = $this->actingAs($this->unauthorizedUser)->get('/settings/data-sync');
        $webResponse->assertRedirect();
    }

    public function test_authorized_user_can_access_index_and_clients_tab_by_default(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync');
        $response->assertStatus(200);
        $response->assertSee('Data Sync');
        $response->assertSee('Manage API clients, explore endpoints, and view external record mappings.');
        $response->assertSee('API Clients');
        $response->assertSee('API Explorer');
        $response->assertSee('Record Mappings');
        $response->assertSee('Manage API credentials and permissions for connected external applications.');
    }

    public function test_authorized_user_can_access_clients_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/clients');
        $response->assertStatus(200);
        $response->assertSee('Manage API credentials and permissions for connected external applications.');
        $response->assertSee(route('settings.index'));
        // Check active tab styling
        $response->assertSee('bg-success-300 text-white shadow-sm', false);
    }

    public function test_authorized_user_can_access_explorer_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/explorer');
        $response->assertStatus(200);
        $response->assertSee('Browse available Data Sync modules, endpoints, and request examples.');
        $response->assertSee(route('settings.index'));
    }

    public function test_authorized_user_can_access_mappings_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/mappings');
        $response->assertStatus(200);
        $response->assertSee('View relationships between external system records and PMS customers or projects.');
        $response->assertSee(route('settings.index'));
    }

    public function test_authorized_user_can_access_outbound_index_and_connections_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound');
        $response->assertStatus(200);
        $response->assertSee('Data Sync');
        $response->assertSee('Outbound');
        $response->assertSee('Send data to external systems.');
        $response->assertSee('Connections');
        $response->assertSee('History');
        $response->assertSee('bg-success-300 text-white shadow-sm', false);
    }

    public function test_authorized_user_can_access_outbound_connections_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/connections');
        $response->assertStatus(200);
        $response->assertSee('Connections');
        $response->assertSee('History');
        $response->assertSee(route('settings.index'));
        $response->assertSee('bg-success-300 text-white shadow-sm', false);
    }

    public function test_authorized_user_can_access_outbound_history_tab(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history');
        $response->assertStatus(200);
        $response->assertSee('Connections');
        $response->assertSee('History');
        $response->assertSee(route('settings.index'));
        $response->assertSee('bg-success-300 text-white shadow-sm', false);
    }

    public function test_settings_index_shows_card_only_for_authorized_user(): void
    {
        $authorizedResponse = $this->actingAs($this->authorizedUser)->get('/settings');
        $authorizedResponse->assertStatus(200);
        $authorizedResponse->assertSee(route('settings.data-sync.index'));

        $unauthorizedResponse = $this->actingAs($this->unauthorizedUser)->get('/settings');
        $unauthorizedResponse->assertStatus(200);
        $unauthorizedResponse->assertDontSee(route('settings.data-sync.index'));
    }
}
