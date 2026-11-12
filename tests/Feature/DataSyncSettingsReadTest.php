<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Industry;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncSettingsReadTest extends TestCase
{
    use RefreshDatabase;

    protected string $readKey;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        $result = app(ApiClientService::class)->createClient('Read Sync Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->readKey = $result['plain_text_key'];
    }

    public function test_list_and_get_technologies(): void
    {
        $php = Technology::create([
            'name' => 'PHP',
            'code' => '111111',
            'sort_order' => 2,
            'is_system' => true,
            'is_active' => true,
        ]);

        $laravel = Technology::create([
            'name' => 'Laravel',
            'code' => '222222',
            'sort_order' => 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        $deleted = Technology::create([
            'name' => 'Deleted Tech',
            'code' => '333333',
            'sort_order' => 3,
            'is_system' => false,
            'is_active' => true,
        ]);
        $deleted->delete();

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Records retrieved successfully.')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.code', '222222')
            ->assertJsonPath('data.1.code', '111111');

        $this->assertSame(
            ['id', 'name', 'code', 'sort_order', 'is_system', 'is_active'],
            array_keys($listResponse->json('data.0'))
        );

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/technologies/{$php->code}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'id' => $php->id,
                    'name' => 'PHP',
                    'code' => '111111',
                    'sort_order' => 2,
                    'is_system' => true,
                    'is_active' => true,
                ],
            ]);

        // Soft-deleted record must return 404
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/technologies/{$deleted->code}")
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);
    }

    public function test_list_and_get_industries_resolves_parent_code_without_n_plus_one(): void
    {
        $parent = Industry::create([
            'name' => 'Information Technology',
            'code' => '743813',
            'parent_id' => null,
            'sort_order' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $child = Industry::create([
            'name' => 'Software Development',
            'code' => '640358',
            'parent_id' => $parent->id,
            'sort_order' => 2,
            'is_system' => false,
            'is_active' => true,
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/industries');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.code', '743813')
            ->assertJsonPath('data.0.parent_code', null)
            ->assertJsonPath('data.1.code', '640358')
            ->assertJsonPath('data.1.parent_code', '743813');

        $this->assertSame(
            ['id', 'name', 'code', 'parent_code', 'sort_order', 'is_system', 'is_active'],
            array_keys($listResponse->json('data.1'))
        );

        // Auth query + last_used_at update + count query + list query + eager-load parent query <= 6
        $this->assertLessThanOrEqual(6, $queryCount);

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/industries/{$child->code}")
            ->assertStatus(200)
            ->assertJsonPath('data.parent_code', '743813');
    }

    public function test_list_and_get_project_categories(): void
    {
        $category = ProjectCategory::create([
            'name' => 'Web Development',
            'code' => '445566',
            'sort_order' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/project_categories');

        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.code', '445566');

        $this->assertSame(
            ['id', 'name', 'code', 'sort_order', 'is_system', 'is_active'],
            array_keys($listResponse->json('data.0'))
        );

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/project_categories/{$category->code}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Web Development');
    }

    public function test_list_and_get_project_statuses(): void
    {
        $status = ProjectStatus::create([
            'name' => 'Project Confirmed',
            'code' => '839533',
            'color' => '#6c757d',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 1,
            'is_default' => true,
            'is_completed' => false,
            'is_system' => true,
            'is_active' => true,
        ]);

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/project_statuses');

        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.code', '839533');

        $this->assertSame(
            ['id', 'name', 'code', 'color', 'type', 'sort_order', 'is_default', 'is_completed', 'is_system', 'is_active'],
            array_keys($listResponse->json('data.0'))
        );

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/project_statuses/{$status->code}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'id' => $status->id,
                    'name' => 'Project Confirmed',
                    'code' => '839533',
                    'color' => '#6c757d',
                    'type' => 'open',
                    'sort_order' => 1,
                    'is_default' => true,
                    'is_completed' => false,
                    'is_system' => true,
                    'is_active' => true,
                ],
            ]);
    }

    public function test_search_filter_pagination_sorting_and_query_validation(): void
    {
        Technology::create(['name' => 'Alpha', 'code' => '100001', 'sort_order' => 3, 'is_active' => true]);
        Technology::create(['name' => 'Beta', 'code' => '100002', 'sort_order' => 1, 'is_active' => false]);
        Technology::create(['name' => 'Gamma', 'code' => '100003', 'sort_order' => 2, 'is_active' => true]);

        // Filter is_active=1
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?is_active=1')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.name', 'Gamma')
            ->assertJsonPath('data.1.name', 'Alpha');

        // Filter is_active=0
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?is_active=0')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Beta');

        // Search by name
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?search=Alph')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.code', '100001');

        // Search by code
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?search=100002')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Beta');

        // Pagination + Sorting
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?sort_by=name&sort_direction=desc&per_page=2&page=2')
            ->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('data.0.name', 'Alpha');

        // Invalid query params return 422
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies?per_page=500&sort_by=password&sort_direction=invalid')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['per_page', 'sort_by', 'sort_direction']]);
    }
}

