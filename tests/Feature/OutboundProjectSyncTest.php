<?php

namespace Tests\Feature;

use App\Jobs\DataSync\SendOutboundApiJob;
use App\Models\Customer;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Models\User;
use App\Services\DataSync\OutboundDataSyncService;
use App\Services\DataSync\OutboundPayloadService;
use App\Services\ProjectServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OutboundProjectSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected DataSyncOutboundConnection $connection;
    protected User $user;
    protected Customer $customer;
    protected ProjectStatus $statusOpen;
    protected ProjectStatus $statusCompleted;
    protected ProjectCategory $categoryWeb;
    protected ProjectCategory $categoryMobile;
    protected Technology $techPhp;
    protected Technology $techVue;

    protected function setUp(): void
    {
        parent::setUp();

        OutboundDataSyncService::setOriginatingClient(null);

        $this->user = User::factory()->create([
            'is_super_admin' => true,
        ]);
        $this->actingAs($this->user);

        $this->connection = DataSyncOutboundConnection::create([
            'name' => 'CRM External System',
            'base_url' => 'https://crm.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'test-bearer-token'],
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-PRJ-01',
            'name' => 'Acme Corporation',
            'is_active' => true,
        ]);

        $this->statusOpen = ProjectStatus::firstOrCreate(
            ['code' => '000001'],
            ['name' => 'Planning', 'color' => '#3b82f6', 'type' => 'in_progress', 'sort_order' => 1, 'is_active' => true]
        );

        $this->statusCompleted = ProjectStatus::firstOrCreate(
            ['code' => '000003'],
            ['name' => 'Completed', 'color' => '#10b981', 'type' => 'completed', 'sort_order' => 2, 'is_active' => true]
        );

        $this->categoryWeb = ProjectCategory::firstOrCreate(
            ['code' => '000002'],
            ['name' => 'Web Development', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]
        );

        $this->categoryMobile = ProjectCategory::firstOrCreate(
            ['code' => '000005'],
            ['name' => 'Mobile App', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]
        );

        $this->techPhp = Technology::firstOrCreate(
            ['code' => '000001'],
            ['name' => 'PHP', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]
        );

        $this->techVue = Technology::firstOrCreate(
            ['code' => '000004'],
            ['name' => 'Vue.js', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]
        );
    }

    protected function tearDown(): void
    {
        OutboundDataSyncService::setOriginatingClient(null);
        parent::tearDown();
    }

    /**
     * Helper to create a base project for testing.
     */
    protected function createProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'project_code' => 'PRJ-2026-001',
            'name' => 'Corporate Portal Redesign',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'high',
            'status_id' => $this->statusOpen->id,
            'start_date' => '2026-02-01',
            'end_date' => '2026-07-31',
            'customer_end_date' => '2026-07-31',
            'estimated_time_seconds' => 541800, // 150 hours 30 mins
            'customer_estimate_seconds' => 504000, // 140 hours 0 mins
            'project_category_ids' => [$this->categoryWeb->id],
            'is_active' => true,
        ], $attributes));
    }

    /**
     * 1. Status Update Operation:
     * Changing status triggers status_update outbound API with correct payload structure.
     */
    public function test_01_status_update_operation_dispatches_correct_payload(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $statusApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        // Update status using ProjectServices
        $service = app(ProjectServices::class);
        $service->updateStatus($project, $this->statusCompleted->id, '2026-03-01', 'Status changed to completed', $this->user);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $statusApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNotNull($history, 'Outbound history record should be created for status_update.');
        $this->assertSame(DataSyncOutboundHistory::STATUS_PENDING, $history->status);
        $this->assertSame('https://crm.example.com/projects/status-update', $history->request_url);

        $expectedPayload = [
            'project_code' => 'PRJ-2026-001',
            'status_code' => '000003',
        ];
        $this->assertEquals($expectedPayload, $history->request_payload);

        Queue::assertPushed(SendOutboundApiJob::class, fn($job) => $job->historyId === $history->id);
    }

    /**
     * 2. Project Update Operation:
     * Changing project details triggers update outbound API with correct payload structure.
     */
    public function test_02_project_details_update_dispatches_correct_payload(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $updateApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'POST',
            'endpoint' => '/projects/update',
            'is_active' => true,
        ]);

        $project = $this->createProject();
        $project->technologies()->sync([$this->techPhp->id, $this->techVue->id]);

        // Update project details using ProjectServices
        $service = app(ProjectServices::class);
        $service->update($project, [
            'name' => 'Corporate Portal Redesign v2',
            'customer_id' => $this->customer->id,
            'priority' => 'urgent',
            'default_billable' => true,
            'start_date' => '2026-02-01',
            'end_date' => '2026-08-15',
            'customer_end_date' => '2026-08-30',
            'estimated_time_minutes' => 9030, // 150 hours 30 mins
            'customer_estimate_minutes' => 8400, // 140 hours
            'project_category_ids' => [$this->categoryWeb->id, $this->categoryMobile->id],
            'project_technology_ids' => [$this->techPhp->id, $this->techVue->id],
        ], $this->user);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNotNull($history, 'Outbound history record should be created for update.');
        $this->assertSame('https://crm.example.com/projects/update', $history->request_url);

        $expectedPayload = [
            'pms_project_id' => 'PRJ-2026-001',
            'project_name' => 'Corporate Portal Redesign v2',
            'start_date' => '2026-02-01',
            'internal_deadline' => '2026-08-15',
            'client_deadline' => '2026-08-30',
            'internal_hours' => 150,
            'internal_minutes' => 30,
            'client_hours' => 140,
            'client_minutes' => 0,
            'technologies' => ['000001', '000004'],
            'project_types' => ['000002', '000005'],
        ];
        $this->assertEquals($expectedPayload, $history->request_payload);

        Queue::assertPushed(SendOutboundApiJob::class, fn($job) => $job->historyId === $history->id);
    }

    /**
     * 3. Operation Separation:
     * When only status changes, only status_update API is triggered.
     * Update API is not called.
     */
    public function test_03_only_status_changed_triggers_only_status_update(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $statusApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $updateApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'POST',
            'endpoint' => '/projects/update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        // Change only status
        $project->update(['status_id' => $this->statusCompleted->id]);

        $statusHistory = DataSyncOutboundHistory::where('outbound_api_id', $statusApi->id)
            ->where('entity_id', $project->id)
            ->first();
        $updateHistory = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNotNull($statusHistory, 'status_update must be dispatched.');
        $this->assertNull($updateHistory, 'update must NOT be dispatched when only status changes.');
    }

    /**
     * 4. Operation Separation:
     * When only details change, only update API is triggered.
     * Status update API is not called.
     */
    public function test_04_only_details_changed_triggers_only_update(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $statusApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $updateApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'POST',
            'endpoint' => '/projects/update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        // Change only details
        $project->update(['name' => 'Renamed Portal Project']);

        $statusHistory = DataSyncOutboundHistory::where('outbound_api_id', $statusApi->id)
            ->where('entity_id', $project->id)
            ->first();
        $updateHistory = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNull($statusHistory, 'status_update must NOT be dispatched when only details change.');
        $this->assertNotNull($updateHistory, 'update must be dispatched.');
    }

    /**
     * 5. Both Changed Together:
     * When both status and details change, both APIs are triggered independently.
     */
    public function test_05_both_changed_triggers_both_operations(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $statusApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $updateApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'POST',
            'endpoint' => '/projects/update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        // Change both status and details
        $project->update([
            'status_id' => $this->statusCompleted->id,
            'name' => 'Completed Portal Project',
        ]);

        $statusHistory = DataSyncOutboundHistory::where('outbound_api_id', $statusApi->id)
            ->where('entity_id', $project->id)
            ->first();
        $updateHistory = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNotNull($statusHistory, 'status_update must be dispatched.');
        $this->assertNotNull($updateHistory, 'update must be dispatched.');
        $this->assertSame('000003', $statusHistory->request_payload['status_code']);
        $this->assertSame('Completed Portal Project', $updateHistory->request_payload['project_name']);
    }

    /**
     * 6. No Irrelevant Updates:
     * Changes to irrelevant fields (e.g. updated_by, domain) do not trigger outbound sync.
     */
    public function test_06_irrelevant_fields_do_not_trigger_outbound_sync(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'update',
            'method' => 'POST',
            'endpoint' => '/projects/update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        // Update irrelevant field (domain, default_billable, updated_by)
        $project->update([
            'domain' => 'https://example.local',
            'updated_by' => $this->user->id,
        ]);

        $histories = DataSyncOutboundHistory::where('entity_id', $project->id)->get();
        $this->assertCount(0, $histories, 'No outbound sync should occur for irrelevant field changes.');
        Queue::assertNothingPushed();
    }

    /**
     * 7. Duplicate Request Prevention:
     * Identical consecutive status or details updates do not create redundant outbound requests.
     */
    public function test_07_duplicate_request_prevention(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $statusApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => true,
        ]);

        $project = $this->createProject();

        $syncService = app(OutboundDataSyncService::class);

        // First sync dispatch
        $histories1 = $syncService->sync('projects', 'status_update', $project);
        $this->assertCount(1, $histories1);

        // Second sync dispatch with identical payload while first is still pending
        $histories2 = $syncService->sync('projects', 'status_update', $project);
        $this->assertCount(1, $histories2);
        $this->assertSame($histories1[0]->id, $histories2[0]->id, 'Redundant pending job should be deduplicated.');

        // Total histories in database should remain 1
        $count = DataSyncOutboundHistory::where('outbound_api_id', $statusApi->id)
            ->where('entity_id', $project->id)
            ->count();
        $this->assertSame(1, $count);
    }

    /**
     * 8. Payload Structure & Value Accuracy:
     * Hours and minutes conversion, dates, arrays of codes are accurately formed.
     */
    public function test_08_payload_structure_and_conversion_accuracy(): void
    {
        $project = $this->createProject([
            'project_code' => 'PRJ-ALPHA-99',
            'name' => 'Accuracy Test Project',
            'start_date' => '2026-05-10',
            'end_date' => '2026-11-20',
            'customer_end_date' => '2026-12-05',
            'estimated_time_seconds' => 7320, // 2 hours, 2 minutes
            'customer_estimate_seconds' => 3600, // 1 hour, 0 minutes
            'project_category_ids' => [$this->categoryWeb->id, $this->categoryMobile->id],
        ]);
        $project->technologies()->sync([$this->techPhp->id, $this->techVue->id]);

        $payloadService = app(OutboundPayloadService::class);

        // Verify status_update payload
        $statusPayload = $payloadService->buildPayload('projects', 'status_update', $project, $this->connection);
        $this->assertSame([
            'project_code' => 'PRJ-ALPHA-99',
            'status_code' => '000001',
        ], $statusPayload);

        // Verify update payload
        $updatePayload = $payloadService->buildPayload('projects', 'update', $project, $this->connection);
        $this->assertSame([
            'pms_project_id' => 'PRJ-ALPHA-99',
            'project_name' => 'Accuracy Test Project',
            'start_date' => '2026-05-10',
            'internal_deadline' => '2026-11-20',
            'client_deadline' => '2026-12-05',
            'internal_hours' => 2,
            'internal_minutes' => 2,
            'client_hours' => 1,
            'client_minutes' => 0,
            'technologies' => ['000001', '000004'],
            'project_types' => ['000002', '000005'],
        ], $updatePayload);
    }

    /**
     * 9. Missing Optional Values Handling:
     * Null dates, null estimates, and empty relationships produce graceful defaults.
     */
    public function test_09_missing_optional_values_handling(): void
    {
        // Test in-memory model or model with status without code
        $project = new Project([
            'project_code' => 'PRJ-EMPTY-01',
            'name' => 'Minimal Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'low',
            'status_id' => null,
            'start_date' => null,
            'end_date' => null,
            'customer_end_date' => null,
            'estimated_time_seconds' => null,
            'customer_estimate_seconds' => null,
            'project_category_ids' => null,
            'is_active' => true,
        ]);

        $payloadService = app(OutboundPayloadService::class);

        $statusPayload = $payloadService->buildPayload('projects', 'status_update', $project, $this->connection);
        $this->assertSame('PRJ-EMPTY-01', $statusPayload['project_code']);
        $this->assertSame('', $statusPayload['status_code']);

        $updatePayload = $payloadService->buildPayload('projects', 'update', $project, $this->connection);
        $this->assertSame('PRJ-EMPTY-01', $updatePayload['pms_project_id']);
        $this->assertSame('Minimal Project', $updatePayload['project_name']);
        $this->assertNull($updatePayload['start_date']);
        $this->assertNull($updatePayload['internal_deadline']);
        $this->assertNull($updatePayload['client_deadline']);
        $this->assertSame(0, $updatePayload['internal_hours']);
        $this->assertSame(0, $updatePayload['internal_minutes']);
        $this->assertSame(0, $updatePayload['client_hours']);
        $this->assertSame(0, $updatePayload['client_minutes']);
        $this->assertSame([], $updatePayload['technologies']);
        $this->assertSame([], $updatePayload['project_types']);
    }

    /**
     * 10. Inactive Connection and API Skipping:
     * Inactive configurations are silently ignored without creating history.
     */
    public function test_10_inactive_connection_and_api_skipping(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        // Inactive API
        $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'status_update',
            'method' => 'POST',
            'endpoint' => '/projects/status-update',
            'is_active' => false,
        ]);

        $project = $this->createProject();
        $project->update(['status_id' => $this->statusCompleted->id]);

        $this->assertSame(0, DataSyncOutboundHistory::count());
        Queue::assertNothingPushed();

        // Inactive Connection
        $this->connection->update(['is_active' => false]);
        $project->update(['name' => 'Renamed Name']);

        $this->assertSame(0, DataSyncOutboundHistory::count());
        Queue::assertNothingPushed();
    }
}
