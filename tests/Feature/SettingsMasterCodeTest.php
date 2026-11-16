<?php

namespace Tests\Feature;

use App\Models\BroadcastCategory;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Industry;
use App\Models\MeetingLocation;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use App\Models\Technology;
use App\Models\User;
use Database\Seeders\BroadcastCategorySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\IndustrySeeder;
use Database\Seeders\MeetingSettingsSeeder;
use Database\Seeders\SettingsMasterCodeSeeder;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsMasterCodeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{table: string, model: class-string}>
     */
    protected function masterDefinitions(): array
    {
        return [
            ['table' => 'departments', 'model' => Department::class],
            ['table' => 'designations', 'model' => Designation::class],
            ['table' => 'technologies', 'model' => Technology::class],
            ['table' => 'industries', 'model' => Industry::class],
            ['table' => 'meeting_types', 'model' => MeetingType::class],
            ['table' => 'meeting_locations', 'model' => MeetingLocation::class],
            ['table' => 'meeting_tags', 'model' => MeetingTag::class],
            ['table' => 'broadcast_categories', 'model' => BroadcastCategory::class],
        ];
    }

    public function test_all_settings_master_tables_have_code_column_after_name(): void
    {
        foreach ($this->masterDefinitions() as $definition) {
            $columns = Schema::getColumnListing($definition['table']);
            $nameIndex = array_search('name', $columns, true);
            $codeIndex = array_search('code', $columns, true);

            $this->assertNotFalse($nameIndex, "{$definition['table']} missing name column");
            $this->assertNotFalse($codeIndex, "{$definition['table']} missing code column");
            $this->assertSame($nameIndex + 1, $codeIndex, "{$definition['table']} code column is not after name");
        }
    }

    public function test_generate_unique_code_matches_six_digit_numeric_format_for_all_models(): void
    {
        foreach ($this->masterDefinitions() as $definition) {
            $modelClass = $definition['model'];
            $codes = [];

            for ($i = 0; $i < 15; $i++) {
                $code = $modelClass::generateUniqueCode();
                $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
                $this->assertArrayNotHasKey($code, $codes);
                $codes[$code] = true;
            }
        }
    }

    public function test_crud_store_and_update_generates_and_preserves_unique_code_for_all_masters(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        foreach ([
            'department.create',
            'department.edit',
            'designation.create',
            'designation.edit',
            'technology.create',
            'technology.edit',
            'industry.create',
            'industry.edit',
            'meeting_settings.create',
            'meeting_settings.edit',
            'broadcast_settings.create',
            'broadcast_settings.edit',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $cases = [
            [
                'storeRoute' => 'settings.departments.store',
                'updateRoute' => 'settings.departments.update',
                'storePayload' => ['name' => 'Custom Department', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Department', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.designations.store',
                'updateRoute' => 'settings.designations.update',
                'storePayload' => ['name' => 'Custom Designation', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Designation', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.technologies.store',
                'updateRoute' => 'settings.technologies.update',
                'storePayload' => ['name' => 'Custom Technology', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Technology', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.industries.store',
                'updateRoute' => 'settings.industries.update',
                'storePayload' => ['name' => 'Custom Industry', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Industry', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.meeting-types.store',
                'updateRoute' => 'settings.meeting-types.update',
                'storePayload' => ['name' => 'Custom Meeting Type', 'color' => '#3B82F6', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Meeting Type', 'color' => '#10B981', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.meeting-locations.store',
                'updateRoute' => 'settings.meeting-locations.update',
                'storePayload' => ['name' => 'Custom Meeting Location', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Meeting Location', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.meeting-tags.store',
                'updateRoute' => 'settings.meeting-tags.update',
                'storePayload' => ['name' => 'Custom Meeting Tag', 'color' => '#F59E0B', 'sort_order' => 10],
                'updatePayload' => ['name' => 'Updated Meeting Tag', 'color' => '#EF4444', 'sort_order' => 11],
            ],
            [
                'storeRoute' => 'settings.broadcast-categories.store',
                'updateRoute' => 'settings.broadcast-categories.update',
                'storePayload' => ['name' => 'Custom Broadcast Category', 'color' => '#3B82F6'],
                'updatePayload' => ['name' => 'Updated Broadcast Category', 'color' => '#10B981'],
            ],
        ];

        foreach ($cases as $case) {
            $storeResponse = $this->actingAs($user)
                ->postJson(route($case['storeRoute']), $case['storePayload']);

            $storeResponse->assertOk()->assertJsonPath('status', true);

            $createdId = $storeResponse->json('data.id');
            $createdCode = $storeResponse->json('data.code');

            $this->assertNotNull($createdCode);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $createdCode);

            $updateResponse = $this->actingAs($user)
                ->putJson(route($case['updateRoute'], $createdId), $case['updatePayload']);

            $updateResponse->assertOk()
                ->assertJsonPath('status', true)
                ->assertJsonPath('data.code', $createdCode)
                ->assertJsonPath('data.name', $case['updatePayload']['name']);
        }
    }

    public function test_common_seeder_fills_missing_codes_for_existing_and_soft_deleted_records(): void
    {
        $created = [];
        $missingActiveIds = [];
        $missingDeletedIds = [];

        foreach ($this->masterDefinitions() as $definition) {
            $modelClass = $definition['model'];
            $table = $definition['table'];

            $baseAttributes = [
                'name' => 'Already Coded ' . $table,
                'code' => '123456',
                'sort_order' => 1,
                'is_active' => true,
            ];

            if ($table === 'broadcast_categories') {
                $baseAttributes['color'] = '#3B82F6';
            }

            $created[$table] = $modelClass::create($baseAttributes);

            $activeInsert = [
                'name' => 'Legacy Active ' . $table,
                'code' => null,
                'sort_order' => 2,
                'is_system' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $deletedInsert = [
                'name' => 'Legacy Trashed ' . $table,
                'code' => '',
                'sort_order' => 3,
                'is_system' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ];

            if ($table === 'broadcast_categories') {
                $activeInsert['color'] = '#3B82F6';
                $deletedInsert['color'] = '#10B981';
            }

            $missingActiveIds[$table] = DB::table($table)->insertGetId($activeInsert);
            $missingDeletedIds[$table] = DB::table($table)->insertGetId($deletedInsert);
        }

        $this->seed(SettingsMasterCodeSeeder::class);

        foreach ($this->masterDefinitions() as $definition) {
            $modelClass = $definition['model'];
            $table = $definition['table'];

            $created[$table]->refresh();
            $this->assertSame('123456', $created[$table]->code);

            $activeRow = $modelClass::find($missingActiveIds[$table]);
            $this->assertNotNull($activeRow);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $activeRow->code);

            $deletedRow = $modelClass::withTrashed()->find($missingDeletedIds[$table]);
            $this->assertNotNull($deletedRow);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $deletedRow->code);
            $this->assertNotSame($activeRow->code, $deletedRow->code);
        }
    }

    public function test_master_seeders_assign_unique_six_digit_codes(): void
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(DesignationSeeder::class);
        $this->seed(TechnologySeeder::class);
        $this->seed(IndustrySeeder::class);
        $this->seed(MeetingSettingsSeeder::class);
        $this->seed(BroadcastCategorySeeder::class);

        foreach ($this->masterDefinitions() as $definition) {
            $modelClass = $definition['model'];
            $records = $modelClass::all();

            $this->assertGreaterThan(0, $records->count(), "{$definition['table']} should have seeded records");

            foreach ($records as $record) {
                $this->assertMatchesRegularExpression('/^\d{6}$/', $record->code);
            }

            $this->assertSame($records->count(), $records->pluck('code')->unique()->count());
        }
    }
}

