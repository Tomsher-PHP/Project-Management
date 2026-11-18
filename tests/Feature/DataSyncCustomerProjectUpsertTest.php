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
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTimeline;
use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use App\Services\ApiClientService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncCustomerProjectUpsertTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClient $apiClient;
    protected string $authorizedKey;
    protected ProjectStatus $defaultStatus;
    protected ProjectStatus $secondStatus;
    protected ProjectStage $defaultStage;
    protected ProjectStage $secondStage;
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

        $this->secondStatus = ProjectStatus::firstOrCreate(['code' => '100002'], [
            'name' => 'In Progress',
            'type' => 'open',
            'sort_order' => 2,
            'is_completed' => 0,
            'is_system' => 0,
            'is_default' => 0,
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

        $this->secondStage = ProjectStage::firstOrCreate(['code' => '200002'], [
            'name' => 'Development',
            'color' => '#334455',
            'sort_order' => 2,
            'is_system' => 0,
            'is_default' => 0,
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

        $result = app(ApiClientService::class)->createClient('Upsert Test Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->apiClient = $result['client'];
        $this->authorizedKey = $result['plain_text_key'];
    }

    /**
     * Helper to build valid base payload.
     */
    protected function basePayload(array $overrides = []): array
    {
        $base = [
            'customer' => [
                'external_code' => 'CRM-CUS-100',
                'external_id' => 'EXT-100',
                'name' => 'Acme Corporation LLC',
                'email' => 'contact@acme.ae',
                'industry_code' => $this->testIndustry->code,
                'country_code' => $this->testCountry->code,
                'sales_person_email' => $this->testSalesUser->email,
                'contacts' => [
                    [
                        'name' => 'John Doe',
                        'email' => 'john@acme.ae',
                        'mobile' => '+971501112233',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-100',
                    'external_id' => 'PRJ-EXT-100',
                    'name' => 'Acme ERP System',
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

    public function test_new_customer_and_project_are_created_with_201(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $response->assertJson([
            'success' => true,
            'data' => [
                'customer' => [
                    'external_code' => 'CRM-CUS-100',
                    'external_id' => 'EXT-100',
                    'name' => 'Acme Corporation LLC',
                    'operation' => 'created',
                    'created' => true,
                ],
                'projects' => [
                    [
                        'external_code' => 'CRM-PRJ-100',
                        'external_id' => 'PRJ-EXT-100',
                        'name' => 'Acme ERP System',
                        'operation' => 'created',
                        'created' => true,
                    ],
                ],
                'summary' => [
                    'customer_created' => 1,
                    'customer_updated' => 0,
                    'projects_created' => 1,
                    'projects_updated' => 0,
                ],
            ],
        ]);

        $this->assertArrayNotHasKey('customer_id', $response->json('data.customer'));
        $this->assertArrayNotHasKey('project_id', $response->json('data.projects.0'));

        $customerCode = $response->json('data.customer.customer_code');
        $projectCode = $response->json('data.projects.0.project_code');

        $this->assertNotNull($customerCode);
        $this->assertNotNull($projectCode);
        $this->assertMatchesRegularExpression('/^CUS\d+$/i', $customerCode);
        $this->assertMatchesRegularExpression('/^PRJ\d+$/i', $projectCode);
    }

    public function test_existing_customer_and_project_are_updated_with_200(): void
    {
        // 1. Initial create
        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $customerCode = $firstResponse->json('data.customer.customer_code');
        $projectCode = $firstResponse->json('data.projects.0.project_code');

        // 2. Second request updating customer name and project priority/status
        $updatePayload = $this->basePayload([
            'customer' => [
                'name' => 'Acme Corporation Updated',
                // Contacts omitted to test preservation of existing contacts
                'contacts' => [],
            ],
            'projects' => [
                [
                    'name' => 'Acme ERP System V2',
                    'priority' => 'urgent',
                    'status_code' => $this->secondStatus->code,
                ],
            ],
        ]);

        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $updatePayload)
            ->assertStatus(200);

        $secondResponse->assertJson([
            'success' => true,
            'data' => [
                'customer' => [
                    'customer_code' => $customerCode,
                    'name' => 'Acme Corporation Updated',
                    'operation' => 'updated',
                    'created' => false,
                ],
                'projects' => [
                    [
                        'project_code' => $projectCode,
                        'name' => 'Acme ERP System V2',
                        'operation' => 'updated',
                        'created' => false,
                    ],
                ],
                'summary' => [
                    'customer_created' => 0,
                    'customer_updated' => 1,
                    'projects_created' => 0,
                    'projects_updated' => 1,
                ],
            ],
        ]);

        // Verify database state
        $customer = Customer::where('customer_code', $customerCode)->first();
        $this->assertSame('Acme Corporation Updated', $customer->name);
        $this->assertSame('contact@acme.ae', $customer->email); // Preserved

        $project = Project::where('project_code', $projectCode)->first();
        $this->assertSame('Acme ERP System V2', $project->name);
        $this->assertSame('urgent', $project->priority);
        $this->assertSame($this->secondStatus->id, $project->status_id);
        $this->assertSame($this->defaultStage->id, $project->project_stage_id); // Preserved

        // Contacts are preserved
        $this->assertSame(1, $customer->contacts()->count());
        $this->assertSame('John Doe', $customer->contacts()->first()->name);

        // Status history created only for status change
        $this->assertSame(2, ProjectStatusHistory::where('project_id', $project->id)->count());
        // Stage history untouched because stage was omitted
        $this->assertSame(1, ProjectStageHistory::where('project_id', $project->id)->count());
        // Timeline not duplicated
        $this->assertSame(1, ProjectTimeline::where('project_id', $project->id)->count());
    }

    public function test_duplicate_contact_email_belonging_to_another_customer_becomes_null(): void
    {
        // 1. Existing contact in PMS belonging to Customer A
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS99991',
            'name' => 'Other Customer LLC',
            'is_active' => true,
        ]);
        CustomerContact::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Existing Contact Elsewhere',
            'email' => 'jisha@yopmail.com',
            'mobile' => '0501111111',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // 2. Incoming payload for a NEW customer with that exact duplicate email
        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-CUS-NEW',
                'external_id' => 'EXT-NEW',
                'name' => 'New Company - Fayiz',
                'contacts' => [
                    [
                        'name' => 'Jisha In New Company',
                        'email' => 'jisha@yopmail.com', // Duplicate!
                        'mobile' => '0568650838',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-2026-0041',
                    'external_id' => '41',
                    'name' => 'New test push from CRM',
                ],
            ],
        ]);

        // Must succeed with 201 without throwing unique constraint violation
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerCode = $response->json('data.customer.customer_code');
        $customer = Customer::where('customer_code', $customerCode)->first();
        $this->assertNotNull($customer);

        $contact = $customer->contacts()->first();
        $this->assertNotNull($contact);
        $this->assertSame('Jisha In New Company', $contact->name);
        $this->assertNull($contact->email); // Stored as NULL instead of failing
        $this->assertSame('0568650838', $contact->mobile);
        $this->assertTrue((bool) $contact->is_primary);

        // Warning included
        $this->assertNotEmpty($response->json('data.warnings') ?? $response->json('warnings') ?? []);
    }

    public function test_multiple_contacts_with_duplicate_emails_in_same_payload_handled(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Contact 1',
                        'email' => 'shared@batch.ae',
                        'mobile' => '0501000001',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Contact 2',
                        'email' => 'shared@batch.ae', // duplicate in same payload
                        'mobile' => '0501000002',
                        'is_primary' => false,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerCode = $response->json('data.customer.customer_code');
        $customer = Customer::where('customer_code', $customerCode)->first();
        $contacts = $customer->contacts()->orderBy('id')->get();

        $this->assertCount(2, $contacts);
        $this->assertSame('shared@batch.ae', $contacts[0]->email);
        $this->assertNull($contacts[1]->email); // Normalized to null
    }

    public function test_contact_alias_fields_normalized(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Alias Contact',
                        'email' => 'alias@corp.ae',
                        'mobile' => '0501234567',
                        'whatsapp_number' => '+971509998888',
                        'landline_number' => '043334444',
                        'is_primary' => true,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerCode = $response->json('data.customer.customer_code');
        $contact = Customer::where('customer_code', $customerCode)->first()->contacts()->first();

        $this->assertSame('+971509998888', $contact->whatsapp);
        $this->assertSame('043334444', $contact->landline);
    }

    public function test_mapped_project_belonging_to_another_customer_returns_409(): void
    {
        // 1. Client creates Customer A and Project A
        $payloadA = $this->basePayload([
            'customer' => [
                'external_code' => 'CUS-A',
                'external_id' => 'EXT-A',
                'name' => 'Customer A',
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-SHARED',
                    'external_id' => 'PRJ-SHARED-ID',
                    'name' => 'Project A',
                ],
            ],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payloadA)
            ->assertStatus(201);

        // 2. Client sends request for Customer B attempting to claim PRJ-SHARED
        $payloadB = $this->basePayload([
            'customer' => [
                'external_code' => 'CUS-B',
                'external_id' => 'EXT-B',
                'name' => 'Customer B',
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-SHARED',
                    'external_id' => 'PRJ-SHARED-ID',
                    'name' => 'Claim Project A',
                ],
            ],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payloadB)
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Mapped project belongs to a different customer.',
            ]);
    }

    public function test_customer_upsert_by_external_id_only(): void
    {
        $payload1 = $this->basePayload([
            'customer' => [
                'external_code' => null,
                'external_id' => 'EXT-ID-ONLY-999',
                'name' => 'Initial Name',
            ],
        ]);

        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload1)
            ->assertStatus(201);

        $customerCode = $res1->json('data.customer.customer_code');

        $payload2 = $this->basePayload([
            'customer' => [
                'external_code' => null,
                'external_id' => 'EXT-ID-ONLY-999',
                'name' => 'Updated Name By Ext ID',
                'contacts' => [],
            ],
        ]);

        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload2)
            ->assertStatus(200);

        $this->assertSame($customerCode, $res2->json('data.customer.customer_code'));
        $this->assertSame('Updated Name By Ext ID', Customer::where('customer_code', $customerCode)->first()->name);
    }

    public function test_customer_upsert_by_pms_customer_code(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS77701',
            'name' => 'PMS Code Customer',
            'is_active' => true,
        ]);

        $payload = $this->basePayload([
            'customer' => [
                'external_code' => 'CRM-LINK-CODE',
                'external_id' => 'CRM-LINK-ID',
                'pms_customer_code' => 'CUS77701',
                'name' => 'PMS Code Customer Updated',
                'contacts' => [],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201); // 201 because project is newly created

        $response->assertJson([
            'success' => true,
            'data' => [
                'customer' => [
                    'customer_code' => 'CUS77701',
                    'name' => 'PMS Code Customer Updated',
                    'operation' => 'updated',
                    'created' => false,
                ],
            ],
        ]);

        $this->assertSame('PMS Code Customer Updated', $customer->fresh()->name);

        // Mapping created
        $this->assertDatabaseHas('data_sync_entity_mappings', [
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-LINK-CODE',
            'external_id' => 'CRM-LINK-ID',
            'local_id' => $customer->id,
        ]);
    }

    public function test_mixed_projects_create_and_update_in_single_request(): void
    {
        // 1. Initial create with Project 1
        $initialPayload = $this->basePayload([
            'projects' => [
                [
                    'external_code' => 'PRJ-1',
                    'external_id' => '1',
                    'name' => 'Project 1 Original',
                ],
            ],
        ]);

        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $initialPayload)
            ->assertStatus(201);

        $prj1Code = $res1->json('data.projects.0.project_code');

        // 2. Second request updating Project 1 and creating Project 2
        $mixedPayload = $this->basePayload([
            'customer' => [
                'contacts' => [],
            ],
            'projects' => [
                [
                    'external_code' => 'PRJ-1',
                    'external_id' => '1',
                    'name' => 'Project 1 Updated',
                ],
                [
                    'external_code' => 'PRJ-2',
                    'external_id' => '2',
                    'name' => 'Project 2 Brand New',
                ],
            ],
        ]);

        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $mixedPayload)
            ->assertStatus(201); // 201 because project 2 is created

        $res2->assertJson([
            'success' => true,
            'data' => [
                'projects' => [
                    [
                        'external_code' => 'PRJ-1',
                        'project_code' => $prj1Code,
                        'name' => 'Project 1 Updated',
                        'operation' => 'updated',
                        'created' => false,
                    ],
                    [
                        'external_code' => 'PRJ-2',
                        'name' => 'Project 2 Brand New',
                        'operation' => 'created',
                        'created' => true,
                    ],
                ],
                'summary' => [
                    'customer_created' => 0,
                    'customer_updated' => 1,
                    'projects_created' => 1,
                    'projects_updated' => 1,
                ],
            ],
        ]);
    }

    public function test_empty_contact_email_string_accepted_as_null(): void
    {
        $payload = $this->basePayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'No Email Contact',
                        'email' => '', // blank string
                        'mobile' => '+971505556677',
                        'is_primary' => true,
                    ],
                ],
            ],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $customerCode = $response->json('data.customer.customer_code');
        $contact = Customer::where('customer_code', $customerCode)->first()->contacts()->first();

        $this->assertNull($contact->email);
        $this->assertSame('+971505556677', $contact->mobile);
    }

    public function test_primary_contact_switching_preserves_single_primary_per_customer(): void
    {
        // 1. Initial create with Contact 1 as primary
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $customerCode = $res1->json('data.customer.customer_code');
        $customer = Customer::where('customer_code', $customerCode)->first();
        $this->assertSame(1, $customer->contacts()->where('is_primary', true)->count());

        // 2. Update with Contact 2 as primary
        $updatePayload = $this->basePayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Second Primary Contact',
                        'email' => 'second@acme.ae',
                        'mobile' => '+971507778899',
                        'is_primary' => true,
                    ],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $updatePayload)
            ->assertStatus(200);

        // Verify exactly one primary contact remains
        $freshCustomer = $customer->fresh();
        $this->assertSame(2, $freshCustomer->contacts()->count());
        $this->assertSame(1, $freshCustomer->contacts()->where('is_primary', true)->count());

        $newPrimary = $freshCustomer->contacts()->where('is_primary', true)->first();
        $this->assertSame('Second Primary Contact', $newPrimary->name);
    }

    public function test_responses_never_expose_internal_database_ids(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(201);

        $data = $response->json('data');

        $this->assertArrayNotHasKey('id', $data['customer']);
        $this->assertArrayNotHasKey('customer_id', $data['customer']);

        foreach ($data['projects'] as $proj) {
            $this->assertArrayNotHasKey('id', $proj);
            $this->assertArrayNotHasKey('project_id', $proj);
        }
    }
}
