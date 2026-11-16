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
use App\Services\DataSync\ProjectReferenceResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DataSyncProjectReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected string $authorizedKey;
    protected ProjectStatus $defaultStatus;
    protected ProjectStage $defaultStage;
    protected Industry $testIndustry;
    protected Country $testCountry;
    protected User $testSalesUser;

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

        $result = app(ApiClientService::class)->createClient('Reference Test Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->authorizedKey = $result['plain_text_key'];
    }

    protected function basePayload(array $overrides = []): array
    {
        $base = [
            'customer' => [
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
                    'name' => 'Enterprise Portal',
                    'project_flow' => 'agile',
                    'priority' => 'high',
                    'status_code' => $this->defaultStatus->code,
                    'stage_code' => $this->defaultStage->code,
                    'sales_person_email' => $this->testSalesUser->email,
                ],
            ],
        ];

        return array_replace_recursive($base, $overrides);
    }

    public function test_existing_customer_code_resolves_successfully(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS10001',
            'name' => 'Existing Acme Corp',
            'email' => 'acme@test.ae',
            'sales_person_id' => $this->testSalesUser->id,
            'is_active' => true,
        ]);

        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS10001',
            ],
            'projects' => [
                ['name' => 'New Project for Existing Customer'],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve([
            'customer' => ['pms_customer_code' => 'CUS10001'],
            'projects' => [['name' => 'New Project']],
        ]);

        $this->assertSame($customer->id, $resolved['customer']['resolved']['customer_id']);
        $this->assertSame($customer->id, $resolved['customer']['resolved']['customer']->id);
    }

    public function test_invalid_customer_code_returns_422(): void
    {
        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS99999', // Nonexistent
            ],
            'projects' => [
                ['name' => 'Project with missing customer'],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['customer.pms_customer_code']);
    }

    public function test_new_customer_without_pms_code_remains_supported(): void
    {
        $payload = $this->basePayload();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertNull($resolved['customer']['resolved']['customer_id']);
        $this->assertNull($resolved['customer']['resolved']['customer']);
        $this->assertSame($this->testIndustry->id, $resolved['customer']['resolved']['industry_id']);
        $this->assertSame($this->testCountry->id, $resolved['customer']['resolved']['registered_country_id']);
        $this->assertSame($this->testSalesUser->id, $resolved['customer']['resolved']['sales_person_id']);
    }

    public function test_valid_project_status_resolves_successfully(): void
    {
        $customStatus = ProjectStatus::create([
            'name' => 'In Testing',
            'code' => '300001',
            'type' => 'in_progress',
            'sort_order' => 2,
            'is_default' => 0,
            'is_active' => 1,
        ]);

        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'status_code' => '300001',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($customStatus->id, $resolved['projects'][0]['resolved']['project_status_id']);
    }

    public function test_empty_status_uses_default(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'status_code' => null,
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($this->defaultStatus->id, $resolved['projects'][0]['resolved']['project_status_id']);
    }

    public function test_invalid_status_uses_default(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'status_code' => 'NONEXISTENT_OR_MALFORMED',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($this->defaultStatus->id, $resolved['projects'][0]['resolved']['project_status_id']);
    }

    public function test_valid_stage_resolves_successfully(): void
    {
        $customStage = ProjectStage::create([
            'name' => 'Development Stage',
            'code' => '400001',
            'color' => '#123456',
            'sort_order' => 2,
            'is_default' => 0,
            'is_active' => 1,
        ]);

        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'stage_code' => '400001',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($customStage->id, $resolved['projects'][0]['resolved']['project_stage_id']);
    }

    public function test_empty_stage_uses_default(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'stage_code' => null,
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($this->defaultStage->id, $resolved['projects'][0]['resolved']['project_stage_id']);
    }

    public function test_invalid_stage_uses_default(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'stage_code' => 'NONEXISTENT_STAGE_999',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame($this->defaultStage->id, $resolved['projects'][0]['resolved']['project_stage_id']);
    }

    public function test_missing_default_status_returns_appropriate_error(): void
    {
        // Force un-default all statuses
        ProjectStatus::query()->update(['is_default' => 0]);

        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'status_code' => null],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.status_code'])
            ->assertJsonFragment([
                'projects.0.status_code' => ['Default project status is not configured.'],
            ]);
    }

    public function test_missing_default_stage_returns_appropriate_error(): void
    {
        // Force un-default all stages
        ProjectStage::query()->update(['is_default' => 0]);

        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'stage_code' => null],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.stage_code'])
            ->assertJsonFragment([
                'projects.0.stage_code' => ['Default project stage is not configured.'],
            ]);
    }

    public function test_valid_technology_codes_resolve(): void
    {
        $t1 = Technology::create(['name' => 'PHP', 'code' => '500001', 'sort_order' => 1, 'is_active' => 1]);
        $t2 = Technology::create(['name' => 'Vue', 'code' => '500002', 'sort_order' => 2, 'is_active' => 1]);

        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'technology_codes' => ['500001', '500002']],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $resolvedTechIds = $resolved['projects'][0]['resolved']['technology_ids'];
        $this->assertCount(2, $resolvedTechIds);
        $this->assertContains($t1->id, $resolvedTechIds);
        $this->assertContains($t2->id, $resolvedTechIds);
    }

    public function test_invalid_technology_codes_are_ignored(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'technology_codes' => ['INVALID_1', '999999']],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame([], $resolved['projects'][0]['resolved']['technology_ids']);
    }

    public function test_mixed_valid_and_invalid_technology_codes_retain_valid_records(): void
    {
        $t1 = Technology::create(['name' => 'Laravel', 'code' => '500003', 'sort_order' => 3, 'is_active' => 1]);

        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'technology_codes' => ['500003', 'FAKE_CODE', '000000']],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame([(int) $t1->id], $resolved['projects'][0]['resolved']['technology_ids']);
    }

    public function test_empty_technology_array_resolves_to_empty(): void
    {
        $payload = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'technology_codes' => []],
            ],
        ]);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame([], $resolved['projects'][0]['resolved']['technology_ids']);
    }

    public function test_invalid_category_and_service_type_codes_are_ignored(): void
    {
        $cat = ProjectCategory::create(['name' => 'Web App', 'code' => '600001', 'sort_order' => 1, 'is_active' => 1]);
        $group = ServiceGroup::firstOrCreate(['name' => 'General Services'], ['code' => '800001', 'sort_order' => 1, 'is_active' => 1]);
        $st = ServiceType::create(['service_group_id' => $group->id, 'name' => 'Consulting', 'code' => '700001', 'sort_order' => 1, 'is_active' => 1]);

        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'P1',
                    'category_codes' => ['600001', 'NONEXISTENT_CAT'],
                    'service_type_codes' => ['700001', 'FAKE_ST'],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        $this->assertSame([(int) $cat->id], $resolved['projects'][0]['resolved']['category_ids']);
        $this->assertSame([(int) $st->id], $resolved['projects'][0]['resolved']['service_type_ids']);
    }

    public function test_invalid_essential_references_return_422(): void
    {
        // 1. Invalid industry code
        $payload1 = $this->basePayload([
            'customer' => ['industry_code' => '999999'],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.industry_code']);

        // 2. Invalid country code
        $payload2 = $this->basePayload([
            'customer' => ['country_code' => 'ZZ'],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.country_code']);

        // 3. Invalid customer sales person email
        $payload3 = $this->basePayload([
            'customer' => ['sales_person_email' => 'unknown_sales@test.ae'],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload3)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.sales_person_email']);

        // 4. Invalid project sales person email
        $payload4 = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'sales_person_email' => 'ghost_pm@test.ae'],
            ],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload4)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.sales_person_email']);

        // 5. Invalid parent project code
        $payload5 = $this->basePayload([
            'projects' => [
                ['name' => 'P1', 'parent_project_code' => 'PRJ99999'],
            ],
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload5)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.parent_project_code']);
    }

    public function test_multiple_projects_resolve_independently(): void
    {
        $statusA = ProjectStatus::create(['name' => 'Status A', 'code' => '300002', 'type' => 'open', 'sort_order' => 3, 'is_active' => 1]);
        $stageA = ProjectStage::create(['name' => 'Stage A', 'code' => '400002', 'color' => '#111111', 'sort_order' => 3, 'is_active' => 1]);
        $t1 = Technology::create(['name' => 'Tech 1', 'code' => '500004', 'sort_order' => 4, 'is_active' => 1]);
        $t2 = Technology::create(['name' => 'Tech 2', 'code' => '500005', 'sort_order' => 5, 'is_active' => 1]);

        $payload = $this->basePayload([
            'projects' => [
                [
                    'name' => 'Project Alpha',
                    'status_code' => '300002',
                    'stage_code' => null, // will default
                    'technology_codes' => ['500004'],
                ],
                [
                    'name' => 'Project Beta',
                    'status_code' => null, // will default
                    'stage_code' => '400002',
                    'technology_codes' => ['500005', 'UNKNOWN'],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);

        $resolver = app(ProjectReferenceResolver::class);
        $resolved = $resolver->resolve($payload);

        // Project Alpha assertions
        $this->assertSame($statusA->id, $resolved['projects'][0]['resolved']['project_status_id']);
        $this->assertSame($this->defaultStage->id, $resolved['projects'][0]['resolved']['project_stage_id']);
        $this->assertSame([(int) $t1->id], $resolved['projects'][0]['resolved']['technology_ids']);

        // Project Beta assertions
        $this->assertSame($this->defaultStatus->id, $resolved['projects'][1]['resolved']['project_status_id']);
        $this->assertSame($stageA->id, $resolved['projects'][1]['resolved']['project_stage_id']);
        $this->assertSame([(int) $t2->id], $resolved['projects'][1]['resolved']['technology_ids']);
    }

    public function test_api_authorization_remains_unchanged(): void
    {
        // Unauthenticated request
        $this->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(401);

        // Unauthorized scope request
        $meetingsOnly = app(ApiClientService::class)->createClient('Meetings App', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $meetingsOnly['plain_text_key'])
            ->postJson('/api/data-sync/projects', $this->basePayload())
            ->assertStatus(403);
    }

    public function test_no_customer_contact_or_project_records_are_created_on_reference_failure(): void
    {
        $customerCount = Customer::count();
        $contactCount = CustomerContact::count();
        $projectCount = Project::count();

        $invalidRefPayload = $this->basePayload([
            'customer' => ['industry_code' => '999999'],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $invalidRefPayload)
            ->assertStatus(422);

        $this->assertSame($customerCount, Customer::count());
        $this->assertSame($contactCount, CustomerContact::count());
        $this->assertSame($projectCount, Project::count());
    }
}

