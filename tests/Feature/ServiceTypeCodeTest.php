<?php

namespace Tests\Feature;

use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ServiceTypeCodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceTypeCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_unique_code_uses_code_prefix_and_six_digits(): void
    {
        $pattern = '/^' . preg_quote(ServiceType::CODE_PREFIX, '/') . '-\d{6}$/';
        $codes = [];

        for ($i = 0; $i < 25; $i++) {
            $code = ServiceType::generateUniqueCode();
            $this->assertMatchesRegularExpression($pattern, $code);
            $this->assertArrayNotHasKey($code, $codes);
            $codes[$code] = true;
        }
    }

    public function test_store_and_update_service_type_generates_and_preserves_unique_code(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        Permission::findOrCreate('project_settings.create', 'web');
        Permission::findOrCreate('project_settings.edit', 'web');

        $group = ServiceGroup::firstOrCreate(
            ['code' => 'grp_test_code'],
            ['name' => 'Test Group', 'sort_order' => 1, 'is_active' => true]
        );

        $storeResponse = $this->actingAs($user)
            ->postJson(route('settings.service-types.store'), [
                'name' => 'Custom Service Type',
                'service_group_id' => $group->id,
                'color' => '#3B82F6',
                'sort_order' => 10,
            ]);

        $storeResponse->assertOk()
            ->assertJsonPath('status', true);

        $createdId = $storeResponse->json('data.id');
        $createdCode = $storeResponse->json('data.code');

        $this->assertNotNull($createdCode);
        $this->assertMatchesRegularExpression('/^pst-\d{6}$/', $createdCode);

        $updateResponse = $this->actingAs($user)
            ->putJson(route('settings.service-types.update', $createdId), [
                'name' => 'Updated Service Type',
                'service_group_id' => $group->id,
                'color' => '#10B981',
                'sort_order' => 11,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', $createdCode)
            ->assertJsonPath('data.name', 'Updated Service Type');
    }

    public function test_seeder_fills_and_normalizes_codes_for_existing_including_soft_deleted_records(): void
    {
        $group = ServiceGroup::firstOrCreate(
            ['code' => 'grp_test_seed'],
            ['name' => 'Seed Group', 'sort_order' => 1, 'is_active' => true]
        );

        $alreadyPrefixed = ServiceType::create([
            'service_group_id' => $group->id,
            'name' => 'Already Prefixed',
            'code' => 'pst-123456',
            'sort_order' => 1,
        ]);

        $legacyNumericId = DB::table('service_types')->insertGetId([
            'service_group_id' => $group->id,
            'name' => 'Legacy Numeric',
            'code' => '654321',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $legacyEmptyDeletedId = DB::table('service_types')->insertGetId([
            'service_group_id' => $group->id,
            'name' => 'Legacy Empty Deleted',
            'code' => '',
            'sort_order' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ]);

        $this->seed(ServiceTypeCodeSeeder::class);

        $alreadyPrefixed->refresh();
        $this->assertSame('pst-123456', $alreadyPrefixed->code);

        $legacyNumeric = ServiceType::find($legacyNumericId);
        $this->assertNotNull($legacyNumeric);
        $this->assertMatchesRegularExpression('/^pst-\d{6}$/', $legacyNumeric->code);

        $legacyDeleted = ServiceType::withTrashed()->find($legacyEmptyDeletedId);
        $this->assertNotNull($legacyDeleted);
        $this->assertMatchesRegularExpression('/^pst-\d{6}$/', $legacyDeleted->code);
        $this->assertNotSame($legacyNumeric->code, $legacyDeleted->code);
    }
}

