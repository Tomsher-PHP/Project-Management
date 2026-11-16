<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\DataSyncEntityMapping;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use App\Services\ApiClientService;
use App\Services\DataSync\ExternalEntityMappingService;
use App\Services\DataSync\ProjectSyncService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class DataSyncProjectMappingTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClient $apiClient;
    protected string $authorizedKey;
    protected ProjectStatus $defaultStatus;
    protected ProjectStage $defaultStage;
    protected Industry $testIndustry;
    protected Country $testCountry;
    protected User $testSalesUser;
    protected Technology $testTechnology;
    protected ProjectCategory $testCategory;
    protected ServiceType $testServiceType;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');
        Cache::flush();

        $this->defaultStatus = ProjectStatus::firstOrCreate(['is_default' => 1], [
            'name' => 'Default Planned',
            'code' => '100001',
            'type' => 'open',
            'sort_order' => 1,
            'is_completed' => 0,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        $this->defaultStage = ProjectStage::firstOrCreate(['is_default' => 1], [
            'name' => 'Default Planning',
            'code' => '200001',
            'color' => '#000000',
            'sort_order' => 1,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        $this->testIndustry = Industry::firstOrCreate(['code' => '743813'], [
            'name' => 'Information Technology',
        ]);

        $this->testCountry = Country::firstOrCreate(['code' => 'AE'], [
            'name' => 'United Arab Emirates',
        ]);

        $this->testSalesUser = User::firstOrCreate(['email' => 'sales@abctech.ae'], [
            'name' => 'Sarah Sales',
            'password' => bcrypt('secret123'),
        ]);

        $this->testTechnology = Technology::firstOrCreate(['code' => '500001'], [
            'name' => 'Laravel Framework',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->testCategory = ProjectCategory::firstOrCreate(['code' => '600001'], [
            'name' => 'Web Application',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $group = ServiceGroup::firstOrCreate(['name' => 'Development Services'], [
            'code' => '800001',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->testServiceType = ServiceType::firstOrCreate(['code' => '700001'], [
            'service_group_id' => $group->id,
            'name' => 'Custom Development',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $result = app(ApiClientService::class)->createClient('Mapping Test Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->apiClient = $result['client'];
        $this->authorizedKey = $result['plain_text_key'];
    }

    protected function basePayload(array $overrides = []): array
    {
        $base = [
            'customer' => [
                'external_code' => 'CRM-CUS-1025',
                'external_id' => '125',
                'name' => 'ABC Technologies LLC',
                'email' => 'info@abctech.ae',
                'industry_code' => $this->testIndustry->code,
                'country_code' => $this->testCountry->code,
                'sales_person_email' => $this->testSalesUser->email,
                'contacts' => [
                    [
                        'name' => 'Ahmed Mohammed',
                        'email' => 'ahmed@abctech.ae',
                        'mobile' => '+971501234567',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-1001',
                    'external_id' => '201',
                    'name' => 'Corporate Website Development',
                    'project_flow' => 'agile',
                    'priority' => 'high',
                    'status_code' => $this->defaultStatus->code,
                    'stage_code' => $this->defaultStage->code,
                    'start_date' => '2026-12-01',
                    'end_date' => '2027-02-28',
                    'estimated_time_minutes' => 600,
                    'sales_person_email' => $this->testSalesUser->email,
                ],
            ],
        ];

        return array_replace_recursive($base, $overrides);
    }

    public function test_1_new_customer_mapping_creation(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-NEW-1',
                'external_id' => 'EXT-CUS-NEW-1',
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerId = $response->json('data.customer.customer_id');
        $this->assertNotNull($customerId);
        $this->assertTrue($response->json('data.customer.created'));

        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-NEW-1',
            'external_id' => 'EXT-CUS-NEW-1',
            'local_id' => $customerId,
        ]);
    }

    public function test_2_existing_customer_mapping_resolution(): void
    {
        // First sync creates customer and mapping
        $firstPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-REUSE',
                'external_id' => 'EXT-CUS-REUSE',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-INIT',
                    'external_id' => '201',
                ],
            ],
        ]);

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $firstPayload)
            ->assertStatus(201);

        $customerId = $firstResponse->json('data.customer.customer_id');

        // Second sync with same customer external code but a different project
        $secondPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-REUSE',
                'external_id' => 'EXT-CUS-REUSE',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-NEW-FOR-CUSTOMER',
                    'external_id' => '202',
                ],
            ],
        ]);

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $secondPayload)
            ->assertStatus(201);

        $this->assertSame($customerId, $secondResponse->json('data.customer.customer_id'));
        $this->assertFalse($secondResponse->json('data.customer.created'));
        $this->assertSame(1, Customer::where('name', 'ABC Technologies LLC')->count());
    }

    public function test_3_new_project_mapping_creation(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-MAP-1',
                    'external_id' => 'PRJ-EXT-MAP-1',
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $this->assertNotNull($projectId);
        $this->assertTrue($response->json('data.projects.0.created'));

        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-PRJ-MAP-1',
            'external_id' => 'PRJ-EXT-MAP-1',
            'local_id' => $projectId,
        ]);
    }

    public function test_4_existing_project_mapping_resolution(): void
    {
        // First sync creates customer and project
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-PRJ-REUSE',
                'external_id' => 'CUS-PRJ-REUSE',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-REUSE',
                    'external_id' => 'PRJ-REUSE-1',
                ],
            ],
        ]);

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $firstResponse->json('data.projects.0.project_id');

        // Repeated sync should resolve the mapped project and not duplicate it
        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(200);

        $this->assertSame($projectId, $secondResponse->json('data.projects.0.project_id'));
        $this->assertFalse($secondResponse->json('data.projects.0.created'));
        $this->assertSame(1, Project::count());
    }

    public function test_5_repeated_crm_request_does_not_duplicate_records(): void
    {
        $payload = $this->basePayload();

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $firstCustomerId = $firstResponse->json('data.customer.customer_id');
        $firstProjectId = $firstResponse->json('data.projects.0.project_id');

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(200);

        $this->assertSame($firstCustomerId, $secondResponse->json('data.customer.customer_id'));
        $this->assertFalse($secondResponse->json('data.customer.created'));
        $this->assertSame($firstProjectId, $secondResponse->json('data.projects.0.project_id'));
        $this->assertFalse($secondResponse->json('data.projects.0.created'));

        $this->assertSame(1, Customer::count());
        $this->assertSame(1, CustomerContact::count());
        $this->assertSame(1, Project::count());
        $this->assertSame(2, DataSyncEntityMapping::count());
    }

    public function test_6_duplicate_prevention_after_cache_expiration(): void
    {
        $payload = $this->basePayload();

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->withHeader('Idempotency-Key', 'idemp-key-mapping-test-6')
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $firstProjectId = $firstResponse->json('data.projects.0.project_id');

        // Flush cache to simulate 10-minute cache expiration
        Cache::flush();

        // Send same payload again with different or same idempotency key
        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->withHeader('Idempotency-Key', 'idemp-key-mapping-test-6-new')
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(200);

        $this->assertSame($firstProjectId, $secondResponse->json('data.projects.0.project_id'));
        $this->assertFalse($secondResponse->json('data.customer.created'));
        $this->assertFalse($secondResponse->json('data.projects.0.created'));

        $this->assertSame(1, Customer::count());
        $this->assertSame(1, Project::count());
    }

    public function test_7_mixed_existing_and_new_projects(): void
    {
        // 1. Initial sync with Project A
        $initialPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-MIXED',
                'external_id' => 'CUS-MIXED',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-A',
                    'external_id' => 'PRJ-A',
                    'name' => 'Project Alpha',
                ],
            ],
        ]);

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $initialPayload)
            ->assertStatus(201);

        $projectAId = $firstResponse->json('data.projects.0.project_id');

        // 2. Second sync with Project A (existing), Project B (new), and Project C (new)
        $secondPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-MIXED',
                'external_id' => 'CUS-MIXED',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-A',
                    'external_id' => 'PRJ-A',
                    'name' => 'Project Alpha',
                ],
                [
                    'external_code' => 'CRM-PRJ-B',
                    'external_id' => 'PRJ-B',
                    'name' => 'Project Beta',
                ],
                [
                    'external_code' => 'CRM-PRJ-C',
                    'external_id' => 'PRJ-C',
                    'name' => 'Project Gamma',
                ],
            ],
        ]);

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $secondPayload)
            ->assertStatus(201);

        $projects = $secondResponse->json('data.projects');
        $this->assertCount(3, $projects);

        // Project A is reused
        $this->assertSame($projectAId, $projects[0]['project_id']);
        $this->assertFalse($projects[0]['created']);
        $this->assertSame('CRM-PRJ-A', $projects[0]['external_code']);

        // Project B is newly created
        $this->assertNotSame($projectAId, $projects[1]['project_id']);
        $this->assertTrue($projects[1]['created']);
        $this->assertSame('CRM-PRJ-B', $projects[1]['external_code']);

        // Project C is newly created
        $this->assertNotSame($projectAId, $projects[2]['project_id']);
        $this->assertTrue($projects[2]['created']);
        $this->assertSame('CRM-PRJ-C', $projects[2]['external_code']);

        $this->assertSame(3, Project::count());
    }

    public function test_8_mapping_lookup_by_external_code(): void
    {
        $firstPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-CODE-ONLY',
                'external_id' => null,
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-CODE-ONLY',
                    'external_id' => null,
                ],
            ],
        ]);

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $firstPayload)
            ->assertStatus(201);

        $customerId = $firstResponse->json('data.customer.customer_id');
        $projectId = $firstResponse->json('data.projects.0.project_id');

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $firstPayload)
            ->assertStatus(200);

        $this->assertSame($customerId, $secondResponse->json('data.customer.customer_id'));
        $this->assertSame($projectId, $secondResponse->json('data.projects.0.project_id'));
        $this->assertFalse($secondResponse->json('data.customer.created'));
        $this->assertFalse($secondResponse->json('data.projects.0.created'));
    }

    public function test_9_mapping_lookup_by_external_id(): void
    {
        $firstPayload = $this->basePayload([
            'customer' => [
                'external_code' => null,
                'external_id' => 'EXT-CUS-ID-ONLY-99',
            ],
            'projects' => [
                [
                    'external_code' => null,
                    'external_id' => 'EXT-PRJ-ID-ONLY-99',
                ],
            ],
        ]);

        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $firstPayload)
            ->assertStatus(201);

        $customerId = $firstResponse->json('data.customer.customer_id');
        $projectId = $firstResponse->json('data.projects.0.project_id');

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $firstPayload)
            ->assertStatus(200);

        $this->assertSame($customerId, $secondResponse->json('data.customer.customer_id'));
        $this->assertSame($projectId, $secondResponse->json('data.projects.0.project_id'));
        $this->assertFalse($secondResponse->json('data.customer.created'));
        $this->assertFalse($secondResponse->json('data.projects.0.created'));
    }

    public function test_10_conflicting_external_identifiers_return_409(): void
    {
        // Customer 1
        $customer1 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Mapped Customer One',
        ]);
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-CONFLICT-CODE',
            'external_id' => null,
            'local_id' => $customer1->id,
        ]);

        // Customer 2
        $customer2 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Mapped Customer Two',
        ]);
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => null,
            'external_id' => 'EXT-CUS-CONFLICT-ID',
            'local_id' => $customer2->id,
        ]);

        // Request supplies both conflicting identifiers
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-CONFLICT-CODE',
                'external_id' => 'EXT-CUS-CONFLICT-ID',
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Conflicting external customer identifiers.',
            ]);
    }

    public function test_11_conflicting_pms_customer_code_returns_409(): void
    {
        // Create Customer 1 mapped to external code
        $customer1 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Original Customer',
        ]);
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-CODE-MATCH',
            'external_id' => null,
            'local_id' => $customer1->id,
        ]);

        // Create Customer 2
        $customer2 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Different Customer',
        ]);

        // Request has external code mapped to Customer 1, but specifies Customer 2's code
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-CODE-MATCH',
                'pms_customer_code' => $customer2->customer_code,
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Conflicting PMS customer code.',
            ]);
    }

    public function test_12_different_api_clients_maintain_independent_mappings(): void
    {
        // Client A sends request
        $payloadA = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-SHARED-CODE',
                'external_id' => 'SHARED-ID',
                'name' => 'Client A Customer',
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-SHARED-CODE',
                    'external_id' => 'PRJ-SHARED-ID',
                    'name' => 'Client A Project',
                ],
            ],
        ]);

        $responseA = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payloadA)
            ->assertStatus(201);

        $customerAId = $responseA->json('data.customer.customer_id');
        $projectAId = $responseA->json('data.projects.0.project_id');

        // Create Client B
        $resultB = app(ApiClientService::class)->createClient('Mapping Test Client B', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $clientB = $resultB['client'];
        $keyB = $resultB['plain_text_key'];

        // Client B sends request with identical external identifiers
        $payloadB = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-SHARED-CODE',
                'external_id' => 'SHARED-ID',
                'name' => 'Client B Customer',
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-SHARED-CODE',
                    'external_id' => 'PRJ-SHARED-ID',
                    'name' => 'Client B Project',
                ],
            ],
        ]);

        $responseB = $this->withHeader('Authorization', 'Bearer ' . $keyB)
            ->postJson('/api/data-sync/projects', $payloadB)
            ->assertStatus(201);

        $customerBId = $responseB->json('data.customer.customer_id');
        $projectBId = $responseB->json('data.projects.0.project_id');

        // Mappings must be independent
        $this->assertNotSame($customerAId, $customerBId);
        $this->assertNotSame($projectAId, $projectBId);

        $this->assertSame(2, Customer::count());
        $this->assertSame(2, Project::count());

        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'api_client_id' => $this->apiClient->id,
            'external_code' => 'CRM-SHARED-CODE',
            'local_id' => $customerAId,
        ]);

        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'api_client_id' => $clientB->id,
            'external_code' => 'CRM-SHARED-CODE',
            'local_id' => $customerBId,
        ]);
    }

    public function test_13_missing_external_identifiers_preserve_existing_creation_behavior(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => null,
                'external_id' => null,
            ],
            'projects' => [
                [
                    'external_code' => null,
                    'external_id' => null,
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $this->assertTrue($response->json('data.customer.created'));
        $this->assertTrue($response->json('data.projects.0.created'));

        $this->assertSame(0, DataSyncEntityMapping::count());
        $this->assertSame(1, Customer::count());
        $this->assertSame(1, Project::count());
    }

    public function test_14_transaction_rollback_includes_mapping_records(): void
    {
        $initialMappings = DataSyncEntityMapping::count();
        $initialCustomers = Customer::count();
        $initialProjects = Project::count();

        $this->instance(ProjectSyncService::class, new class(
            app(\App\Services\ProjectServices::class),
            app(ExternalEntityMappingService::class)
        ) extends ProjectSyncService {
            public function sync(array $resolvedPayload): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($resolvedPayload) {
                    $customer = Customer::create([
                        'customer_code' => Customer::generateCustomerCode(),
                        'name' => 'Failing Customer',
                    ]);

                    DataSyncEntityMapping::create([
                        'api_client_id' => 1,
                        'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                        'external_code' => 'FAIL-CUS-1',
                        'local_id' => $customer->id,
                    ]);

                    throw new Exception('Simulated crash during transaction.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(500);

        $this->assertSame($initialMappings, DataSyncEntityMapping::count());
        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialProjects, Project::count());
    }

    public function test_15_concurrent_requests_cannot_create_duplicate_mappings(): void
    {
        $service = app(ExternalEntityMappingService::class);

        // Pre-create mapping
        $service->createMapping(
            $this->apiClient->id,
            DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'CONCURRENT-CUS-CODE',
            null,
            10
        );

        // Attempt duplicate mapping creation
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Duplicate external identifier mapping for customer.');

        $service->createMapping(
            $this->apiClient->id,
            DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'CONCURRENT-CUS-CODE',
            null,
            20
        );
    }

    public function test_16_existing_customers_remain_unchanged(): void
    {
        $initialPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-IMMUTABLE',
                'name' => 'Original Customer Name LLC',
                'email' => 'original@abctech.ae',
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $initialPayload)
            ->assertStatus(201);

        $customer = Customer::where('name', 'Original Customer Name LLC')->firstOrFail();

        // Second request with changed details should NOT overwrite existing customer
        $secondPayload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-IMMUTABLE',
                'name' => 'Attempted Changed Customer Name',
                'email' => 'changed@abctech.ae',
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $secondPayload)
            ->assertStatus(200);

        $customer->refresh();
        $this->assertSame('Original Customer Name LLC', $customer->name);
        $this->assertSame('original@abctech.ae', $customer->email);
    }

    public function test_17_existing_projects_remain_unchanged(): void
    {
        $initialPayload = $this->basePayload([
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-IMMUTABLE',
                    'name' => 'Original Project Name',
                    'priority' => 'low',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $initialPayload)
            ->assertStatus(201);

        $project = Project::where('name', 'Original Project Name')->firstOrFail();
        $this->assertSame('low', $project->priority);

        // Second request with changed details should NOT overwrite existing project
        $secondPayload = $this->basePayload([
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-IMMUTABLE',
                    'name' => 'Attempted Changed Project Name',
                    'priority' => 'urgent',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $secondPayload)
            ->assertStatus(200);

        $project->refresh();
        $this->assertSame('Original Project Name', $project->name);
        $this->assertSame('low', $project->priority);
    }

    public function test_18_mapping_conflicts_do_not_create_partial_data(): void
    {
        // Customer 1 and Project 1 created
        $payload1 = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-OWNER-1',
                'external_id' => 'EXT-OWNER-1',
                'name' => 'Customer One',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-SHARED',
                    'external_id' => 'PRJ-SHARED',
                    'name' => 'Project Mapped to Customer 1',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload1)
            ->assertStatus(201);

        $initialCustomers = Customer::count();
        $initialProjects = Project::count();

        // Customer 2 attempts to claim Project 1 (belongs to Customer 1)
        $payload2 = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-OWNER-2',
                'external_id' => 'EXT-OWNER-2',
                'name' => 'Customer Two',
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-SHARED',
                    'external_id' => 'PRJ-SHARED',
                    'name' => 'Project Mapped to Customer 1',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload2)
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Mapped project belongs to a different customer.',
            ]);

        // Customer Two was rolled back and not persisted
        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialProjects, Project::count());
        $this->assertNull(Customer::where('name', 'Customer Two')->first());
    }

    public function test_19_existing_authentication_remains_unchanged(): void
    {
        $payload = $this->basePayload();

        // Unauthenticated request
        $this->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(401);

        // Insufficient scope
        $readClientResult = app(ApiClientService::class)->createClient('Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $readClientResult['plain_text_key'])
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_20_mapped_project_belonging_to_different_customer_returns_409(): void
    {
        $customer1 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'First Customer',
        ]);

        $project1 = Project::create([
            'project_code' => Project::generateProjectCode(),
            'name' => 'First Customer Project',
            'customer_id' => $customer1->id,
            'start_date' => '2026-12-01',
            'end_date' => '2027-02-28',
            'status_id' => $this->defaultStatus->id,
            'project_stage_id' => $this->defaultStage->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'EXT-PRJ-MISMATCH-1',
            'local_id' => $project1->id,
        ]);

        $customer2 = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Second Customer',
        ]);

        // Request sends pms_customer_code for Customer 2 with project mapped to Customer 1
        $payload = $this->basePayload([
            'customer' => [
                'pms_customer_code' => $customer2->customer_code,
            ],
            'projects' => [
                [
                    'external_code' => 'EXT-PRJ-MISMATCH-1',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Mapped project belongs to a different customer.',
            ]);
    }
}

