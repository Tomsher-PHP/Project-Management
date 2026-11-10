<?php

namespace Tests\Feature;

use App\Models\ProjectCategory;
use App\Models\User;
use Database\Seeders\ProjectCategoryCodeSeeder;
use Database\Seeders\ProjectCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectCategoryCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_categories_table_has_code_column_after_name(): void
    {
        $columns = Schema::getColumnListing('project_categories');
        $nameIndex = array_search('name', $columns, true);
        $codeIndex = array_search('code', $columns, true);

        $this->assertNotFalse($nameIndex);
        $this->assertNotFalse($codeIndex);
        $this->assertSame($nameIndex + 1, $codeIndex);
    }

    public function test_generate_unique_code_matches_expected_format(): void
    {
        $codes = [];
        for ($i = 0; $i < 25; $i++) {
            $code = ProjectCategory::generateUniqueCode();
            $this->assertMatchesRegularExpression('/^pct-\d{6}$/', $code);
            $this->assertArrayNotHasKey($code, $codes);
            $codes[$code] = true;
        }
    }

    public function test_store_and_update_project_category_generates_and_preserves_unique_code(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Permission::findOrCreate('project_settings.create', 'web');
        Permission::findOrCreate('project_settings.edit', 'web');

        $storeResponse = $this->actingAs($user)
            ->postJson(route('settings.project-categories.store'), [
                'name' => 'Custom Category',
                'sort_order' => 10,
            ]);

        $storeResponse->assertOk()
            ->assertJsonPath('status', true);

        $createdId = $storeResponse->json('data.id');
        $createdCode = $storeResponse->json('data.code');

        $this->assertNotNull($createdCode);
        $this->assertMatchesRegularExpression('/^pct-\d{6}$/', $createdCode);

        $updateResponse = $this->actingAs($user)
            ->putJson(route('settings.project-categories.update', $createdId), [
                'name' => 'Updated Category',
                'sort_order' => 11,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', $createdCode)
            ->assertJsonPath('data.name', 'Updated Category');
    }

    public function test_seeder_fills_missing_codes_for_existing_including_soft_deleted_records(): void
    {
        $existingWithCode = ProjectCategory::create([
            'name' => 'Already Coded',
            'code' => 'abc-123456',
            'sort_order' => 1,
        ]);

        // Simulate legacy rows before code backfill (nullable column in altered tables)
        Schema::table('project_categories', function ($table) {
            $table->string('code', 20)->nullable()->change();
        });

        $missingActiveId = DB::table('project_categories')->insertGetId([
            'name' => 'Legacy Active',
            'code' => null,
            'sort_order' => 2,
            'is_system' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missingDeletedId = DB::table('project_categories')->insertGetId([
            'name' => 'Legacy Trashed',
            'code' => '',
            'sort_order' => 3,
            'is_system' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ]);

        $this->seed(ProjectCategoryCodeSeeder::class);

        $existingWithCode->refresh();
        $this->assertSame('abc-123456', $existingWithCode->code);

        $activeRow = ProjectCategory::find($missingActiveId);
        $this->assertNotNull($activeRow);
        $this->assertMatchesRegularExpression('/^pct-\d{6}$/', $activeRow->code);

        $deletedRow = ProjectCategory::withTrashed()->find($missingDeletedId);
        $this->assertNotNull($deletedRow);
        $this->assertMatchesRegularExpression('/^pct-\d{6}$/', $deletedRow->code);
        $this->assertNotSame($activeRow->code, $deletedRow->code);
    }

    public function test_project_category_seeder_assigns_codes(): void
    {
        $this->seed(ProjectCategorySeeder::class);

        $categories = ProjectCategory::all();
        $this->assertGreaterThan(0, $categories->count());

        foreach ($categories as $category) {
            $this->assertMatchesRegularExpression('/^pct-\d{6}$/', $category->code);
        }

        $this->assertSame($categories->count(), $categories->pluck('code')->unique()->count());
    }
}

