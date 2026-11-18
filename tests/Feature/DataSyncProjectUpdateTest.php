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
use App\Services\DataSync\ExternalEntityMappingService;
use App\Services\DataSync\ProjectUpdateService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncProjectUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClient $apiClient;
    protected string $authorizedKey;
    protected ProjectStatus $defaultStatus;
    protected ProjectStatus $customStatus;
    protected ProjectStage $defaultStage;
    protected ProjectStage $customStage;
    protected Industry $testIndustry;
    protected Country $testCountry;
    protected User $testSalesUser;
    protected User $secondSalesUser;
    protected Technology $tech1;
    protected Technology $tech2;
    protected ProjectCategory $cat1;
    protected ProjectCategory $cat2;
    protected ServiceType $type1;
    protected ServiceType $type2;
    protected Customer $customer;
    protected CustomerContact $contact;
    protected Project $project;

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

        $this->customStatus = ProjectStatus::firstOrCreate(['code' => '100002'], [
            'name' => 'In Progress Status',
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

        $this->customStage = ProjectStage::firstOrCreate(['code' => '200002'], [
            'name' => 'Execution Stage',
            'color' => '#112233',
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

        $this->secondSalesUser = User::firstOrCreate(['email' => 'john.sales@abctech.ae'], [
            'name' => 'John Sales',
            'password' => bcrypt('secret123'),
        ]);

        $this->tech1 = Technology::firstOrCreate(['code' => '500001'], [
            'name' => 'Laravel Framework',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->tech2 = Technology::firstOrCreate(['code' => '500002'], [
            'name' => 'Vue.js',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        $this->cat1 = ProjectCategory::firstOrCreate(['code' => '600001'], [
            'name' => 'Web Application',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->cat2 = ProjectCategory::firstOrCreate(['code' => '600002'], [
            'name' => 'Mobile Application',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        $group = ServiceGroup::firstOrCreate(['name' => 'Development Services'], [
            'code' => '800001',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->type1 = ServiceType::firstOrCreate(['code' => '700001'], [
            'service_group_id' => $group->id,
            'name' => 'Custom Development',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->type2 = ServiceType::firstOrCreate(['code' => '700002'], [
            'service_group_id' => $group->id,
            'name' => 'UI/UX Design',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        // Customer & Contact
        $this->customer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Original Customer LLC',
            'email' => 'info@customer.ae',
            'industry_id' => $this->testIndustry->id,
            'registered_country_id' => $this->testCountry->id,
            'sales_person_id' => $this->testSalesUser->id,
            'is_active' => true,
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name' => 'Ahmed Contact',
            'email' => 'ahmed@customer.ae',
            'mobile' => '+971501112233',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Initial Project
        $this->project = Project::create([
            'project_code' => 'PRJ00001',
            'name' => 'Original Project Name',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'low',
            'status_id' => $this->defaultStatus->id,
            'project_stage_id' => $this->defaultStage->id,
            'start_date' => '2026-12-01',
            'end_date' => '2027-02-28',
            'customer_end_date' => '2027-03-31',
            'estimated_time_seconds' => 36000,
            'customer_estimate_seconds' => 36000,
            'default_task_estimate_seconds' => 1800,
            'domain' => 'example.com',
            'project_category_ids' => [$this->cat1->id],
            'sales_person_id' => $this->testSalesUser->id,
            'default_billable' => true,
            'is_active' => true,
        ]);

        $this->project->technologies()->sync([$this->tech1->id]);
        $this->project->serviceTypes()->sync([$this->type1->id]);

        ProjectStatusHistory::create([
            'project_id' => $this->project->id,
            'status_id' => $this->defaultStatus->id,
            'added_by' => null,
        ]);

        ProjectStageHistory::create([
            'project_id' => $this->project->id,
            'stage_id' => $this->defaultStage->id,
            'added_by' => null,
        ]);

        ProjectTimeline::create([
            'project_id' => $this->project->id,
            'name' => 'Original Timeline',
            'type' => 'original',
            'status' => ProjectTimeline::STATUS_ACTIVE,
            'start_date' => $this->project->start_date,
            'end_date' => $this->project->end_date,
            'customer_end_date' => $this->project->customer_end_date,
            'estimated_time_seconds' => $this->project->estimated_time_seconds,
            'customer_estimate_seconds' => $this->project->customer_estimate_seconds,
            'sort_order' => 1,
            'created_by' => null,
        ]);

        // API Client
        $result = app(ApiClientService::class)->createClient('Project Update Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->apiClient = $result['client'];
        $this->authorizedKey = $result['plain_text_key'];
    }

    public function test_1_existing_project_updates_successfully(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Updated Project Name via API',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Project updated successfully.',
                'data' => [
                    'project_id' => $this->project->id,
                    'project_code' => $this->project->project_code,
                    'updated' => true,
                ],
            ]);

        $this->assertSame('Updated Project Name via API', $this->project->fresh()->name);
    }

    public function test_2_invalid_pms_project_code_returns_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/PRJ999999', [
                'name' => 'Nonexistent Project',
            ])
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Project not found.',
            ]);
    }

    public function test_3_unauthorized_request_returns_401(): void
    {
        $this->putJson('/api/integrations/projects/' . $this->project->project_code, [
            'name' => 'Unauthorized Update',
        ])->assertStatus(401);
    }

    public function test_4_missing_projects_write_scope_returns_403(): void
    {
        $readResult = app(ApiClientService::class)->createClient('Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $readResult['plain_text_key'])
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Forbidden Update',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_5_project_name_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Brand New Name',
            ])
            ->assertStatus(200);

        $this->assertSame('Brand New Name', $this->project->fresh()->name);
    }

    public function test_6_priority_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'priority' => 'urgent',
            ])
            ->assertStatus(200);

        $this->assertSame('urgent', $this->project->fresh()->priority);
    }

    public function test_7_partial_update_preserves_unchanged_fields(): void
    {
        $originalDomain = $this->project->domain;
        $originalStartDate = $this->project->start_date->format('Y-m-d');
        $originalEndDate = $this->project->end_date->format('Y-m-d');
        $originalSalesPersonId = $this->project->sales_person_id;

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'priority' => 'high',
            ])
            ->assertStatus(200);

        $fresh = $this->project->fresh();
        $this->assertSame('high', $fresh->priority);
        $this->assertSame('Original Project Name', $fresh->name);
        $this->assertSame($originalDomain, $fresh->domain);
        $this->assertSame($originalStartDate, $fresh->start_date->format('Y-m-d'));
        $this->assertSame($originalEndDate, $fresh->end_date->format('Y-m-d'));
        $this->assertSame($originalSalesPersonId, $fresh->sales_person_id);
    }

    public function test_8_valid_status_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'status_code' => $this->customStatus->code,
            ])
            ->assertStatus(200);

        $this->assertSame($this->customStatus->id, $this->project->fresh()->status_id);
    }

    public function test_9_invalid_status_uses_default(): void
    {
        // First set to custom status
        $this->project->update(['status_id' => $this->customStatus->id]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'status_code' => 'NONEXISTENT_CODE_999',
            ])
            ->assertStatus(200);

        $this->assertSame($this->defaultStatus->id, $this->project->fresh()->status_id);
    }

    public function test_10_omitted_status_preserves_existing_status(): void
    {
        $this->project->update(['status_id' => $this->customStatus->id]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Name Update Only',
            ])
            ->assertStatus(200);

        $this->assertSame($this->customStatus->id, $this->project->fresh()->status_id);
    }

    public function test_11_valid_stage_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'stage_code' => $this->customStage->code,
            ])
            ->assertStatus(200);

        $this->assertSame($this->customStage->id, $this->project->fresh()->project_stage_id);
    }

    public function test_12_invalid_stage_uses_default(): void
    {
        // First set to custom stage
        $this->project->update(['project_stage_id' => $this->customStage->id]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'stage_code' => 'INVALID_STAGE_CODE_999',
            ])
            ->assertStatus(200);

        $this->assertSame($this->defaultStage->id, $this->project->fresh()->project_stage_id);
    }

    public function test_13_status_history_creation(): void
    {
        $initialHistoryCount = ProjectStatusHistory::where('project_id', $this->project->id)->count();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'status_code' => $this->customStatus->code,
            ])
            ->assertStatus(200);

        $this->assertSame($initialHistoryCount + 1, ProjectStatusHistory::where('project_id', $this->project->id)->count());

        $latestHistory = ProjectStatusHistory::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame($this->defaultStatus->id, $latestHistory->from_status_id);
        $this->assertSame($this->customStatus->id, $latestHistory->status_id);
    }

    public function test_14_stage_history_creation(): void
    {
        $initialHistoryCount = ProjectStageHistory::where('project_id', $this->project->id)->count();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'stage_code' => $this->customStage->code,
            ])
            ->assertStatus(200);

        $this->assertSame($initialHistoryCount + 1, ProjectStageHistory::where('project_id', $this->project->id)->count());

        $latestHistory = ProjectStageHistory::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame($this->defaultStage->id, $latestHistory->from_stage_id);
        $this->assertSame($this->customStage->id, $latestHistory->stage_id);
    }

    public function test_15_no_duplicate_history_for_unchanged_values(): void
    {
        $initialStatusHistoryCount = ProjectStatusHistory::where('project_id', $this->project->id)->count();
        $initialStageHistoryCount = ProjectStageHistory::where('project_id', $this->project->id)->count();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'status_code' => $this->defaultStatus->code,
                'stage_code' => $this->defaultStage->code,
            ])
            ->assertStatus(200);

        $this->assertFalse($response->json('data.updated'));
        $this->assertSame($initialStatusHistoryCount, ProjectStatusHistory::where('project_id', $this->project->id)->count());
        $this->assertSame($initialStageHistoryCount, ProjectStageHistory::where('project_id', $this->project->id)->count());
    }

    public function test_16_technology_relationship_synchronization(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'technology_codes' => [$this->tech2->code],
            ])
            ->assertStatus(200);

        $techIds = $this->project->fresh()->technologies->pluck('id')->all();
        $this->assertSame([$this->tech2->id], $techIds);
    }

    public function test_17_category_relationship_synchronization(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'category_codes' => [$this->cat2->code],
            ])
            ->assertStatus(200);

        $this->assertSame([$this->cat2->id], $this->project->fresh()->project_category_ids);
    }

    public function test_18_service_type_relationship_synchronization(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'service_type_codes' => [$this->type2->code],
            ])
            ->assertStatus(200);

        $stIds = $this->project->fresh()->serviceTypes->pluck('id')->all();
        $this->assertSame([$this->type2->id], $stIds);
    }

    public function test_19_empty_arrays_clear_supplied_relationships(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'technology_codes' => [],
                'service_type_codes' => [],
                'category_codes' => [],
            ])
            ->assertStatus(200);

        $fresh = $this->project->fresh();
        $this->assertEmpty($fresh->technologies);
        $this->assertEmpty($fresh->serviceTypes);
        $this->assertNull($fresh->project_category_ids);
    }

    public function test_20_omitted_relationship_fields_preserve_existing_values(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Name Change Only',
            ])
            ->assertStatus(200);

        $fresh = $this->project->fresh();
        $this->assertSame([$this->tech1->id], $fresh->technologies->pluck('id')->all());
        $this->assertSame([$this->type1->id], $fresh->serviceTypes->pluck('id')->all());
        $this->assertSame([$this->cat1->id], $fresh->project_category_ids);
    }

    public function test_21_invalid_salesperson_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'sales_person_email' => 'nobody@nonexistent.ae',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['sales_person_email']);
    }

    public function test_22_invalid_parent_project_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'parent_project_code' => 'PRJ999999',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['parent_project_code']);
    }

    public function test_23_circular_parent_project_relationship_rejected(): void
    {
        // Self-parenting
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'parent_project_code' => $this->project->project_code,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_project_code']);

        // Circular ancestry: Project 2 is child of Project 1, cannot make Project 2 parent of Project 1
        $project2 = Project::create([
            'project_code' => 'PRJ00002',
            'name' => 'Child Project',
            'customer_id' => $this->customer->id,
            'status_id' => $this->defaultStatus->id,
            'project_stage_id' => $this->defaultStage->id,
            'parent_project_id' => $this->project->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'parent_project_code' => $project2->project_code,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_project_code']);
    }

    public function test_24_date_validation(): void
    {
        // end_date before start_date
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'start_date' => '2027-01-01',
                'end_date' => '2026-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // customer_end_date before end_date
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'customer_end_date' => '2026-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_end_date']);
    }

    public function test_25_estimate_conversion(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'estimated_time_minutes' => 120,
                'customer_estimate_minutes' => 90,
                'default_task_estimate_seconds' => 3600,
            ])
            ->assertStatus(200);

        $fresh = $this->project->fresh();
        $this->assertSame(7200, $fresh->estimated_time_seconds);
        $this->assertSame(5400, $fresh->customer_estimate_seconds);
        $this->assertSame(3600, $fresh->default_task_estimate_seconds);
    }

    public function test_26_external_mapping_conflict_returns_409(): void
    {
        // Map Project 2 to external code
        $project2 = Project::create([
            'project_code' => 'PRJ00002',
            'name' => 'Project Two',
            'customer_id' => $this->customer->id,
            'status_id' => $this->defaultStatus->id,
            'project_stage_id' => $this->defaultStage->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'EXT-CONFLICT-CODE',
            'local_id' => $project2->id,
        ]);

        // Attempt to update Project 1 claiming external code of Project 2
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'external_code' => 'EXT-CONFLICT-CODE',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'External mapping belongs to a different project.',
            ]);
    }

    public function test_27_existing_customer_remains_unchanged(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Project Update Check Customer',
            ])
            ->assertStatus(200);

        $freshCustomer = $this->customer->fresh();
        $this->assertSame('Original Customer LLC', $freshCustomer->name);
        $this->assertSame('info@customer.ae', $freshCustomer->email);
    }

    public function test_28_existing_contacts_remain_unchanged(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Project Update Check Contacts',
            ])
            ->assertStatus(200);

        $freshContact = $this->contact->fresh();
        $this->assertSame('Ahmed Contact', $freshContact->name);
        $this->assertSame('ahmed@customer.ae', $freshContact->email);
    }

    public function test_29_database_rollback_on_failure(): void
    {
        $originalName = $this->project->name;
        $originalPriority = $this->project->priority;

        $this->instance(ProjectUpdateService::class, new class(
            app(\App\Services\ProjectServices::class),
            app(ExternalEntityMappingService::class)
        ) extends ProjectUpdateService {
            public function update(string $projectCode, array $data, ?ApiClient $apiClient = null): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($projectCode, $data) {
                    $project = Project::where('project_code', $projectCode)->first();
                    $project->update(['name' => 'Name Before Rollback Crash']);

                    throw new Exception('Simulated project update database crash.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'name' => 'Failing Name',
            ])
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to process project data update.',
            ]);

        $fresh = $this->project->fresh();
        $this->assertSame($originalName, $fresh->name);
        $this->assertSame($originalPriority, $fresh->priority);
    }

    public function test_30_safe_mapping_enrichment(): void
    {
        // Project 1 previously mapped with only external_code
        $mapping = DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-ENRICH-CODE',
            'external_id' => null,
            'local_id' => $this->project->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/projects/' . $this->project->project_code, [
                'external_code' => 'CRM-ENRICH-CODE',
                'external_id' => 'CRM-ENRICH-ID-999',
            ])
            ->assertStatus(200);

        $mapping->refresh();
        $this->assertSame('CRM-ENRICH-CODE', $mapping->external_code);
        $this->assertSame('CRM-ENRICH-ID-999', $mapping->external_id);
    }
}

