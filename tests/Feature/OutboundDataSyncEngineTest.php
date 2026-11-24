<?php

namespace Tests\Feature;

use App\Jobs\DataSync\SendOutboundApiJob;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use App\Models\Industry;
use App\Models\Meeting;
use App\Models\MeetingStatus;
use App\Models\MeetingType;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Models\User;
use App\Services\DataSync\OutboundDataSyncService;
use App\Services\DataSync\OutboundHttpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OutboundDataSyncEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected DataSyncOutboundConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        OutboundDataSyncService::setOriginatingClient(null);

        $this->connection = DataSyncOutboundConnection::create([
            'name' => 'Primary External System',
            'base_url' => 'https://api.external-system.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'secure-api-token-12345'],
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        OutboundDataSyncService::setOriginatingClient(null);
        parent::tearDown();
    }

    /**
     * Scenario 1: Technology Create → Create API.
     */
    public function test_01_technology_create_uses_create_api(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/v1/technologies',
            'is_active' => true,
        ]);

        $technology = Technology::create([
            'name' => 'RustLang Engine',
            'code' => '990001',
            'sort_order' => 10,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame(DataSyncOutboundHistory::STATUS_PENDING, $history->status);
        $this->assertSame('https://api.external-system.com/api/v1/technologies', $history->request_url);
        $this->assertSame('technologies', $history->request_payload['module']);
        $this->assertSame('990001', $history->request_payload['code']);
        $this->assertSame('RustLang Engine', $history->request_payload['name']);
        $this->assertSame(10, $history->request_payload['sort_order']);
        $this->assertTrue($history->request_payload['is_active']);
        $this->assertArrayNotHasKey('is_system', $history->request_payload);

        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);
    }

    /**
     * Scenario 2: Technology Create → Sync API (fallback when Create API is not configured).
     */
    public function test_02_technology_create_uses_sync_api_when_create_api_missing(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'sync',
            'method' => 'PUT',
            'endpoint' => '/api/v1/technologies/sync',
            'is_active' => true,
        ]);

        $technology = Technology::create([
            'name' => 'GoLang Microservices',
            'code' => '990002',
            'sort_order' => 20,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('https://api.external-system.com/api/v1/technologies/sync', $history->request_url);
        $this->assertSame('990002', $history->request_payload['code']);
        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);
    }

    /**
     * Scenario 3: Technology Update → Update API.
     */
    public function test_03_technology_update_uses_update_api(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $technology = Technology::create([
            'name' => 'Initial Tech',
            'code' => '990003',
            'sort_order' => 5,
            'is_system' => false,
            'is_active' => true,
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/v1/technologies/{id}',
            'is_active' => true,
        ]);

        $technology->update(['name' => 'Updated Tech Name']);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('https://api.external-system.com/api/v1/technologies/990003', $history->request_url);
        $this->assertSame('Updated Tech Name', $history->request_payload['name']);
        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);
    }

    /**
     * Scenario 4: Technology Update → Sync API (fallback when Update API is not configured).
     */
    public function test_04_technology_update_uses_sync_api(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $technology = Technology::create([
            'name' => 'Initial Sync Tech',
            'code' => '990004',
            'sort_order' => 5,
            'is_system' => false,
            'is_active' => true,
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'sync',
            'method' => 'POST',
            'endpoint' => '/api/v1/technologies/sync',
            'is_active' => true,
        ]);

        $technology->update(['name' => 'Updated via Sync API']);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('Updated via Sync API', $history->request_payload['name']);
        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);
    }

    /**
     * Scenario 5: Technology Delete → Delete API.
     */
    public function test_05_technology_delete_uses_delete_api(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $technology = Technology::create([
            'name' => 'Tech To Delete',
            'code' => '990005',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/api/v1/technologies/{id}',
            'is_active' => true,
        ]);

        $technology->delete();

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('https://api.external-system.com/api/v1/technologies/990005', $history->request_url);
        $this->assertSame('990005', $history->request_payload['code']);
        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);
    }

    /**
     * Scenario 6: Industry parent relationship payload.
     */
    public function test_06_industry_parent_relationship_payload(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $parent = Industry::create([
            'name' => 'Parent Industry',
            'code' => '990101',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'industries',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/industries',
            'is_active' => true,
        ]);

        $child = Industry::create([
            'name' => 'Child Industry',
            'code' => '990102',
            'parent_id' => $parent->id,
            'sort_order' => 2,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $child->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('industries', $history->request_payload['module']);
        $this->assertSame('990102', $history->request_payload['code']);
        $this->assertSame('Child Industry', $history->request_payload['name']);
        $this->assertSame('990101', $history->request_payload['parent_code']);
        $this->assertSame(2, $history->request_payload['sort_order']);
        $this->assertTrue($history->request_payload['is_active']);
        $this->assertArrayNotHasKey('is_system', $history->request_payload);
    }

    /**
     * Scenario 7: Project Category CRUD.
     */
    public function test_07_project_category_crud(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $createApi = $this->connection->apis()->create([
            'module' => 'project_categories',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/project-categories',
            'is_active' => true,
        ]);
        $updateApi = $this->connection->apis()->create([
            'module' => 'project_categories',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/project-categories/{id}',
            'is_active' => true,
        ]);
        $deleteApi = $this->connection->apis()->create([
            'module' => 'project_categories',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/api/project-categories/{id}',
            'is_active' => true,
        ]);

        // Create
        $category = ProjectCategory::create([
            'name' => 'Cloud Infrastructure',
            'code' => '990201',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);
        $createHistory = DataSyncOutboundHistory::where('outbound_api_id', $createApi->id)
            ->where('entity_id', $category->id)
            ->first();
        $this->assertNotNull($createHistory);
        $this->assertSame('project_categories', $createHistory->request_payload['module']);
        $this->assertSame('990201', $createHistory->request_payload['code']);
        $this->assertSame('Cloud Infrastructure', $createHistory->request_payload['name']);
        $this->assertSame(1, $createHistory->request_payload['sort_order']);
        $this->assertTrue($createHistory->request_payload['is_active']);
        $this->assertArrayNotHasKey('is_system', $createHistory->request_payload);

        // Update
        $category->update(['name' => 'Cloud & DevOps Infrastructure']);
        $updateHistory = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $category->id)
            ->first();
        $this->assertNotNull($updateHistory);
        $this->assertSame('Cloud & DevOps Infrastructure', $updateHistory->request_payload['name']);
        $this->assertSame('https://api.external-system.com/api/project-categories/990201', $updateHistory->request_url);

        // Delete
        $category->delete();
        $deleteHistory = DataSyncOutboundHistory::where('outbound_api_id', $deleteApi->id)
            ->where('entity_id', $category->id)
            ->first();
        $this->assertNotNull($deleteHistory);
        $this->assertSame('990201', $deleteHistory->request_payload['code']);
    }

    /**
     * Scenario 8: Project Status CRUD.
     */
    public function test_08_project_status_crud(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $createApi = $this->connection->apis()->create([
            'module' => 'project_statuses',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/project-statuses',
            'is_active' => true,
        ]);

        $status = ProjectStatus::create([
            'name' => 'In Code Review',
            'code' => '990301',
            'color' => '#3b82f6',
            'type' => 'in_progress',
            'sort_order' => 3,
            'is_default' => false,
            'is_completed' => false,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $createApi->id)
            ->where('entity_id', $status->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('project_statuses', $history->request_payload['module']);
        $this->assertSame('990301', $history->request_payload['code']);
        $this->assertSame('In Code Review', $history->request_payload['name']);
        $this->assertSame('#3b82f6', $history->request_payload['color']);
        $this->assertSame('in_progress', $history->request_payload['type']);
        $this->assertSame(3, $history->request_payload['sort_order']);
        $this->assertFalse($history->request_payload['is_default']);
        $this->assertTrue($history->request_payload['is_active']);
        $this->assertArrayNotHasKey('is_completed', $history->request_payload);
        $this->assertArrayNotHasKey('is_system', $history->request_payload);
    }

    /**
     * Scenario 9: Customer Create and Update.
     */
    public function test_09_customer_create_and_update(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $createApi = $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/customers',
            'is_active' => true,
        ]);
        $updateApi = $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/customers/{id}',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS99991',
            'name' => 'Apex Global Logistics',
            'email' => 'contact@apexlogistics.example.com',
            'is_active' => true,
        ]);

        $createHistory = DataSyncOutboundHistory::where('outbound_api_id', $createApi->id)
            ->where('entity_id', $customer->id)
            ->first();
        $this->assertNotNull($createHistory);
        $this->assertSame('CUS99991', $createHistory->request_payload['customer_code']);
        $this->assertSame('Apex Global Logistics', $createHistory->request_payload['name']);

        // Create an ApiClient for foreign key integrity in DataSyncEntityMapping
        $apiClient = ApiClient::create([
            'name' => 'CRM Test Client',
            'key_id' => 'pms_crm_key_' . uniqid(),
            'secret_hash' => hash('sha256', 'secret'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_WRITE],
            'is_active' => true,
        ]);

        // Establish an external mapping for the customer to verify {id} endpoint resolution
        DataSyncEntityMapping::create([
            'api_client_id' => $apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'EXT-CUS-101',
            'external_id' => 'crm_cus_777',
            'local_id' => $customer->id,
        ]);

        $customer->update(['name' => 'Apex Global Logistics LLC']);

        $updateHistory = DataSyncOutboundHistory::where('outbound_api_id', $updateApi->id)
            ->where('entity_id', $customer->id)
            ->first();
        $this->assertNotNull($updateHistory);
        $this->assertSame('Apex Global Logistics LLC', $updateHistory->request_payload['name']);
        $this->assertSame('https://api.external-system.com/api/customers/crm_cus_777', $updateHistory->request_url);
    }

    /**
     * Scenario 10: Project Create and Update.
     */
    public function test_10_project_create_and_update(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $customer = Customer::create([
            'customer_code' => 'CUS99992',
            'name' => 'Client for Project',
            'is_active' => true,
        ]);

        $status = ProjectStatus::firstOrCreate(
            ['code' => '990401'],
            ['name' => 'Active Status', 'color' => '#10b981', 'type' => 'in_progress', 'sort_order' => 1, 'is_active' => true]
        );

        $createApi = $this->connection->apis()->create([
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/projects',
            'is_active' => true,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ99991',
            'name' => 'Portal Overhaul',
            'customer_id' => $customer->id,
            'project_flow' => 'agile',
            'priority' => 'high',
            'status_id' => $status->id,
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $createHistory = DataSyncOutboundHistory::where('outbound_api_id', $createApi->id)
            ->where('entity_id', $project->id)
            ->first();

        $this->assertNotNull($createHistory);
        $this->assertSame('PRJ99991', $createHistory->request_payload['project_code']);
        $this->assertSame('Portal Overhaul', $createHistory->request_payload['name']);
        $this->assertSame('CUS99992', $createHistory->request_payload['customer_code']);
        $this->assertSame('agile', $createHistory->request_payload['project_flow']);
        $this->assertSame('high', $createHistory->request_payload['priority']);
    }

    /**
     * Scenario 11: Meeting Create and Update.
     */
    public function test_11_meeting_create_and_update(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $user = User::factory()->create();

        $type = MeetingType::firstOrCreate(
            ['name' => 'Sprint Meeting'],
            ['color' => '#10b981', 'sort_order' => 1]
        );

        $status = MeetingStatus::firstOrCreate(
            ['code' => 'scheduled'],
            ['name' => 'Scheduled', 'color' => '#3b82f6', 'sort_order' => 1]
        );

        $createApi = $this->connection->apis()->create([
            'module' => 'meetings',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/meetings',
            'is_active' => true,
        ]);

        $meeting = Meeting::create([
            'title' => 'Sprint Kickoff Discussion',
            'description' => 'Planning user stories for Sprint 12',
            'meeting_type_id' => $type->id,
            'meeting_status_id' => $status->id,
            'start_at' => '2026-12-01 10:00:00',
            'end_at' => '2026-12-01 11:00:00',
            'organizer_id' => $user->id,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $createApi->id)
            ->where('entity_id', $meeting->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('Sprint Kickoff Discussion', $history->request_payload['title']);
        $this->assertSame($user->email, $history->request_payload['organizer_email']);
    }

    /**
     * Scenario 12: Multiple Outbound Connections.
     */
    public function test_12_multiple_outbound_connections_create_independent_deliveries(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $secondConnection = DataSyncOutboundConnection::create([
            'name' => 'Secondary ERP System',
            'base_url' => 'https://erp.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'second-token'],
            'is_active' => true,
        ]);

        $api1 = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $api2 = $secondConnection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/erp/technologies',
            'is_active' => true,
        ]);

        $tech = Technology::create([
            'name' => 'Multi Connection Tech',
            'code' => '990501',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $histories = DataSyncOutboundHistory::where('entity_id', $tech->id)->get();

        $this->assertCount(2, $histories);
        $this->assertTrue($histories->contains('outbound_api_id', $api1->id));
        $this->assertTrue($histories->contains('outbound_api_id', $api2->id));
    }

    /**
     * Scenario 13: Inactive Connection → Skipped.
     */
    public function test_13_inactive_connection_is_skipped(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $this->connection->update(['is_active' => false]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $tech = Technology::create([
            'name' => 'Inactive Conn Tech',
            'code' => '990601',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $tech->id)
            ->first();

        $this->assertNull($history);
    }

    /**
     * Scenario 14: Inactive API → Skipped.
     */
    public function test_14_inactive_api_is_skipped(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => false,
        ]);

        $tech = Technology::create([
            'name' => 'Inactive Api Tech',
            'code' => '990602',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('outbound_api_id', $api->id)
            ->where('entity_id', $tech->id)
            ->first();

        $this->assertNull($history);
    }

    /**
     * Scenario 15: No Matching API → Skipped without creating history.
     */
    public function test_15_no_matching_api_is_skipped(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        // Connection only has 'delete' configured, but we 'create'
        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/api/technologies/{id}',
            'is_active' => true,
        ]);

        $tech = Technology::create([
            'name' => 'No Matching Api Tech',
            'code' => '990603',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::where('entity_id', $tech->id)->first();
        $this->assertNull($history);
    }

    /**
     * Scenario 16: API Success → History record updated.
     */
    public function test_16_api_success_updates_history_record(): void
    {
        Http::fake([
            'https://api.external-system.com/api/technologies' => Http::response(['id' => 'ext_1', 'status' => 'created'], 201),
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 123,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990701', 'name' => 'Testing Job'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        $history->refresh();
        $this->assertSame(DataSyncOutboundHistory::STATUS_SUCCESS, $history->status);
        $this->assertSame(201, $history->response_code);
        $this->assertStringContainsString('ext_1', $history->response_body);
        $this->assertNull($history->error_message);
    }

    /**
     * Scenario 17: API Failure → History record updated.
     */
    public function test_17_api_failure_updates_history_record(): void
    {
        Http::fake([
            'https://api.external-system.com/api/technologies' => Http::response(['error' => 'Validation failed', 'fields' => ['name']], 422),
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 124,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990702', 'name' => 'Bad Tech'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        $history->refresh();
        $this->assertSame(DataSyncOutboundHistory::STATUS_FAILED, $history->status);
        $this->assertSame(422, $history->response_code);
        $this->assertStringContainsString('Validation failed', $history->error_message);
    }

    /**
     * Scenario 18: Retryable Failure → History remains pending and job releases.
     */
    public function test_18_retryable_failure_maintains_pending_status(): void
    {
        Http::fake([
            'https://api.external-system.com/api/technologies' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 125,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990703', 'name' => 'Retryable Tech'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        $history->refresh();
        // During retry attempts, history remains pending
        $this->assertSame(DataSyncOutboundHistory::STATUS_PENDING, $history->status);
        $this->assertSame(429, $history->response_code);
        $this->assertNotNull($history->error_message);
    }

    /**
     * Scenario 19: Invalid Credentials → No automatic retry.
     */
    public function test_19_invalid_credentials_marks_failed_immediately_without_retry(): void
    {
        Http::fake([
            'https://api.external-system.com/api/technologies' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 126,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990704', 'name' => 'Unauthorized Tech'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        $history->refresh();
        // 401 is permanent, marked failed immediately
        $this->assertSame(DataSyncOutboundHistory::STATUS_FAILED, $history->status);
        $this->assertSame(401, $history->response_code);
    }

    /**
     * Scenario 20: Inbound-originated updates → No synchronization loop.
     */
    public function test_20_inbound_originated_updates_prevent_synchronization_loop(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        // Inbound ApiClient named 'External CRM'
        $apiClient = ApiClient::create([
            'name' => 'External CRM',
            'key_id' => 'pms_crm_key_' . uniqid(),
            'secret_hash' => hash('sha256', 'secret'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_WRITE],
            'is_active' => true,
        ]);

        // Outbound connection matching the originating CRM system
        $crmConnection = DataSyncOutboundConnection::create([
            'name' => 'External CRM',
            'base_url' => 'https://crm.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'crm-token'],
            'is_active' => true,
        ]);

        $crmApi = $crmConnection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/technologies/{id}',
            'is_active' => true,
        ]);

        // Secondary connection representing an ERP system (should receive sync)
        $erpConnection = DataSyncOutboundConnection::create([
            'name' => 'Internal ERP',
            'base_url' => 'https://erp.internal.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'erp-token'],
            'is_active' => true,
        ]);

        $erpApi = $erpConnection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/erp/technologies/{id}',
            'is_active' => true,
        ]);

        $technology = Technology::create([
            'name' => 'Loop Prevention Tech',
            'code' => '990801',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        // Simulate an inbound API request originating from $apiClient
        OutboundDataSyncService::setOriginatingClient($apiClient);

        // Update the technology
        $technology->update(['name' => 'Loop Prevention Tech Updated By Inbound']);

        // Check histories: CRM connection should be SKIPPED, ERP should be DELIVERED
        $crmHistory = DataSyncOutboundHistory::where('outbound_api_id', $crmApi->id)
            ->where('entity_id', $technology->id)
            ->first();

        $erpHistory = DataSyncOutboundHistory::where('outbound_api_id', $erpApi->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNull($crmHistory, 'Originating external CRM must NOT receive outbound synchronization (loop prevention).');
        $this->assertNotNull($erpHistory, 'Non-originating external systems MUST receive synchronization normally.');
    }

    /**
     * Scenario 21: Rolled-back database transaction → No outbound request.
     */
    public function test_21_rolled_back_database_transaction_triggers_no_outbound_request(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $historiesCountBefore = DataSyncOutboundHistory::count();

        try {
            DB::transaction(function () {
                Technology::create([
                    'name' => 'Transaction Rollback Tech',
                    'code' => '990901',
                    'sort_order' => 1,
                    'is_system' => false,
                    'is_active' => true,
                ]);

                // Simulate an exception triggering a rollback
                throw new \Exception('Simulated database transaction failure');
            });
        } catch (\Exception $e) {
            // Expected rollback
        }

        $historiesCountAfter = DataSyncOutboundHistory::count();
        $this->assertSame($historiesCountBefore, $historiesCountAfter, 'No outbound history or request may be dispatched when a transaction is rolled back.');
    }

    /**
     * Scenario 22: Linked Inbound ApiClient relationship prevents loop even with differing connection names.
     */
    public function test_22_linked_inbound_api_client_relationship_prevents_loop(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $apiClient = ApiClient::create([
            'name' => 'CRM EMEA Inbound Gateway',
            'key_id' => 'pms_crm_key_' . uniqid(),
            'secret_hash' => hash('sha256', 'secret'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_WRITE],
            'is_active' => true,
        ]);

        // Connection with a completely DIFFERENT name, but linked via api_client_id foreign key
        $linkedConnection = DataSyncOutboundConnection::create([
            'api_client_id' => $apiClient->id,
            'name' => 'Custom Dedicated Outbound Pipe',
            'base_url' => 'https://pipe.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'pipe-token'],
            'is_active' => true,
        ]);

        $linkedApi = $linkedConnection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/technologies/{id}',
            'is_active' => true,
        ]);

        // Secondary connection linked to a different API client or none (should receive sync)
        $unlinkedConnection = DataSyncOutboundConnection::create([
            'name' => 'Global Analytics Feed',
            'base_url' => 'https://analytics.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'analytics-token'],
            'is_active' => true,
        ]);

        $unlinkedApi = $unlinkedConnection->apis()->create([
            'module' => 'technologies',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/api/technologies/{id}',
            'is_active' => true,
        ]);

        $technology = Technology::create([
            'name' => 'ForeignKey Loop Test Tech',
            'code' => '990902',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        // Execute update under $apiClient source identity
        OutboundDataSyncService::withOriginatingClient($apiClient, function () use ($technology) {
            $technology->update(['name' => 'Tech Updated with FK Source Check']);
        });

        // The linked connection must be skipped because of api_client_id match
        $linkedHistory = DataSyncOutboundHistory::where('outbound_api_id', $linkedApi->id)
            ->where('entity_id', $technology->id)
            ->first();

        // The unlinked connection must receive synchronization
        $unlinkedHistory = DataSyncOutboundHistory::where('outbound_api_id', $unlinkedApi->id)
            ->where('entity_id', $technology->id)
            ->first();

        $this->assertNull($linkedHistory, 'Outbound connection linked by api_client_id must be skipped to prevent bidirectional loops.');
        $this->assertNotNull($unlinkedHistory, 'Unrelated outbound connections must receive synchronization normally.');
    }

    /**
     * Scenario 23: Automatic Pending Delivery Recovery via Scheduler Command.
     */
    public function test_23_pending_delivery_recovery_via_scheduler_command(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        // Create an abandoned pending history 15 minutes in the past
        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 999,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990903', 'name' => 'Stranded Delivery'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        DB::table('data_sync_outbound_histories')->where('id', $history->id)->update([
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        // Run recovery scheduler command
        $exitCode = \Illuminate\Support\Facades\Artisan::call('data-sync:recover-outbound-deliveries');
        $this->assertSame(0, $exitCode);

        // Verify the job was pushed to the queue
        Queue::assertPushed(SendOutboundApiJob::class, fn($j) => $j->historyId === $history->id);

        // Verify updated_at timestamp was refreshed
        $history->refresh();
        $this->assertTrue($history->updated_at->gt(now()->subMinutes(2)));

        // Run recovery command a second time immediately -> should be 0 because updated_at is fresh
        $service = app(OutboundDataSyncService::class);
        $recoveredSecondTime = $service->recoverPendingDeliveries();
        $this->assertSame(0, $recoveredSecondTime, 'Immediate subsequent recovery runs must not dispatch duplicate jobs.');
    }

    /**
     * Scenario 24: Duplicate job execution does not repeat successful request.
     */
    public function test_24_duplicate_job_execution_does_not_repeat_successful_request(): void
    {
        Http::fake();

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 888,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990904', 'name' => 'Already Delivered Tech'],
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 200,
            'response_body' => json_encode(['ok' => true]),
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        // Assert that HTTP request was never sent since history is already SUCCESS
        Http::assertNothingSent();
    }

    /**
     * Scenario 25: Recovery marks stranded history failed if connection became inactive or deleted.
     */
    public function test_25_recovery_marks_stranded_history_failed_if_connection_inactive(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 777,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990905', 'name' => 'Inactive Connection Tech'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        DB::table('data_sync_outbound_histories')->where('id', $history->id)->update([
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        // Connection becomes inactive before recovery runs
        $this->connection->update(['is_active' => false]);

        $service = app(OutboundDataSyncService::class);
        $service->recoverPendingDeliveries();

        $history->refresh();
        $this->assertSame(DataSyncOutboundHistory::STATUS_FAILED, $history->status);
        $this->assertStringContainsString('inactive or deleted', $history->error_message);
        Queue::assertNotPushed(SendOutboundApiJob::class);
    }

    /**
     * Scenario 26: Customer and Project Delete operations with stable external identifiers.
     */
    public function test_26_customer_and_project_delete_operations(): void
    {
        Queue::fake([SendOutboundApiJob::class]);

        $apiClient = ApiClient::create([
            'name' => 'CRM Test Client 26',
            'key_id' => 'pms_crm_key_' . uniqid(),
            'secret_hash' => hash('sha256', 'secret'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_WRITE],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS99993',
            'name' => 'Client To Delete',
            'is_active' => true,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'EXT-CUS-DELETE',
            'external_id' => 'crm_cus_del_999',
            'local_id' => $customer->id,
        ]);

        $customerDeleteApi = $this->connection->apis()->create([
            'module' => 'customers',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/api/customers/{id}',
            'is_active' => true,
        ]);

        $customer->delete();

        $history = DataSyncOutboundHistory::where('outbound_api_id', $customerDeleteApi->id)
            ->where('entity_id', $customer->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('https://api.external-system.com/api/customers/crm_cus_del_999', $history->request_url);
        $this->assertSame('CUS99993', $history->request_payload['customer_code']);
        $this->assertSame('crm_cus_del_999', $history->request_payload['external_id']);
    }

    /**
     * Scenario 27: Bearer Token Authorization header sent in HTTP request and token not exposed.
     */
    public function test_27_bearer_token_authorization_header_sent_and_not_exposed_in_history(): void
    {
        Http::fake([
            'https://api.external-system.com/api/technologies' => Http::response(['ok' => true], 200),
        ]);

        $api = $this->connection->apis()->create([
            'module' => 'technologies',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/api/technologies',
            'is_active' => true,
        ]);

        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $api->id,
            'entity_id' => 991,
            'request_url' => 'https://api.external-system.com/api/technologies',
            'request_payload' => ['code' => '990906', 'name' => 'Token Header Tech'],
            'status' => DataSyncOutboundHistory::STATUS_PENDING,
        ]);

        $job = new SendOutboundApiJob($history->id);
        $job->handle(app(OutboundHttpService::class));

        // Verify Bearer Token is passed in HTTP Authorization header
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secure-api-token-12345')
                && $request->method() === 'POST'
                && $request->url() === 'https://api.external-system.com/api/technologies';
        });

        // Verify token is NOT exposed in the history record URL, payload, or error_message
        $history->refresh();
        $this->assertStringNotContainsString('secure-api-token-12345', $history->request_url);
        $this->assertStringNotContainsString('secure-api-token-12345', json_encode($history->request_payload));
        $this->assertStringNotContainsString('secure-api-token-12345', (string) $history->error_message);
    }
}
