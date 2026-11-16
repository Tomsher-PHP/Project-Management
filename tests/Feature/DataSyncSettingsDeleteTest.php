<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTechnology;
use App\Models\Technology;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncSettingsDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected string $writeKey;
    protected string $readKey;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        $service = app(ApiClientService::class);
        $this->writeKey = $service->createClient('Delete Write Client', [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ])['plain_text_key'];

        $this->readKey = $service->createClient('Delete Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ])['plain_text_key'];
    }

    public function test_delete_unused_records_across_all_four_modules_and_idempotent_repeat(): void
    {
        $tech = Technology::create(['name' => 'Temp Tech', 'code' => '745123', 'sort_order' => 1, 'is_system' => false]);
        $ind = Industry::create(['name' => 'Temp Ind', 'code' => '845621', 'sort_order' => 1, 'is_system' => false]);
        $cat = ProjectCategory::create(['name' => 'Temp Cat', 'code' => '397711', 'sort_order' => 1, 'is_system' => false]);
        $status = ProjectStatus::create(['name' => 'Temp Status', 'code' => '839533', 'type' => ProjectStatus::TYPE_OPEN, 'sort_order' => 1, 'is_default' => false, 'is_system' => false]);

        foreach ([
            'technologies' => $tech,
            'industries' => $ind,
            'project_categories' => $cat,
            'project_statuses' => $status,
        ] as $module => $model) {
            // Read-only client cannot delete
            $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
                ->deleteJson("/api/data-sync/settings/{$module}/{$model->code}")
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Insufficient API permissions.']);

            // First delete succeeds (soft delete)
            $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
                ->deleteJson("/api/data-sync/settings/{$module}/{$model->code}")
                ->assertStatus(200)
                ->assertJson(['success' => true, 'message' => 'Record deleted successfully.']);

            $fresh = $model::withTrashed()->find($model->id);
            $this->assertNotNull($fresh->deleted_at);
            $this->assertSame($model->code, $fresh->code);

            // Excluded from GET APIs
            $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
                ->getJson("/api/data-sync/settings/{$module}/{$model->code}")
                ->assertStatus(404);

            // Repeated DELETE returns 200 "Record already deleted."
            $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
                ->deleteJson("/api/data-sync/settings/{$module}/{$model->code}")
                ->assertStatus(200)
                ->assertJson(['success' => true, 'message' => 'Record already deleted.']);
        }
    }

    public function test_invalid_module_and_unknown_code_return_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/invalid_module/123456')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Invalid Data Sync module.']);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/technologies/000000')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Record not found.']);
    }

    public function test_system_records_cannot_be_deleted_across_all_modules(): void
    {
        $tech = Technology::create(['name' => 'Sys Tech', 'code' => '111001', 'sort_order' => 1, 'is_system' => true]);
        $ind = Industry::create(['name' => 'Sys Ind', 'code' => '111002', 'sort_order' => 1, 'is_system' => true]);
        $cat = ProjectCategory::create(['name' => 'Sys Cat', 'code' => '111003', 'sort_order' => 1, 'is_system' => true]);
        $status = ProjectStatus::create(['name' => 'Sys Status', 'code' => '111004', 'type' => ProjectStatus::TYPE_OPEN, 'sort_order' => 1, 'is_system' => true]);

        foreach ([
            'technologies' => $tech,
            'industries' => $ind,
            'project_categories' => $cat,
            'project_statuses' => $status,
        ] as $module => $model) {
            $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
                ->deleteJson("/api/data-sync/settings/{$module}/{$model->code}")
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'System records cannot be deleted.']);

            $this->assertNull($model->fresh()->deleted_at);
        }
    }

    public function test_rejects_deletion_of_referenced_technology_industry_category_and_status(): void
    {
        $tech = Technology::create(['name' => 'Used Tech', 'code' => '222001', 'sort_order' => 1]);
        $indWithChild = Industry::create(['name' => 'Parent Ind', 'code' => '222002', 'sort_order' => 1]);
        Industry::create(['name' => 'Child Ind', 'code' => '222003', 'parent_id' => $indWithChild->id, 'sort_order' => 2]);

        $indWithCustomer = Industry::create(['name' => 'Cust Ind', 'code' => '222004', 'sort_order' => 3]);
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Test Customer',
            'industry_id' => $indWithCustomer->id,
        ]);

        $cat = ProjectCategory::create(['name' => 'Used Cat', 'code' => '222005', 'sort_order' => 1]);
        $statusInProject = ProjectStatus::create(['name' => 'Used Status', 'code' => '222006', 'type' => ProjectStatus::TYPE_OPEN, 'sort_order' => 1]);
        $statusInHistory = ProjectStatus::create(['name' => 'History Status', 'code' => '222007', 'type' => ProjectStatus::TYPE_IN_PROGRESS, 'sort_order' => 2]);
        $defaultStatus = ProjectStatus::create(['name' => 'Default Status', 'code' => '222008', 'type' => ProjectStatus::TYPE_OPEN, 'sort_order' => 3, 'is_default' => true]);

        $project = Project::create([
            'project_code' => 'PRJ-0001',
            'name' => 'Test Project',
            'customer_id' => $customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
            'status_id' => $statusInProject->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'project_category_ids' => [$cat->id],
        ]);

        ProjectTechnology::create([
            'project_id' => $project->id,
            'technology_id' => $tech->id,
        ]);

        ProjectStatusHistory::create([
            'project_id' => $project->id,
            'from_status_id' => $statusInHistory->id,
            'status_id' => $statusInProject->id,
        ]);

        // 1. Technology referenced by project
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/technologies/222001')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'message' => 'This record is currently in use and cannot be deleted.']);
        $this->assertTrue(ProjectTechnology::where('technology_id', $tech->id)->exists());

        // 2. Industry with child industry
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/industries/222002')
            ->assertStatus(409);

        // 3. Industry with customer
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/industries/222004')
            ->assertStatus(409);
        $this->assertSame($indWithCustomer->id, $customer->fresh()->industry_id);

        // 4. ProjectCategory referenced in projects.project_category_ids JSON
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/project_categories/222005')
            ->assertStatus(409);
        $this->assertSame([$cat->id], $project->fresh()->project_category_ids);

        // 5. ProjectStatus referenced by project
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/project_statuses/222006')
            ->assertStatus(409);

        // 6. ProjectStatus referenced by history
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/project_statuses/222007')
            ->assertStatus(409);

        // 7. Default ProjectStatus
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/project_statuses/222008')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'message' => 'Default project status cannot be deleted.']);
    }
}

