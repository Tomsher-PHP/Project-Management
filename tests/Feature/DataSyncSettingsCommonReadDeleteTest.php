<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\ProjectTechnology;
use App\Models\Technology;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncSettingsCommonReadDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClientService $clientService;
    protected string $readKey;
    protected string $writeKey;
    protected string $fullKey;
    protected string $meetingOnlyKey;
    protected string $projectOnlyKey;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        $this->clientService = app(ApiClientService::class);

        $readClient = $this->clientService->createClient('Settings Read Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);
        $this->readKey = $readClient['plain_text_key'];

        $writeClient = $this->clientService->createClient('Settings Write Client', [
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);
        $this->writeKey = $writeClient['plain_text_key'];

        $fullClient = $this->clientService->createClient('Settings Full Client', [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);
        $this->fullKey = $fullClient['plain_text_key'];

        $meetingClient = $this->clientService->createClient('Meetings Only Client', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);
        $this->meetingOnlyKey = $meetingClient['plain_text_key'];

        $projectClient = $this->clientService->createClient('Projects Only Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->projectOnlyKey = $projectClient['plain_text_key'];
    }

    // ==========================================
    // A. Common List API (Scenarios 1-14)
    // ==========================================

    /**
     * 1. List Technologies via common endpoint.
     */
    public function test_01_common_list_technologies(): void
    {
        Technology::create(['name' => 'Laravel', 'code' => '110001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Technology::create(['name' => 'React', 'code' => '110002', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Records retrieved successfully.')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.code', '110001')
            ->assertJsonPath('data.1.code', '110002');

        $this->assertSame(
            ['id', 'name', 'code', 'sort_order', 'is_system', 'is_active'],
            array_keys($response->json('data.0'))
        );
    }

    /**
     * 2. List Industries via common endpoint.
     */
    public function test_02_common_list_industries(): void
    {
        $parent = Industry::create(['name' => 'Tech Parent', 'code' => '210001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Industry::create(['name' => 'Tech Child', 'code' => '210002', 'parent_id' => $parent->id, 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=industries');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.code', '210001')
            ->assertJsonPath('data.0.parent_code', null)
            ->assertJsonPath('data.1.code', '210002')
            ->assertJsonPath('data.1.parent_code', '210001');

        $this->assertSame(
            ['id', 'name', 'code', 'parent_code', 'sort_order', 'is_system', 'is_active'],
            array_keys($response->json('data.0'))
        );
    }

    /**
     * 3. List Project Categories via common endpoint.
     */
    public function test_03_common_list_project_categories(): void
    {
        ProjectCategory::create(['name' => 'Web App', 'code' => '310001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=project_categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Web App')
            ->assertJsonPath('data.0.code', '310001');

        $this->assertSame(
            ['id', 'name', 'code', 'sort_order', 'is_system', 'is_active'],
            array_keys($response->json('data.0'))
        );
    }

    /**
     * 4. List Project Statuses via common endpoint.
     */
    public function test_04_common_list_project_statuses(): void
    {
        ProjectStatus::create([
            'name' => 'In Progress',
            'code' => '410001',
            'type' => ProjectStatus::TYPE_IN_PROGRESS,
            'color' => '#3B82F6',
            'sort_order' => 1,
            'is_default' => true,
            'is_completed' => false,
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=project_statuses');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.code', '410001')
            ->assertJsonPath('data.0.type', ProjectStatus::TYPE_IN_PROGRESS)
            ->assertJsonPath('data.0.color', '#3B82F6')
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.0.is_completed', false);

        $this->assertSame(
            ['id', 'name', 'code', 'color', 'type', 'sort_order', 'is_default', 'is_completed', 'is_system', 'is_active'],
            array_keys($response->json('data.0'))
        );
    }

    /**
     * 5. Default pagination works (default per_page = 50).
     */
    public function test_05_common_list_default_pagination(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            Technology::create([
                'name' => 'Tech ' . $i,
                'code' => sprintf('%06d', 100000 + $i),
                'sort_order' => $i,
                'is_system' => false,
                'is_active' => true,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 50)
            ->assertJsonPath('pagination.total', 55)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(50, $response->json('data'));
    }

    /**
     * 6. Custom pagination works.
     */
    public function test_06_common_list_custom_pagination(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Technology::create([
                'name' => 'Tech ' . $i,
                'code' => sprintf('%06d', 110000 + $i),
                'sort_order' => $i,
                'is_system' => false,
                'is_active' => true,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 25)
            ->assertJsonPath('pagination.last_page', 3);

        $this->assertCount(10, $response->json('data'));
        $this->assertSame('110011', $response->json('data.0.code'));
    }

    /**
     * 7. Maximum pagination limit enforced (max 100).
     */
    public function test_07_common_list_max_pagination_limit(): void
    {
        // per_page > 100 triggers 422 validation error
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        // per_page = 100 is valid
        $validResponse = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&per_page=100');

        $validResponse->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 100);
    }

    /**
     * 8. Search by name works.
     */
    public function test_08_common_list_search_by_name(): void
    {
        Technology::create(['name' => 'PHP Framework', 'code' => '120001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Technology::create(['name' => 'Python Django', 'code' => '120002', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&search=Python');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Python Django')
            ->assertJsonPath('data.0.code', '120002');
    }

    /**
     * 9. Search by code works.
     */
    public function test_09_common_list_search_by_code(): void
    {
        Technology::create(['name' => 'Vue.js', 'code' => '130001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Technology::create(['name' => 'Angular', 'code' => '130002', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&search=130002');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Angular')
            ->assertJsonPath('data.0.code', '130002');
    }

    /**
     * 10. is_active filtering works.
     */
    public function test_10_common_list_is_active_filtering(): void
    {
        Technology::create(['name' => 'Active Tech', 'code' => '140001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Technology::create(['name' => 'Inactive Tech', 'code' => '140002', 'sort_order' => 2, 'is_system' => false, 'is_active' => false]);

        $activeResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&is_active=1');

        $activeResp->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.code', '140001');

        $inactiveResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&is_active=0');

        $inactiveResp->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.code', '140002');
    }

    /**
     * 11. Sorting works.
     */
    public function test_11_common_list_sorting(): void
    {
        Technology::create(['name' => 'Beta', 'code' => '150001', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);
        Technology::create(['name' => 'Alpha', 'code' => '150002', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);

        $nameAsc = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&sort_by=name&sort_direction=asc');

        $nameAsc->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Beta');

        $nameDesc = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies&sort_by=name&sort_direction=desc');

        $nameDesc->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Beta')
            ->assertJsonPath('data.1.name', 'Alpha');
    }

    /**
     * 12. Soft-deleted records are excluded.
     */
    public function test_12_common_list_soft_deleted_excluded(): void
    {
        $tech = Technology::create(['name' => 'Live Tech', 'code' => '160001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        $deleted = Technology::create(['name' => 'Deleted Tech', 'code' => '160002', 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);
        $deleted->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.code', '160001');
    }

    /**
     * 13. Missing module returns 422.
     */
    public function test_13_common_list_missing_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The module field is required.',
                    ],
                ],
            ]);
    }

    /**
     * 14. Invalid module returns 422.
     */
    public function test_14_common_list_invalid_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=unknown_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The selected module is invalid.',
                    ],
                ],
            ]);
    }

    // ==========================================
    // B. Common Get API (Scenarios 15-25)
    // ==========================================

    /**
     * 15. Get Technology by code.
     */
    public function test_15_common_get_technology_by_code(): void
    {
        Technology::create(['name' => 'Golang', 'code' => '210001', 'sort_order' => 5, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/210001?module=technologies');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'name' => 'Golang',
                    'code' => '210001',
                    'sort_order' => 5,
                    'is_system' => false,
                    'is_active' => true,
                ],
            ]);
    }

    /**
     * 16. Get Industry by code (with parent_code).
     */
    public function test_16_common_get_industry_by_code(): void
    {
        $parent = Industry::create(['name' => 'Finance', 'code' => '220001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Industry::create(['name' => 'Banking', 'code' => '220002', 'parent_id' => $parent->id, 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/220002?module=industries');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'name' => 'Banking',
                    'code' => '220002',
                    'parent_code' => '220001',
                ],
            ]);
    }

    /**
     * 17. Get Project Category by code.
     */
    public function test_17_common_get_project_category_by_code(): void
    {
        ProjectCategory::create(['name' => 'Mobile App', 'code' => '230001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/230001?module=project_categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'name' => 'Mobile App',
                    'code' => '230001',
                ],
            ]);
    }

    /**
     * 18. Get Project Status by code.
     */
    public function test_18_common_get_project_status_by_code(): void
    {
        ProjectStatus::create([
            'name' => 'Completed',
            'code' => '240001',
            'type' => ProjectStatus::TYPE_COMPLETED,
            'color' => '#10B981',
            'sort_order' => 4,
            'is_default' => false,
            'is_completed' => true,
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/240001?module=project_statuses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'name' => 'Completed',
                    'code' => '240001',
                    'color' => '#10B981',
                    'type' => ProjectStatus::TYPE_COMPLETED,
                    'is_default' => false,
                    'is_completed' => true,
                ],
            ]);
    }

    /**
     * 19. Missing record returns 404.
     */
    public function test_19_common_get_missing_record_returns_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/999999?module=technologies')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);
    }

    /**
     * 20. Soft-deleted record returns 404.
     */
    public function test_20_common_get_soft_deleted_record_returns_404(): void
    {
        $tech = Technology::create(['name' => 'Archived Tech', 'code' => '250001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        $tech->delete();

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/250001?module=technologies')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);
    }

    /**
     * 21. Same code in different modules resolves independently.
     */
    public function test_21_common_get_same_code_in_different_modules_resolves_independently(): void
    {
        $sharedCode = '777777';
        Technology::create(['name' => 'Shared Tech', 'code' => $sharedCode, 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        Industry::create(['name' => 'Shared Industry', 'code' => $sharedCode, 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);
        ProjectCategory::create(['name' => 'Shared Category', 'code' => $sharedCode, 'sort_order' => 3, 'is_system' => false, 'is_active' => true]);
        ProjectStatus::create([
            'name' => 'Shared Status',
            'code' => $sharedCode,
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 4,
            'is_default' => false,
            'is_completed' => false,
            'is_system' => false,
            'is_active' => true,
        ]);

        $techResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/{$sharedCode}?module=technologies");
        $techResp->assertStatus(200)
            ->assertJsonPath('data.name', 'Shared Tech');

        $indResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/{$sharedCode}?module=industries");
        $indResp->assertStatus(200)
            ->assertJsonPath('data.name', 'Shared Industry');

        $catResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/{$sharedCode}?module=project_categories");
        $catResp->assertStatus(200)
            ->assertJsonPath('data.name', 'Shared Category');

        $statusResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/{$sharedCode}?module=project_statuses");
        $statusResp->assertStatus(200)
            ->assertJsonPath('data.name', 'Shared Status');
    }

    /**
     * 22. Missing module returns 422 for Get.
     */
    public function test_22_common_get_missing_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/123456')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The module field is required.',
                    ],
                ],
            ]);
    }

    /**
     * 23. Invalid module returns 422 for Get.
     */
    public function test_23_common_get_invalid_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/123456?module=bad_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The selected module is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * 24. Industry parent_code is correctly returned (null vs populated).
     */
    public function test_24_common_get_industry_parent_code_returned(): void
    {
        $parent = Industry::create(['name' => 'Root Industry', 'code' => '260001', 'sort_order' => 1, 'is_system' => false, 'is_active' => true]);
        $child = Industry::create(['name' => 'Sub Industry', 'code' => '260002', 'parent_id' => $parent->id, 'sort_order' => 2, 'is_system' => false, 'is_active' => true]);

        $parentResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/260001?module=industries');
        $parentResp->assertStatus(200)
            ->assertJsonPath('data.parent_code', null);

        $childResp = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/260002?module=industries');
        $childResp->assertStatus(200)
            ->assertJsonPath('data.parent_code', '260001');
    }

    /**
     * 25. Project status module-specific fields are correctly returned.
     */
    public function test_25_common_get_project_status_fields_returned(): void
    {
        ProjectStatus::create([
            'name' => 'Archived Status',
            'code' => '270001',
            'type' => ProjectStatus::TYPE_ARCHIVED,
            'color' => '#F59E0B',
            'sort_order' => 3,
            'is_default' => false,
            'is_completed' => false,
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/270001?module=project_statuses');

        $response->assertStatus(200)
            ->assertJsonPath('data.code', '270001')
            ->assertJsonPath('data.type', ProjectStatus::TYPE_ARCHIVED)
            ->assertJsonPath('data.color', '#F59E0B')
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.is_completed', false);
    }

    // ==========================================
    // C. Common Delete API (Scenarios 26-37)
    // ==========================================

    /**
     * 26. Delete Technology via common endpoint.
     */
    public function test_26_common_delete_technology(): void
    {
        $tech = Technology::create(['name' => 'Delete Tech', 'code' => '310001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/310001?module=technologies');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('technologies', ['id' => $tech->id]);
    }

    /**
     * 27. Delete Industry via common endpoint.
     */
    public function test_27_common_delete_industry(): void
    {
        $ind = Industry::create(['name' => 'Delete Ind', 'code' => '320001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/320001?module=industries');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('industries', ['id' => $ind->id]);
    }

    /**
     * 28. Delete Project Category via common endpoint.
     */
    public function test_28_common_delete_project_category(): void
    {
        $cat = ProjectCategory::create(['name' => 'Delete Cat', 'code' => '330001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/330001?module=project_categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('project_categories', ['id' => $cat->id]);
    }

    /**
     * 29. Delete Project Status via common endpoint.
     */
    public function test_29_common_delete_project_status(): void
    {
        $status = ProjectStatus::create([
            'name' => 'Delete Status',
            'code' => '340001',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 1,
            'is_default' => false,
            'is_system' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/340001?module=project_statuses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('project_statuses', ['id' => $status->id]);
    }

    /**
     * 30. System records cannot be deleted (HTTP 403).
     */
    public function test_30_common_delete_system_records_protected(): void
    {
        $tech = Technology::create(['name' => 'System Tech', 'code' => '350001', 'sort_order' => 1, 'is_system' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/350001?module=technologies');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'System records cannot be deleted.',
            ]);

        $this->assertDatabaseHas('technologies', ['id' => $tech->id, 'deleted_at' => null]);
    }

    /**
     * 31. Referenced records cannot be deleted (HTTP 409).
     */
    public function test_31_common_delete_referenced_records_protected(): void
    {
        $tech = Technology::create(['name' => 'Referenced Tech', 'code' => '360001', 'sort_order' => 1, 'is_system' => false]);
        $category = ProjectCategory::create(['name' => 'Cat', 'code' => '360002', 'sort_order' => 1, 'is_system' => false]);
        $status = ProjectStatus::create(['name' => 'Stat', 'code' => '360003', 'type' => ProjectStatus::TYPE_OPEN, 'sort_order' => 1, 'is_default' => false, 'is_system' => false]);

        $customer = Customer::create([
            'customer_code' => 'CUST-0099',
            'name' => 'Ref Customer',
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-0099',
            'name' => 'Ref Project',
            'customer_id' => $customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
            'status_id' => $status->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'project_category_ids' => [$category->id],
        ]);

        ProjectTechnology::create([
            'project_id' => $project->id,
            'technology_id' => $tech->id,
        ]);

        // Attempt deleting referenced tech -> 409
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/360001?module=technologies');

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'This record is currently in use and cannot be deleted.',
            ]);

        // Attempt deleting referenced category -> 409
        $catResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/360002?module=project_categories');

        $catResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'This record is currently in use and cannot be deleted.',
            ]);

        // Attempt deleting referenced status -> 409
        $statusResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/360003?module=project_statuses');

        $statusResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'This record is currently in use and cannot be deleted.',
            ]);
    }

    /**
     * 32. Default project status cannot be deleted (HTTP 409).
     */
    public function test_32_common_delete_default_project_status_protected(): void
    {
        ProjectStatus::create([
            'name' => 'Default Status',
            'code' => '370001',
            'type' => ProjectStatus::TYPE_OPEN,
            'sort_order' => 1,
            'is_default' => true,
            'is_system' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/370001?module=project_statuses');

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Default project status cannot be deleted.',
            ]);
    }

    /**
     * 33. Soft deletion is verified in database.
     */
    public function test_33_common_delete_soft_deletion_verified(): void
    {
        $tech = Technology::create(['name' => 'Soft Del Tech', 'code' => '380001', 'sort_order' => 1, 'is_system' => false]);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/380001?module=technologies')
            ->assertStatus(200);

        $fresh = Technology::withTrashed()->find($tech->id);
        $this->assertNotNull($fresh);
        $this->assertNotNull($fresh->deleted_at);
        $this->assertSame('380001', $fresh->code);
    }

    /**
     * 34. Repeated deletion returns HTTP 200 "Record already deleted.".
     */
    public function test_34_common_delete_repeated_deletion_returns_200(): void
    {
        Technology::create(['name' => 'Repeat Del Tech', 'code' => '390001', 'sort_order' => 1, 'is_system' => false]);

        // First deletion
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/390001?module=technologies')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        // Second deletion
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/390001?module=technologies')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record already deleted.',
            ]);
    }

    /**
     * 35. Missing record returns 404 for Delete.
     */
    public function test_35_common_delete_missing_record_returns_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/999999?module=technologies')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);
    }

    /**
     * 36. Missing module returns 422 for Delete.
     */
    public function test_36_common_delete_missing_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/123456')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The module field is required.',
                    ],
                ],
            ]);
    }

    /**
     * 37. Invalid module returns 422 for Delete.
     */
    public function test_37_common_delete_invalid_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/123456?module=invalid_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'module' => [
                        'The selected module is invalid.',
                    ],
                ],
            ]);
    }

    // ==========================================
    // D. Security (Scenarios 38-44)
    // ==========================================

    /**
     * 38. Missing API key returns 401 across all common endpoints.
     */
    public function test_38_missing_api_key_returns_401(): void
    {
        $this->getJson('/api/data-sync/settings?module=technologies')->assertStatus(401);
        $this->getJson('/api/data-sync/settings/111111?module=technologies')->assertStatus(401);
        $this->deleteJson('/api/data-sync/settings/111111?module=technologies')->assertStatus(401);
    }

    /**
     * 39. Invalid API key returns 401 across all common endpoints.
     */
    public function test_39_invalid_api_key_returns_401(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid_secret_key')
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer invalid_secret_key')
            ->getJson('/api/data-sync/settings/111111?module=technologies')
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer invalid_secret_key')
            ->deleteJson('/api/data-sync/settings/111111?module=technologies')
            ->assertStatus(401);
    }

    /**
     * 40. Missing settings.read returns 403 for List.
     */
    public function test_40_missing_settings_read_returns_403_for_list(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * 41. Missing settings.read returns 403 for Get.
     */
    public function test_41_missing_settings_read_returns_403_for_get(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->getJson('/api/data-sync/settings/111111?module=technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * 42. Missing settings.write returns 403 for Delete.
     */
    public function test_42_missing_settings_write_returns_403_for_delete(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->deleteJson('/api/data-sync/settings/111111?module=technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * 43. Read-only API keys cannot delete records (HTTP 403).
     */
    public function test_43_read_only_key_cannot_delete(): void
    {
        Technology::create(['name' => 'ReadOnly Del Test', 'code' => '430001', 'sort_order' => 1, 'is_system' => false]);

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->deleteJson('/api/data-sync/settings/430001?module=technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * 44. Rate limiting remains active.
     */
    public function test_44_rate_limiting_remains_active(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
                ->getJson('/api/data-sync/settings?module=technologies');
        }

        $rateLimited = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings?module=technologies');

        $rateLimited->assertStatus(429);
    }

    // ==========================================
    // E. Regression (Scenarios 45-51)
    // ==========================================

    /**
     * 45. Common Sync API remains functional.
     */
    public function test_45_common_sync_remains_functional(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'name' => 'Sync Regress Tech',
                'code' => '450001',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'technologies',
                    'code' => '450001',
                    'name' => 'Sync Regress Tech',
                    'operation' => 'created',
                ],
            ]);
    }

    /**
     * 46. Existing module-specific List API works.
     */
    public function test_46_existing_module_specific_list_works(): void
    {
        Technology::create(['name' => 'Legacy List Tech', 'code' => '460001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Records retrieved successfully.',
            ])
            ->assertJsonPath('data.0.code', '460001');
    }

    /**
     * 47. Existing module-specific Get API works.
     */
    public function test_47_existing_module_specific_get_works(): void
    {
        Technology::create(['name' => 'Legacy Get Tech', 'code' => '470001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies/470001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'name' => 'Legacy Get Tech',
                    'code' => '470001',
                ],
            ]);
    }

    /**
     * 48. Existing module-specific Delete API works.
     */
    public function test_48_existing_module_specific_delete_works(): void
    {
        $tech = Technology::create(['name' => 'Legacy Del Tech', 'code' => '480001', 'sort_order' => 1, 'is_system' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/technologies/480001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('technologies', ['id' => $tech->id]);
    }

    /**
     * 49. Existing Settings POST/PUT APIs remain unchanged.
     */
    public function test_49_existing_settings_post_put_apis_remain_unchanged(): void
    {
        // POST /{module}
        $postResp = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/technologies', [
                'name' => 'Created via Legacy',
            ]);

        $postResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
            ]);

        $code = $postResp->json('data.code');

        // PUT /{module}/{code}
        $putResp = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson("/api/data-sync/settings/technologies/{$code}", [
                'name' => 'Updated via Legacy',
            ]);

        $putResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => [
                    'name' => 'Updated via Legacy',
                ],
            ]);
    }

    /**
     * 50. Project Data Sync API remains unaffected.
     */
    public function test_50_project_data_sync_api_remains_unaffected(): void
    {
        // Unauthenticated returns 401
        $this->postJson('/api/data-sync/projects')->assertStatus(401);

        // Settings-only key returns 403
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->postJson('/api/data-sync/projects')
            ->assertStatus(403);

        // Projects-authorized key returns 422 on empty payload
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->postJson('/api/data-sync/projects')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validation failed.']);

        // Legacy PUT integrations routes still respond
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->putJson('/api/integrations/projects/999999', ['name' => 'Test Project'])
            ->assertStatus(404);
    }

    /**
     * 51. Meeting API remains unaffected.
     */
    public function test_51_meeting_api_remains_unaffected(): void
    {
        // Unauthenticated returns 401
        $this->getJson('/api/meetings/active')->assertStatus(401);

        // Settings-only key returns 403
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(403);

        // Meetings-authorized key returns 200
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

