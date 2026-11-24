<?php

namespace Tests\Feature;

use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\User;
use App\Services\DataSync\OutboundConnectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncOutboundApiConflictTest extends TestCase
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
            'name' => 'Conflict Test Connection',
            'base_url' => 'https://api.external.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'test-bearer-token'],
            'is_active' => true,
        ]);
    }

    /**
     * Scenario 1: Create + Update → Allowed.
     */
    public function test_scenario_1_create_and_update_are_allowed_together(): void
    {
        // Add Create
        $resCreate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'projects',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/projects',
                'is_active' => 1,
            ]);
        $resCreate->assertStatus(201);

        // Add Update
        $resUpdate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/projects/{id}',
                'is_active' => 1,
            ]);
        $resUpdate->assertStatus(201);

        $this->assertSame(2, $this->connection->apis()->where('module', 'projects')->count());
    }

    /**
     * Scenario 2: Create + Sync → Rejected.
     */
    public function test_scenario_2_create_plus_sync_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/projects',
            'is_active' => true,
        ]);

        $resSync = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'projects',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/projects/sync',
                'is_active' => 1,
            ]);

        $resSync->assertStatus(422);
        $resSync->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Create or Update is already configured for this module', $resSync->json('errors.operation.0'));
    }

    /**
     * Scenario 3: Update + Sync → Rejected.
     */
    public function test_scenario_3_update_plus_sync_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $resSync = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'technologies',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/technologies/sync',
                'is_active' => 1,
            ]);

        $resSync->assertStatus(422);
        $resSync->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Create or Update is already configured for this module', $resSync->json('errors.operation.0'));
    }

    /**
     * Scenario 4: Sync + Create → Rejected.
     */
    public function test_scenario_4_sync_plus_create_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/customers/sync',
            'is_active' => true,
        ]);

        $resCreate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'customers',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/customers',
                'is_active' => 1,
            ]);

        $resCreate->assertStatus(422);
        $resCreate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Sync / Upsert is already configured for this module', $resCreate->json('errors.operation.0'));
    }

    /**
     * Scenario 5: Sync + Update → Rejected.
     */
    public function test_scenario_5_sync_plus_update_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/customers/sync',
            'is_active' => true,
        ]);

        $resUpdate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'customers',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/customers/{id}',
                'is_active' => 1,
            ]);

        $resUpdate->assertStatus(422);
        $resUpdate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Sync / Upsert is already configured for this module', $resUpdate->json('errors.operation.0'));
    }

    /**
     * Scenario 6: Sync + Delete → Allowed.
     */
    public function test_scenario_6_sync_and_delete_are_allowed_together(): void
    {
        $this->connection->apis()->create([
            'module' => 'industries',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/industries/sync',
            'is_active' => true,
        ]);

        $resDelete = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'industries',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/industries/{id}',
                'is_active' => 1,
            ]);

        $resDelete->assertStatus(201);
        $this->assertSame(2, $this->connection->apis()->where('module', 'industries')->count());
    }

    /**
     * Scenario 7: Create + Update + Delete → Allowed.
     */
    public function test_scenario_7_create_update_and_delete_are_allowed_together(): void
    {
        foreach ([
            ['operation' => 'create', 'method' => 'POST', 'endpoint' => '/api/meetings'],
            ['operation' => 'update', 'method' => 'PUT', 'endpoint' => '/api/meetings/{id}'],
            ['operation' => 'delete', 'method' => 'DELETE', 'endpoint' => '/api/meetings/{id}'],
        ] as $apiData) {
            $res = $this->actingAs($this->authorizedUser)
                ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                    'module' => 'meetings',
                    'operation' => $apiData['operation'],
                    'method' => $apiData['method'],
                    'endpoint' => $apiData['endpoint'],
                    'is_active' => 1,
                ]);
            $res->assertStatus(201);
        }

        $this->assertSame(3, $this->connection->apis()->where('module', 'meetings')->count());
    }

    /**
     * Scenario 8: Duplicate operation → Rejected.
     */
    public function test_scenario_8_duplicate_operation_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'project_categories',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/categories',
            'is_active' => true,
        ]);

        $resDuplicate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'project_categories',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/categories/duplicate',
                'is_active' => 1,
            ]);

        $resDuplicate->assertStatus(422);
        $resDuplicate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('This operation is already configured for the selected module.', $resDuplicate->json('errors.operation.0'));
    }

    /**
     * Scenario 9: Same module under different connections → Allowed.
     */
    public function test_scenario_9_same_module_under_different_connections_is_allowed(): void
    {
        $connection2 = DataSyncOutboundConnection::create([
            'name' => 'Second Outbound Connection',
            'base_url' => 'https://api.second.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'conn2-bearer-token'],
            'is_active' => true,
        ]);

        // Connection 1 has sync on projects
        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/v1/projects/sync',
            'is_active' => true,
        ]);

        // Connection 2 can configure create on projects without conflict
        $resConn2 = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$connection2->id}/apis", [
                'module' => 'projects',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/v2/projects',
                'is_active' => 1,
            ]);

        $resConn2->assertStatus(201);
    }

    /**
     * Scenario 10: Editing an existing API without changing its operation → Allowed.
     */
    public function test_scenario_10_editing_existing_api_without_changing_operation_is_allowed(): void
    {
        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/tech',
            'is_active' => true,
        ]);

        // Update endpoint without changing operation
        $resUpdate = $this->actingAs($this->authorizedUser)
            ->putJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$api->id}", [
                'module' => 'technologies',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/v2/tech-new-endpoint',
                'is_active' => 1,
            ]);

        $resUpdate->assertStatus(200);
        $api->refresh();
        $this->assertSame('/api/v2/tech-new-endpoint', $api->endpoint);
    }

    /**
     * Scenario 11: Changing an API's module or operation → Validated against the destination combination.
     */
    public function test_scenario_11_changing_api_module_or_operation_is_validated_against_destination(): void
    {
        // Connection has Sync on 'customers'
        $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/customers/sync',
            'is_active' => true,
        ]);

        // Connection has Delete on 'projects'
        $projectDeleteApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/api/projects/{id}',
            'is_active' => true,
        ]);

        // Attempt to move the delete API to 'customers' with 'create' (conflicts with customers sync)
        $resInvalidMove = $this->actingAs($this->authorizedUser)
            ->putJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$projectDeleteApi->id}", [
                'module' => 'customers',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/customers',
                'is_active' => 1,
            ]);

        $resInvalidMove->assertStatus(422);
        $resInvalidMove->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Sync / Upsert is already configured for this module', $resInvalidMove->json('errors.operation.0'));

        // Moving to 'customers' with 'delete' should be allowed (since sync + delete is permitted)
        $resValidMove = $this->actingAs($this->authorizedUser)
            ->putJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$projectDeleteApi->id}", [
                'module' => 'customers',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/customers/{id}',
                'is_active' => 1,
            ]);

        $resValidMove->assertStatus(200);
        $projectDeleteApi->refresh();
        $this->assertSame('customers', $projectDeleteApi->module);
    }

    /**
     * Scenario 12: Inactive API configurations → Still included in conflict validation.
     */
    public function test_scenario_12_inactive_api_configurations_still_prevent_conflicts(): void
    {
        // Inactive 'sync' API
        $this->connection->apis()->create([
            'module' => 'project_statuses',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/statuses/sync',
            'is_active' => false,
        ]);

        // Attempting to add 'create' while 'sync' is inactive must still fail
        $resCreate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'project_statuses',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/statuses',
                'is_active' => 1,
            ]);

        $resCreate->assertStatus(422);
        $resCreate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('Sync / Upsert is already configured for this module', $resCreate->json('errors.operation.0'));
    }

    /**
     * Scenario 13: Concurrent attempts to create conflicting operations → Only a valid combination is persisted.
     */
    public function test_scenario_13_concurrent_attempts_to_create_conflicts_are_prevented(): void
    {
        $service = app(OutboundConnectionService::class);

        // Transaction A creates 'create'
        DB::beginTransaction();
        try {
            $service->createApi($this->connection, [
                'module' => 'projects',
                'operation' => 'create',
                'method' => 'POST',
                'endpoint' => '/api/projects',
                'is_active' => true,
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Subsequent attempt to create 'sync' within another transaction must be blocked by validation
        $conflictThrown = false;
        try {
            $service->createApi($this->connection, [
                'module' => 'projects',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/projects/sync',
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $conflictThrown = true;
            $this->assertArrayHasKey('operation', $e->errors());
            $this->assertStringContainsString('Create or Update is already configured for this module', $e->errors()['operation'][0]);
        }

        $this->assertTrue($conflictThrown);
        $this->assertSame(1, $this->connection->apis()->where('module', 'projects')->count());
        $this->assertSame('create', $this->connection->apis()->where('module', 'projects')->first()->operation);
    }
}

