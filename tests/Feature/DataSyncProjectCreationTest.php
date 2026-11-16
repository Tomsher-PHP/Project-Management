<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
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
use App\Services\DataSync\ProjectSyncService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncProjectCreationTest extends TestCase
{
    use RefreshDatabase;

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

        $result = app(ApiClientService::class)->createClient('Creation Test Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
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

    public function test_1_new_customer_creation(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $customerId = $response->json('data.customer.customer_id');
        $this->assertNotNull($customerId);
        $this->assertTrue($response->json('data.customer.created'));

        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'name' => 'ABC Technologies LLC',
            'email' => 'info@abctech.ae',
            'industry_id' => $this->testIndustry->id,
            'registered_country_id' => $this->testCountry->id,
            'sales_person_id' => $this->testSalesUser->id,
            'new_to_company' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_2_existing_customer_reuse(): void
    {
        $existingCustomer = Customer::create([
            'customer_code' => 'CUS10001',
            'name' => 'Existing Company LLC',
            'email' => 'existing@company.ae',
            'is_active' => true,
        ]);

        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS10001',
            ],
            'projects' => [
                ['name' => 'Follow-up Project'],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $this->assertSame($existingCustomer->id, $response->json('data.customer.customer_id'));
        $this->assertFalse($response->json('data.customer.created'));

        // Customer count remains 1
        $this->assertSame(1, Customer::count());

        $this->assertDatabaseHas('projects', [
            'name' => 'Follow-up Project',
            'customer_id' => $existingCustomer->id,
        ]);
    }

    public function test_3_customer_code_generation(): void
    {
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload([
                'customer' => ['name' => 'First Customer', 'external_code' => 'CRM-CUS-FIRST', 'external_id' => '101'],
                'projects' => [['external_code' => 'CRM-PRJ-FIRST', 'external_id' => '201']],
            ]))
            ->assertStatus(201);

        $code1 = $response1->json('data.customer.customer_code');

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload([
                'customer' => ['name' => 'Second Customer', 'external_code' => 'CRM-CUS-SECOND', 'external_id' => '102'],
                'projects' => [['external_code' => 'CRM-PRJ-SECOND', 'external_id' => '202']],
            ]))
            ->assertStatus(201);

        $code2 = $response2->json('data.customer.customer_code');

        $this->assertNotNull($code1);
        $this->assertNotNull($code2);
        $this->assertNotEquals($code1, $code2);
        $this->assertMatchesRegularExpression('/^CUS\d+$/i', $code1);
        $this->assertMatchesRegularExpression('/^CUS\d+$/i', $code2);
    }

    public function test_4_primary_contact_creation(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $customerId = $response->json('data.customer.customer_id');

        $this->assertDatabaseHas('customer_contacts', [
            'customer_id' => $customerId,
            'name' => 'Ahmed Mohammed',
            'email' => 'ahmed@abctech.ae',
            'mobile' => '+971501234567',
            'is_primary' => 1,
        ]);
    }

    public function test_5_secondary_contact_creation(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Lead Manager',
                        'email' => 'lead@abctech.ae',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Assistant Manager',
                        'email' => 'assistant@abctech.ae',
                        'mobile' => '+971509998877',
                        'is_primary' => false,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerId = $response->json('data.customer.customer_id');

        $this->assertDatabaseHas('customer_contacts', [
            'customer_id' => $customerId,
            'name' => 'Lead Manager',
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('customer_contacts', [
            'customer_id' => $customerId,
            'name' => 'Assistant Manager',
            'is_primary' => 0,
        ]);

        $this->assertSame(2, CustomerContact::where('customer_id', $customerId)->count());
    }

    public function test_6_no_duplicate_contacts_for_existing_customers(): void
    {
        $existingCustomer = Customer::create([
            'customer_code' => 'CUS10002',
            'name' => 'Static Customer LLC',
            'is_active' => true,
        ]);

        CustomerContact::create([
            'customer_id' => $existingCustomer->id,
            'name' => 'Existing Contact',
            'email' => 'contact@static.ae',
            'is_primary' => true,
        ]);

        $this->assertSame(1, CustomerContact::where('customer_id', $existingCustomer->id)->count());

        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS10002',
                'contacts' => [
                    [
                        'name' => 'Ignored New Contact',
                        'email' => 'ignored@static.ae',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                ['name' => 'Project For Existing Customer'],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        // Contact count remains 1 and existing contact is unmodified
        $this->assertSame(1, CustomerContact::where('customer_id', $existingCustomer->id)->count());
        $this->assertDatabaseMissing('customer_contacts', [
            'name' => 'Ignored New Contact',
        ]);
    }

    public function test_7_single_project_creation(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $this->assertNotNull($projectId);
        $this->assertTrue($response->json('data.projects.0.created'));

        $project = Project::find($projectId);
        $this->assertNotNull($project);
        $this->assertSame('Corporate Website Development', $project->name);
        $this->assertSame('agile', $project->project_flow);
        $this->assertSame('high', $project->priority);
        $this->assertSame($this->defaultStatus->id, $project->status_id);
        $this->assertSame($this->defaultStage->id, $project->project_stage_id);
        $this->assertSame(36000, $project->estimated_time_seconds); // 600 min * 60
        $this->assertMatchesRegularExpression('/^PRJ\d+$/i', $project->project_code);
    }

    public function test_8_multiple_project_creation(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'Module 1 - Auth'],
                ['name' => 'Module 2 - Billing'],
                ['name' => 'Module 3 - Reports'],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $this->assertCount(3, $response->json('data.projects'));
        $this->assertSame(3, Project::count());
    }

    public function test_9_all_projects_linked_to_the_correct_customer(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'Project A'],
                ['name' => 'Project B'],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerId = $response->json('data.customer.customer_id');

        $projects = Project::all();
        $this->assertCount(2, $projects);
        foreach ($projects as $project) {
            $this->assertSame($customerId, $project->customer_id);
        }
    }

    public function test_10_default_project_status_assignment(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'Project with Default Status', 'status_code' => null],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $project = Project::find($projectId);

        $this->assertSame($this->defaultStatus->id, $project->status_id);
    }

    public function test_11_default_project_stage_assignment(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'Project with Default Stage', 'stage_code' => null],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $project = Project::find($projectId);

        $this->assertSame($this->defaultStage->id, $project->project_stage_id);
    }

    public function test_12_valid_project_relationships(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'Project with Relations',
                    'category_codes' => [$this->testCategory->code],
                    'technology_codes' => [$this->testTechnology->code],
                    'service_type_codes' => [$this->testServiceType->code],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $project = Project::with(['technologies', 'serviceTypes'])->find($projectId);

        $this->assertSame([(int) $this->testCategory->id], $project->project_category_ids);
        $this->assertTrue($project->technologies->contains('id', $this->testTechnology->id));
        $this->assertTrue($project->serviceTypes->contains('id', $this->testServiceType->id));
    }

    public function test_13_invalid_optional_references_are_ignored(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'Project with Mixed Relations',
                    'category_codes' => [$this->testCategory->code, 'NONEXISTENT_CAT'],
                    'technology_codes' => [$this->testTechnology->code, 'FAKE_TECH'],
                    'service_type_codes' => [$this->testServiceType->code, 'GHOST_ST'],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $projectId = $response->json('data.projects.0.project_id');
        $project = Project::with(['technologies', 'serviceTypes'])->find($projectId);

        $this->assertSame([(int) $this->testCategory->id], $project->project_category_ids);
        $this->assertCount(1, $project->technologies);
        $this->assertCount(1, $project->serviceTypes);
    }

    public function test_14_invalid_customer_code_returns_422(): void
    {
        $payload = [
            'customer' => ['pms_customer_code' => 'CUS99999'],
            'projects' => [['name' => 'Blocked Project']],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.pms_customer_code']);

        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Project::count());
    }

    public function test_15_transaction_rollback_when_project_creation_fails(): void
    {
        $initialCustomers = Customer::count();
        $initialContacts = CustomerContact::count();
        $initialProjects = Project::count();

        // Bind a failing ProjectSyncService to simulate a database failure during project persistence
        $this->instance(ProjectSyncService::class, new class(app(\App\Services\ProjectServices::class)) extends ProjectSyncService {
            public function sync(array $resolvedPayload): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($resolvedPayload) {
                    $customer = Customer::create([
                        'customer_code' => Customer::generateCustomerCode(),
                        'name' => 'Temporary Rollback Customer',
                    ]);
                    CustomerContact::create([
                        'customer_id' => $customer->id,
                        'name' => 'Temporary Contact',
                        'is_primary' => true,
                    ]);

                    // Trigger unexpected error during project creation
                    throw new Exception('Simulated project creation database failure.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to process project data synchronization.',
            ]);

        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialContacts, CustomerContact::count());
        $this->assertSame($initialProjects, Project::count());
    }

    public function test_16_transaction_rollback_when_contact_creation_fails(): void
    {
        $initialCustomers = Customer::count();
        $initialContacts = CustomerContact::count();
        $initialProjects = Project::count();

        $this->instance(ProjectSyncService::class, new class(app(\App\Services\ProjectServices::class)) extends ProjectSyncService {
            public function sync(array $resolvedPayload): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($resolvedPayload) {
                    Customer::create([
                        'customer_code' => Customer::generateCustomerCode(),
                        'name' => 'Customer Before Contact Crash',
                    ]);

                    // Trigger unexpected error during contact creation
                    throw new Exception('Simulated contact creation database failure.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(500);

        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialContacts, CustomerContact::count());
        $this->assertSame($initialProjects, Project::count());
    }

    public function test_17_existing_customer_data_remains_unchanged(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS10003',
            'name' => 'Original Customer Name',
            'email' => 'original@customer.ae',
            'is_active' => true,
        ]);

        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS10003',
                'name' => 'Attempted Overwrite Name',
                'email' => 'hacked@customer.ae',
            ],
            'projects' => [
                ['name' => 'Unchanged Customer Project'],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $freshCustomer = $customer->fresh();
        $this->assertSame('Original Customer Name', $freshCustomer->name);
        $this->assertSame('original@customer.ae', $freshCustomer->email);
    }

    public function test_18_repeated_request_behavior(): void
    {
        $idempotencyKey = 'crm-sync-request-' . uniqid();
        $payload = $this->basePayload();

        // 1st request
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerCountAfterFirst = Customer::count();
        $projectCountAfterFirst = Project::count();

        // 2nd request with same idempotency key and payload
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $this->assertSame($response1->json('data'), $response2->json('data'));

        // No new records created on retry
        $this->assertSame($customerCountAfterFirst, Customer::count());
        $this->assertSame($projectCountAfterFirst, Project::count());
    }

    public function test_19_authorization_remains_unchanged(): void
    {
        // Missing Authorization header
        $this->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(401);

        // Missing projects.write scope
        $readClient = app(ApiClientService::class)->createClient('Read Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $readClient['plain_text_key'])
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(403);
    }

    public function test_20_existing_api_regression_tests_pass(): void
    {
        $multiClient = app(ApiClientService::class)->createClient('Omni Client', [
            ApiClient::SCOPE_MEETINGS_READ,
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $key = $multiClient['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Swift'])
            ->assertStatus(201);
    }
}

