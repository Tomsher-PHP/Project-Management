<?php

namespace Tests\Feature;

use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\User;
use Database\Seeders\ProjectStageSeeder;
use Database\Seeders\ProjectStatusSeeder;
use Database\Seeders\ProjectStatusStageCodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectStatusStageCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $compiledViewsPath = sys_get_temp_dir() . '/pm_test_views';
        if (! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0777, true);
        }
        putenv("VIEW_COMPILED_PATH={$compiledViewsPath}");
        $_ENV['VIEW_COMPILED_PATH'] = $compiledViewsPath;
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledViewsPath;

        parent::setUp();
    }

    public function test_generate_unique_code_is_six_digit_numeric_for_status_and_stage(): void
    {
        $statusCodes = [];
        $stageCodes = [];

        for ($i = 0; $i < 20; $i++) {
            $statusCode = ProjectStatus::generateUniqueCode();
            $this->assertMatchesRegularExpression('/^\d{6}$/', $statusCode);
            $this->assertArrayNotHasKey($statusCode, $statusCodes);
            $statusCodes[$statusCode] = true;

            $stageCode = ProjectStage::generateUniqueCode();
            $this->assertMatchesRegularExpression('/^\d{6}$/', $stageCode);
            $this->assertArrayNotHasKey($stageCode, $stageCodes);
            $stageCodes[$stageCode] = true;
        }
    }

    public function test_store_and_update_project_status_auto_generates_and_preserves_six_digit_code(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Permission::findOrCreate('project_settings.view', 'web');
        Permission::findOrCreate('project_settings.create', 'web');
        Permission::findOrCreate('project_settings.edit', 'web');

        $storeResponse = $this->actingAs($user)
            ->postJson(route('settings.project-statuses.store'), [
                'name' => 'Reviewing',
                'code' => 'reviewing_manual',
                'color' => '#3B82F6',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 10,
                'is_default' => false,
            ]);

        $storeResponse->assertOk()->assertJsonPath('status', true);

        $createdId = $storeResponse->json('data.id');
        $createdCode = $storeResponse->json('data.code');

        $this->assertNotNull($createdCode);
        $this->assertNotSame('reviewing_manual', $createdCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $createdCode);

        $updateResponse = $this->actingAs($user)
            ->putJson(route('settings.project-statuses.update', $createdId), [
                'name' => 'Reviewing Updated',
                'code' => 'changed_manual',
                'color' => '#10B981',
                'type' => ProjectStatus::TYPE_COMPLETED,
                'sort_order' => 11,
                'is_default' => false,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', $createdCode)
            ->assertJsonPath('data.name', 'Reviewing Updated')
            ->assertJsonPath('data.is_completed', true);

        $indexResponse = $this->actingAs($user)->get(route('settings.project-statuses.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('name="code"', false);
    }

    public function test_store_and_update_project_stage_auto_generates_and_preserves_six_digit_code(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Permission::findOrCreate('project_settings.view', 'web');
        Permission::findOrCreate('project_settings.create', 'web');
        Permission::findOrCreate('project_settings.edit', 'web');

        $storeResponse = $this->actingAs($user)
            ->postJson(route('settings.project-stages.store'), [
                'name' => 'QA Signoff',
                'code' => 'qa_signoff_manual',
                'color' => '#22C55E',
                'sort_order' => 10,
                'is_default' => false,
            ]);

        $storeResponse->assertOk()->assertJsonPath('status', true);

        $createdId = $storeResponse->json('data.id');
        $createdCode = $storeResponse->json('data.code');

        $this->assertNotNull($createdCode);
        $this->assertNotSame('qa_signoff_manual', $createdCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $createdCode);

        $updateResponse = $this->actingAs($user)
            ->putJson(route('settings.project-stages.update', $createdId), [
                'name' => 'QA Signoff Updated',
                'code' => 'changed_stage_manual',
                'color' => '#F59E0B',
                'sort_order' => 11,
                'is_default' => false,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', $createdCode)
            ->assertJsonPath('data.name', 'QA Signoff Updated');

        $indexResponse = $this->actingAs($user)->get(route('settings.project-stages.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('name="code"', false);
    }

    public function test_seeder_converts_alphabet_codes_to_six_digit_codes_and_preserves_existing_numeric_codes(): void
    {
        $validStatus = ProjectStatus::create([
            'name' => 'Valid Numeric Status',
            'code' => '123456',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 1,
        ]);

        $alphaStatusId = DB::table('project_statuses')->insertGetId([
            'name' => 'Legacy Planned',
            'code' => 'planned',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 2,
            'is_default' => false,
            'is_completed' => false,
            'is_system' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trashedAlphaStatusId = DB::table('project_statuses')->insertGetId([
            'name' => 'Legacy Trashed Status',
            'code' => 'on_hold',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 3,
            'is_default' => false,
            'is_completed' => false,
            'is_system' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ]);

        $validStage = ProjectStage::create([
            'name' => 'Valid Numeric Stage',
            'code' => '654321',
            'sort_order' => 1,
        ]);

        $alphaStageId = DB::table('project_stages')->insertGetId([
            'name' => 'Legacy Planning',
            'code' => 'planning',
            'sort_order' => 2,
            'is_default' => false,
            'is_system' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trashedAlphaStageId = DB::table('project_stages')->insertGetId([
            'name' => 'Legacy Trashed Stage',
            'code' => 'deployment',
            'sort_order' => 3,
            'is_default' => false,
            'is_system' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ]);

        $this->seed(ProjectStatusStageCodeSeeder::class);

        $validStatus->refresh();
        $this->assertSame('123456', $validStatus->code);

        $convertedStatus = ProjectStatus::find($alphaStatusId);
        $this->assertNotNull($convertedStatus);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $convertedStatus->code);

        $convertedTrashedStatus = ProjectStatus::withTrashed()->find($trashedAlphaStatusId);
        $this->assertNotNull($convertedTrashedStatus);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $convertedTrashedStatus->code);
        $this->assertNotSame($convertedStatus->code, $convertedTrashedStatus->code);

        $validStage->refresh();
        $this->assertSame('654321', $validStage->code);

        $convertedStage = ProjectStage::find($alphaStageId);
        $this->assertNotNull($convertedStage);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $convertedStage->code);

        $convertedTrashedStage = ProjectStage::withTrashed()->find($trashedAlphaStageId);
        $this->assertNotNull($convertedTrashedStage);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $convertedTrashedStage->code);
        $this->assertNotSame($convertedStage->code, $convertedTrashedStage->code);
    }

    public function test_status_and_stage_seeders_assign_unique_six_digit_codes(): void
    {
        $this->seed(ProjectStatusSeeder::class);
        $this->seed(ProjectStageSeeder::class);

        $statuses = ProjectStatus::all();
        $this->assertGreaterThan(0, $statuses->count());
        foreach ($statuses as $status) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', $status->code);
        }
        $this->assertSame($statuses->count(), $statuses->pluck('code')->unique()->count());

        $stages = ProjectStage::all();
        $this->assertGreaterThan(0, $stages->count());
        foreach ($stages as $stage) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', $stage->code);
        }
        $this->assertSame($stages->count(), $stages->pluck('code')->unique()->count());
    }
}

