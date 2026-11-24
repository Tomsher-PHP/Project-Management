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
     * Scenario 1: Projects Status Update + Update → Allowed together.
     */
    public function test_scenario_1_status_update_and_update_are_allowed_together(): void
    {
        // Add Status Update
        $resStatus = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'projects',
                'operation' => 'status_update',
                'method' => 'POST',
                'endpoint' => '/api/projects/status-update',
                'is_active' => 1,
            ]);
        $resStatus->assertStatus(201);

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
     * Scenario 2: Technologies Sync + Delete → Allowed together.
     */
    public function test_scenario_2_sync_and_delete_are_allowed_together(): void
    {
        // Add Sync
        $resSync = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'technologies',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/technologies/sync',
                'is_active' => 1,
            ]);
        $resSync->assertStatus(201);

        // Add Delete
        $resDelete = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'technologies',
                'operation' => 'delete',
                'method' => 'DELETE',
                'endpoint' => '/api/technologies/{code}',
                'is_active' => 1,
            ]);
        $resDelete->assertStatus(201);

        $this->assertSame(2, $this->connection->apis()->where('module', 'technologies')->count());
    }

    /**
     * Scenario 3: Unsupported operations are rejected.
     */
    public function test_scenario_3_unsupported_operations_are_rejected(): void
    {
        // Technologies does not support update
        $resBad = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'technologies',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/technologies/{id}',
                'is_active' => 1,
            ]);

        $resBad->assertStatus(422);
        $resBad->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('not supported for module [technologies]', $resBad->json('errors.operation.0'));
    }

    /**
     * Scenario 4: Duplicate operation → Rejected.
     */
    public function test_scenario_4_duplicate_operation_is_rejected(): void
    {
        $this->connection->apis()->create([
            'module' => 'project_categories',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/categories/sync',
            'is_active' => true,
        ]);

        $resDuplicate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'project_categories',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/categories/duplicate-sync',
                'is_active' => 1,
            ]);

        $resDuplicate->assertStatus(422);
        $resDuplicate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('already configured for the selected module', $resDuplicate->json('errors.operation.0'));
    }

    /**
     * Scenario 5: Same module under different connections → Allowed.
     */
    public function test_scenario_5_same_module_under_different_connections_is_allowed(): void
    {
        $connection2 = DataSyncOutboundConnection::create([
            'name' => 'Second Outbound Connection',
            'base_url' => 'https://api.second.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'conn2-bearer-token'],
            'is_active' => true,
        ]);

        // Connection 1 has update on projects
        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/v1/projects/{id}',
            'is_active' => true,
        ]);

        // Connection 2 can configure update on projects without conflict
        $resConn2 = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$connection2->id}/apis", [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/v2/projects/{id}',
                'is_active' => 1,
            ]);

        $resConn2->assertStatus(201);
    }

    /**
     * Scenario 6: Editing an existing API without changing its operation → Allowed.
     */
    public function test_scenario_6_editing_existing_api_without_changing_operation_is_allowed(): void
    {
        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/v1/tech-sync',
            'is_active' => true,
        ]);

        $resUpdate = $this->actingAs($this->authorizedUser)
            ->putJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis/{$api->id}", [
                'module' => 'technologies',
                'operation' => 'sync',
                'method' => 'PUT',
                'endpoint' => '/api/v2/tech-new-endpoint',
                'is_active' => 1,
            ]);

        $resUpdate->assertStatus(200);
        $api->refresh();
        $this->assertSame('/api/v2/tech-new-endpoint', $api->endpoint);
        $this->assertSame('PUT', $api->method);
    }

    /**
     * Scenario 7: Inactive API configurations still prevent duplicates.
     */
    public function test_scenario_7_inactive_api_configurations_still_prevent_duplicates(): void
    {
        // Inactive 'sync' API
        $this->connection->apis()->create([
            'module' => 'project_statuses',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/statuses/sync',
            'is_active' => false,
        ]);

        // Attempting to add duplicate 'sync' while existing is inactive must still fail
        $resCreate = $this->actingAs($this->authorizedUser)
            ->postJson("/settings/data-sync/outbound/connections/{$this->connection->id}/apis", [
                'module' => 'project_statuses',
                'operation' => 'sync',
                'method' => 'POST',
                'endpoint' => '/api/statuses/another-sync',
                'is_active' => 1,
            ]);

        $resCreate->assertStatus(422);
        $resCreate->assertJsonValidationErrors('operation');
        $this->assertStringContainsString('already configured for the selected module', $resCreate->json('errors.operation.0'));
    }

    /**
     * Scenario 8: Concurrent attempts to create duplicate operations are prevented.
     */
    public function test_scenario_8_concurrent_attempts_to_create_duplicates_are_prevented(): void
    {
        $service = app(OutboundConnectionService::class);

        // Transaction A creates 'update'
        DB::beginTransaction();
        try {
            $service->createApi($this->connection, [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/projects/{id}',
                'is_active' => true,
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Subsequent attempt to create duplicate 'update' within another transaction must be blocked by validation
        $conflictThrown = false;
        try {
            $service->createApi($this->connection, [
                'module' => 'projects',
                'operation' => 'update',
                'method' => 'PUT',
                'endpoint' => '/api/projects/update-duplicate',
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $conflictThrown = true;
            $this->assertArrayHasKey('operation', $e->errors());
            $this->assertStringContainsString('already configured', $e->errors()['operation'][0]);
        }

        $this->assertTrue($conflictThrown);
        $this->assertSame(1, $this->connection->apis()->where('module', 'projects')->count());
        $this->assertSame('update', $this->connection->apis()->where('module', 'projects')->first()->operation);
    }
}
