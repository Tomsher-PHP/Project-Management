<?php

namespace Tests\Feature;

use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\DataSync\OutboundConnectionService;
use App\Services\DataSync\OutboundDataSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OutboundModuleOperationMappingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $authorizedUser;
    protected DataSyncOutboundConnection $connection;

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

        $this->connection = DataSyncOutboundConnection::create([
            'name' => 'Module Op Mapping Test Connection',
            'base_url' => 'https://api.module-test.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'secure-module-token-xyz'],
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Service getOperationsForModule returns exact configured mappings.
     */
    public function test_01_service_module_operations_mapping(): void
    {
        $this->assertEquals(
            ['sync' => 'Sync / Upsert', 'delete' => 'Delete'],
            OutboundConnectionService::getOperationsForModule('technologies')
        );

        $this->assertEquals(
            ['sync' => 'Sync / Upsert', 'delete' => 'Delete'],
            OutboundConnectionService::getOperationsForModule('industries')
        );

        $this->assertEquals(
            ['sync' => 'Sync / Upsert', 'delete' => 'Delete'],
            OutboundConnectionService::getOperationsForModule('project_categories')
        );

        $this->assertEquals(
            ['sync' => 'Sync / Upsert', 'delete' => 'Delete'],
            OutboundConnectionService::getOperationsForModule('project_statuses')
        );

        $this->assertEquals(
            ['update' => 'Update', 'status_update' => 'Status Update'],
            OutboundConnectionService::getOperationsForModule('projects')
        );

        $this->assertEmpty(OutboundConnectionService::getOperationsForModule('customers'));
        $this->assertEmpty(OutboundConnectionService::getOperationsForModule('meetings'));
        $this->assertEmpty(OutboundConnectionService::getOperationsForModule('non_existent_module'));
    }

    /**
     * Test 2: Valid operations can be created for technologies, industries, project categories, and statuses.
     */
    public function test_02_can_create_valid_settings_master_operations(): void
    {
        // technologies + sync
        $resTechSync = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'technologies',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/technologies/sync',
                'is_active' => 1,
            ]
        );
        $resTechSync->assertStatus(201);

        // technologies + delete
        $resTechDelete = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'technologies',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/technologies/{code}',
                'is_active' => 1,
            ]
        );
        $resTechDelete->assertStatus(201);

        // industries + sync
        $resIndSync = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'industries',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/industries/sync',
                'is_active' => 1,
            ]
        );
        $resIndSync->assertStatus(201);

        // project_categories + delete
        $resCatDelete = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'project_categories',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/categories/{code}',
                'is_active' => 1,
            ]
        );
        $resCatDelete->assertStatus(201);

        // project_statuses + sync
        $resStatusSync = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'project_statuses',
                'operation' => 'sync',
                'method' => 'PUT',
                'endpoint' => '/api/statuses/sync',
                'is_active' => 1,
            ]
        );
        $resStatusSync->assertStatus(201);
    }

    /**
     * Test 3: Valid operations can be created for projects (status_update and update).
     */
    public function test_03_can_create_valid_projects_operations(): void
    {
        // projects + status_update
        $resStatusUpdate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'status_update',
                'method' => 'POST',
                'endpoint' => '/api/projects/status-update',
                'is_active' => 1,
            ]
        );
        $resStatusUpdate->assertStatus(201);

        // projects + update
        $resUpdate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/projects/{id}',
                'is_active' => 1,
            ]
        );
        $resUpdate->assertStatus(201);

        $this->assertSame(2, $this->connection->apis()->where('module', 'projects')->count());
    }

    /**
     * Test 4: Unsupported operations on technologies are rejected.
     */
    public function test_04_unsupported_operations_on_technologies_are_rejected(): void
    {
        // technologies + create -> Rejected
        $resCreate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'technologies',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/technologies',
            ]
        );
        $resCreate->assertStatus(422);
        $resCreate->assertJsonValidationErrors('operation');

        // technologies + update -> Rejected
        $resUpdate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'technologies',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/technologies',
            ]
        );
        $resUpdate->assertStatus(422);
        $resUpdate->assertJsonValidationErrors('operation');

        // technologies + status_update -> Rejected
        $resStatusUpdate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'technologies',
                'operation' => 'status_update',
                'method' => 'POST',
                'endpoint' => '/api/technologies/status',
            ]
        );
        $resStatusUpdate->assertStatus(422);
        $resStatusUpdate->assertJsonValidationErrors('operation');
    }

    /**
     * Test 5: Unsupported operations on projects are rejected (sync, create, delete).
     */
    public function test_05_unsupported_operations_on_projects_are_rejected(): void
    {
        // projects + sync -> Rejected
        $resSync = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/projects/sync',
            ]
        );
        $resSync->assertStatus(422);
        $resSync->assertJsonValidationErrors('operation');

        // projects + create -> Rejected
        $resCreate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/projects',
            ]
        );
        $resCreate->assertStatus(422);
        $resCreate->assertJsonValidationErrors('operation');

        // projects + delete -> Rejected
        $resDelete = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/projects/{id}',
            ]
        );
        $resDelete->assertStatus(422);
        $resDelete->assertJsonValidationErrors('operation');
    }

    /**
     * Test 6: Customers and Meetings are rejected when adding or editing an outbound API.
     */
    public function test_06_customers_and_meetings_are_rejected_when_configuring_apis(): void
    {
        $resCust = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'customers',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/customers/{id}',
            ]
        );
        $resCust->assertStatus(422);
        $resCust->assertJsonValidationErrors('module');

        $resMeeting = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'meetings',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/meetings/{id}',
            ]
        );
        $resMeeting->assertStatus(422);
        $resMeeting->assertJsonValidationErrors('module');
    }

    /**
     * Test 7: Duplicate operation validation continues working (unique connection_id + module + operation).
     */
    public function test_07_duplicate_operation_validation_is_enforced(): void
    {
        // First valid API
        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/api/projects/status-1',
            'is_active' => true,
        ]);

        // Second API with identical module + operation -> Rejected
        $resDuplicate = $this->actingAs($this->authorizedUser)->postJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis",
            [
                'module' => 'projects',
                'operation' => 'status_update',
                'method' => 'POST',
                'endpoint' => '/api/projects/status-2',
            ]
        );
        $resDuplicate->assertStatus(422);
        $resDuplicate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('already configured', $resDuplicate->json('errors.operation.0'));
    }

    /**
     * Test 8: Editing an existing valid configuration works.
     */
    public function test_08_editing_existing_valid_configuration_works(): void
    {
        $api = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/v1/projects/{id}',
            'is_active' => true,
        ]);

        // Update endpoint and method, preserving valid operation
        $resEdit = $this->actingAs($this->authorizedUser)->putJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$api->id}",
            [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PATCH',
                'endpoint' => '/api/v2/projects/{id}',
                'is_active' => 1,
            ]
        );

        $resEdit->assertStatus(200);
        $api->refresh();
        $this->assertSame('PATCH', $api->method);
        $this->assertSame('/api/v2/projects/{id}', $api->endpoint);

        // Attempting to change operation to unsupported 'sync' -> Rejected
        $resBadEdit = $this->actingAs($this->authorizedUser)->putJson(
            "/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$api->id}",
            [
                'module' => 'projects',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/projects/sync',
            ]
        );
        $resBadEdit->assertStatus(422);
        $resBadEdit->assertJsonValidationErrors('operation');
    }

    /**
     * Test 9: Existing records using legacy operations are preserved and not automatically deleted.
     */
    public function test_09_existing_unsupported_records_remain_preserved_in_database(): void
    {
        // Insert legacy record (e.g. 'create' on projects) directly into database
        $legacyApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/legacy/projects/create',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('data_sync_outbound_apis', [
            'id' => $legacyApi->id,
            'module' => 'projects',
            'operation' => 'create',
        ]);

        // Service listing and database queries preserve the record
        $this->assertTrue($this->connection->apis()->where('id', $legacyApi->id)->exists());
    }

    /**
     * Test 10: Edit connection UI provides only modules with configured operations.
     */
    public function test_10_edit_connection_view_excludes_customers_and_meetings(): void
    {
        $response = $this->actingAs($this->authorizedUser)
            ->get("/settings/data-sync/outbound/connections/{$this->connection->id}/edit");

        $response->assertStatus(200);
        $availableModules = $response->viewData('availableModules');

        $this->assertArrayHasKey('projects', $availableModules);
        $this->assertArrayHasKey('technologies', $availableModules);
        $this->assertArrayHasKey('industries', $availableModules);
        $this->assertArrayHasKey('project_categories', $availableModules);
        $this->assertArrayHasKey('project_statuses', $availableModules);

        // Customers and Meetings are NOT selectable
        $this->assertArrayNotHasKey('customers', $availableModules);
        $this->assertArrayNotHasKey('meetings', $availableModules);
    }

    /**
     * Test 11: Outbound synchronization engine recognizes status_update for projects only.
     */
    public function test_11_engine_recognizes_status_update_for_projects_only(): void
    {
        $syncService = app(OutboundDataSyncService::class);

        $statusUpdateApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/api/projects/status-update',
            'is_active' => true,
        ]);

        // Directly querying status_update for projects resolves the status_update API
        $resolved = $syncService->resolveMatchingApi($this->connection, 'status_update', 'projects');
        $this->assertNotNull($resolved);
        $this->assertSame($statusUpdateApi->id, $resolved->id);

        // Querying status_update for any other module returns null
        $this->assertNull($syncService->resolveMatchingApi($this->connection, 'status_update', 'technologies'));
        $this->assertNull($syncService->resolveMatchingApi($this->connection, 'status_update', 'customers'));
    }
}

