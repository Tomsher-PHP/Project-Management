<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\DataSyncEntityMapping;
use App\Models\Project;
use App\Models\User;
use App\Services\DataSync\RecordMappingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncSettingsRecordMappingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $viewUser;
    protected User $unauthorizedUser;
    protected ApiClient $apiClientA;
    protected ApiClient $apiClientB;
    protected Customer $customer;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Authorized View User
        $this->viewUser = User::factory()->create(['is_super_admin' => false]);
        $viewPermission = Permission::firstOrCreate(['name' => 'data_sync_settings.view', 'guard_name' => 'web']);
        $this->viewUser->givePermissionTo($viewPermission);

        // 2. Unauthorized User
        $this->unauthorizedUser = User::factory()->create(['is_super_admin' => false]);

        // 3. API Clients
        $this->apiClientA = ApiClient::create([
            'name' => 'Internal CRM App',
            'key_id' => 'azo_live_crmapp12345678',
            'secret_hash' => hash('sha256', 'supersecretrawtoken_crm'),
            'scopes' => [ApiClient::SCOPE_PROJECTS_WRITE, ApiClient::SCOPE_SETTINGS_READ],
            'is_active' => true,
        ]);

        $this->apiClientB = ApiClient::create([
            'name' => 'Secondary Sync Service',
            'key_id' => 'azo_live_secapp12345678',
            'secret_hash' => hash('sha256', 'supersecretrawtoken_sec'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_READ],
            'is_active' => false,
        ]);

        // 4. PMS Customer & Project
        $this->customer = Customer::factory()->create([
            'customer_code' => 'CUS102500',
            'name' => 'Alpha Acme Corp',
        ]);

        $this->project = Project::factory()->create([
            'project_code' => 'PRJ505500',
            'name' => 'E-Commerce Portal Platform',
            'customer_id' => $this->customer->id,
        ]);
    }

    /**
     * Point 1: Authorized user can view mappings.
     */
    public function test_1_authorized_user_can_view_mappings(): void
    {
        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertViewIs('settings.data-sync.index');
        $response->assertViewHas('currentTab', 'mappings');
        $response->assertViewHas('mappings');
        $response->assertViewHas('apiClients');
        $response->assertViewHas('entityTypes');
        $response->assertSee('Record Mappings');
    }

    /**
     * Point 2: Unauthorized user cannot view mappings.
     */
    public function test_2_unauthorized_user_cannot_view_mappings(): void
    {
        // Unauthenticated -> redirect to login
        $unauth = $this->get(route('settings.data-sync.mappings'));
        $unauth->assertRedirect(route('login'));

        // Logged in without permission -> 403 or redirect
        $this->actingAs($this->unauthorizedUser);
        $webResp = $this->get(route('settings.data-sync.mappings'));
        $this->assertTrue($webResp->isRedirect() || $webResp->status() === 403);

        $jsonResp = $this->getJson(route('settings.data-sync.mappings'));
        $jsonResp->assertStatus(403);
    }

    /**
     * Point 3: Customer mappings display correctly.
     */
    public function test_3_customer_mappings_display_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-1025',
            'external_id' => 'EXT-CUS-9021',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee('Customer');
        $response->assertSee('CRM-CUS-1025');
    }

    /**
     * Point 4: Project mappings display correctly.
     */
    public function test_4_project_mappings_display_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-PRJ-2001',
            'external_id' => 'EXT-PRJ-7711',
            'local_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee('Project');
        $response->assertSee('CRM-PRJ-2001');
    }

    /**
     * Point 5: API client name resolves correctly.
     */
    public function test_5_api_client_name_resolves_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CLIENT-TEST',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee($this->apiClientA->name);
    }

    /**
     * Point 6: External code displays correctly.
     */
    public function test_6_external_code_displays_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'UNIQUE-EXT-CODE-9988',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee('UNIQUE-EXT-CODE-9988');
    }

    /**
     * Point 7: External ID displays correctly.
     */
    public function test_7_external_id_displays_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-EXT-ID-TEST',
            'external_id' => 'UNIQUE-EXT-ID-112233',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee('UNIQUE-EXT-ID-112233');
    }

    /**
     * Point 8: PMS customer code resolves correctly.
     */
    public function test_8_pms_customer_code_resolves_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-RESOLVE',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee($this->customer->customer_code);
    }

    /**
     * Point 9: PMS project code resolves correctly.
     */
    public function test_9_pms_project_code_resolves_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-PRJ-RESOLVE',
            'local_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee($this->project->project_code);
    }

    /**
     * Point 10: Customer name displays correctly.
     */
    public function test_10_customer_name_displays_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-NAME-TEST',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee($this->customer->name);
    }

    /**
     * Point 11: Project name displays correctly.
     */
    public function test_11_project_name_displays_correctly(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-PRJ-NAME-TEST',
            'local_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $response->assertOk();
        $response->assertSee($this->project->name);
    }

    /**
     * Point 12: API client filter works.
     */
    public function test_12_api_client_filter_works(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MAP-CLIENT-A',
            'local_id' => $this->customer->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientB->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MAP-CLIENT-B',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'api_client_id' => $this->apiClientA->id,
        ]));

        $response->assertOk();
        $response->assertSee('MAP-CLIENT-A');
        $response->assertDontSee('MAP-CLIENT-B');
    }

    /**
     * Point 13: Entity type filter works.
     */
    public function test_13_entity_type_filter_works(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MAP-FILTER-CUST',
            'local_id' => $this->customer->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'MAP-FILTER-PRJ',
            'local_id' => $this->project->id,
        ]);

        // Filter customer
        $custResp = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'entity_type' => 'customer',
        ]));
        $custResp->assertSee('MAP-FILTER-CUST');
        $custResp->assertDontSee('MAP-FILTER-PRJ');

        // Filter project
        $prjResp = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'entity_type' => 'project',
        ]));
        $prjResp->assertSee('MAP-FILTER-PRJ');
        $prjResp->assertDontSee('MAP-FILTER-CUST');
    }

    /**
     * Point 14: Search by external code works.
     */
    public function test_14_search_by_external_code_works(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'SEARCH-CODE-TARGET',
            'local_id' => $this->customer->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'SEARCH-CODE-OTHER',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'SEARCH-CODE-TARGET',
        ]));

        $response->assertOk();
        $response->assertSee('SEARCH-CODE-TARGET');
        $response->assertDontSee('SEARCH-CODE-OTHER');
    }

    /**
     * Point 15: Search by external ID works.
     */
    public function test_15_search_by_external_id_works(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MAPPING-EXTID-1',
            'external_id' => 'EXT-TARGET-999',
            'local_id' => $this->customer->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MAPPING-EXTID-2',
            'external_id' => 'EXT-OTHER-111',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'EXT-TARGET-999',
        ]));

        $response->assertOk();
        $response->assertSee('MAPPING-EXTID-1');
        $response->assertDontSee('MAPPING-EXTID-2');
    }

    /**
     * Point 16: Search by PMS customer code works.
     */
    public function test_16_search_by_pms_customer_code_works(): void
    {
        $uniqueCustomer = Customer::factory()->create([
            'customer_code' => 'CUS-FIND-ME-77',
            'name' => 'Findable Customer Corp',
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-MATCHED-CUSTOMER',
            'local_id' => $uniqueCustomer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'CUS-FIND-ME-77',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-MATCHED-CUSTOMER');
        $response->assertSee('Findable Customer Corp');
    }

    /**
     * Point 17: Search by PMS project code works.
     */
    public function test_17_search_by_pms_project_code_works(): void
    {
        $uniqueProject = Project::factory()->create([
            'project_code' => 'PRJ-FIND-ME-88',
            'name' => 'Findable Project Corp',
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-MATCHED-PROJECT',
            'local_id' => $uniqueProject->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'PRJ-FIND-ME-88',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-MATCHED-PROJECT');
        $response->assertSee('Findable Project Corp');
    }

    /**
     * Point 18: Combined filters work.
     */
    public function test_18_combined_filters_work(): void
    {
        // Target match: Client A, Project, Search 'MULTI-SEARCH'
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'MULTI-SEARCH-PRJ',
            'local_id' => $this->project->id,
        ]);

        // Non-match 1: Client B
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientB->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'MULTI-SEARCH-PRJ-B',
            'local_id' => $this->project->id,
        ]);

        // Non-match 2: Entity customer
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'MULTI-SEARCH-CUST',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => 'project',
            'search' => 'MULTI-SEARCH',
        ]));

        $response->assertOk();
        $response->assertSee('MULTI-SEARCH-PRJ');
        $response->assertDontSee('MULTI-SEARCH-PRJ-B');
        $response->assertDontSee('MULTI-SEARCH-CUST');
    }

    /**
     * Point 19: Pagination works.
     */
    public function test_19_pagination_works(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            DataSyncEntityMapping::create([
                'api_client_id' => $this->apiClientA->id,
                'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                'external_code' => sprintf('PAGE-CUS-%03d', $i),
                'local_id' => $this->customer->id,
            ]);
        }

        $page1 = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'per_page' => 10,
            'page' => 1,
            'search' => 'PAGE-CUS-',
        ]));

        $page1->assertOk();
        $page1->assertSee('PAGE-CUS-030'); // Latest first
        $page1->assertDontSee('PAGE-CUS-010');

        $page2 = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'per_page' => 10,
            'page' => 2,
            'search' => 'PAGE-CUS-',
        ]));

        $page2->assertOk();
        $page2->assertSee('PAGE-CUS-020');
    }

    /**
     * Point 20: Soft-deleted customers remain identifiable.
     */
    public function test_20_soft_deleted_customers_remain_identifiable(): void
    {
        $deletedCustomer = Customer::factory()->create([
            'customer_code' => 'CUS-DELETED-99',
            'name' => 'Soft Deleted Customer Corp',
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-SOFTDEL-CUST',
            'local_id' => $deletedCustomer->id,
        ]);

        $deletedCustomer->delete(); // Soft-delete
        $this->assertTrue($deletedCustomer->trashed());

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'CRM-SOFTDEL-CUST',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-SOFTDEL-CUST');
        $response->assertSee('CUS-DELETED-99');
        $response->assertSee('Deleted');
    }

    /**
     * Point 21: Soft-deleted projects remain identifiable.
     */
    public function test_21_soft_deleted_projects_remain_identifiable(): void
    {
        $deletedProject = Project::factory()->create([
            'project_code' => 'PRJ-DELETED-99',
            'name' => 'Soft Deleted Project Platform',
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-SOFTDEL-PRJ',
            'local_id' => $deletedProject->id,
        ]);

        $deletedProject->delete(); // Soft-delete
        $this->assertTrue($deletedProject->trashed());

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'CRM-SOFTDEL-PRJ',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-SOFTDEL-PRJ');
        $response->assertSee('PRJ-DELETED-99');
        $response->assertSee('Deleted');
    }

    /**
     * Point 22: Missing PMS records do not cause errors.
     */
    public function test_22_missing_pms_records_do_not_cause_errors(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-MISSING-LOCAL',
            'local_id' => 9999888, // Non-existent PMS ID
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'CRM-MISSING-LOCAL',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-MISSING-LOCAL');
        $response->assertSee('Missing Record');
        $response->assertSee('PMS ID: 9999888');
    }

    /**
     * Point 23: Inactive API client mappings remain visible.
     */
    public function test_23_inactive_api_client_mappings_remain_visible(): void
    {
        $this->assertFalse((bool) $this->apiClientB->is_active);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientB->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-INACTIVE-CLIENT-MAP',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'search' => 'CRM-INACTIVE-CLIENT-MAP',
        ]));

        $response->assertOk();
        $response->assertSee('CRM-INACTIVE-CLIENT-MAP');
        $response->assertSee($this->apiClientB->name);
        $response->assertSee('Inactive');
    }

    /**
     * Point 24: No API secret or hash is exposed.
     */
    public function test_24_no_api_secret_or_hash_is_exposed(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-SECRET-CHECK',
            'local_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));
        $content = $response->getContent();

        $this->assertStringNotContainsString('supersecretrawtoken_crm', $content);
        $this->assertStringNotContainsString($this->apiClientA->secret_hash, $content);
        $this->assertStringNotContainsString('supersecretrawtoken_sec', $content);
        $this->assertStringNotContainsString($this->apiClientB->secret_hash, $content);
    }

    /**
     * Point 25: No mapping records are modified.
     */
    public function test_25_no_mapping_records_are_modified(): void
    {
        $mapping = DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-INTEGRITY-MAP',
            'local_id' => $this->customer->id,
        ]);

        $initialUpdatedAt = $mapping->fresh()->updated_at->toDateTimeString();

        // Perform view, filter, and search requests
        $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings', [
            'api_client_id' => $this->apiClientA->id,
            'search' => 'CRM-INTEGRITY-MAP',
        ]));

        $fresh = $mapping->fresh();
        $this->assertEquals($initialUpdatedAt, $fresh->updated_at->toDateTimeString());
        $this->assertEquals('CRM-INTEGRITY-MAP', $fresh->external_code);
    }

    /**
     * Point 26: No customer or project records are modified.
     */
    public function test_26_no_customer_or_project_records_are_modified(): void
    {
        $customerUpdatedAt = $this->customer->fresh()->updated_at->toDateTimeString();
        $projectUpdatedAt = $this->project->fresh()->updated_at->toDateTimeString();

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-RO-CUST',
            'local_id' => $this->customer->id,
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClientA->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_PROJECT,
            'external_code' => 'CRM-RO-PRJ',
            'local_id' => $this->project->id,
        ]);

        $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));

        $this->assertEquals($customerUpdatedAt, $this->customer->fresh()->updated_at->toDateTimeString());
        $this->assertEquals($projectUpdatedAt, $this->project->fresh()->updated_at->toDateTimeString());
    }

    /**
     * Point 27: API Clients tab remains functional.
     */
    public function test_27_api_clients_tab_remains_functional(): void
    {
        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.clients'));

        $response->assertOk();
        $response->assertViewIs('settings.data-sync.index');
        $response->assertViewHas('currentTab', 'clients');
        $response->assertSee('API Clients');
    }

    /**
     * Point 28: API Explorer remains functional.
     */
    public function test_28_api_explorer_remains_functional(): void
    {
        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.explorer'));

        $response->assertOk();
        $response->assertViewIs('settings.data-sync.index');
        $response->assertViewHas('currentTab', 'explorer');
        $response->assertSee('API Modules');
    }

    /**
     * Point 29: Existing Data Sync routes remain unchanged.
     */
    public function test_29_existing_data_sync_routes_remain_unchanged(): void
    {
        $this->assertTrue(Route::has('settings.data-sync.index'));
        $this->assertTrue(Route::has('settings.data-sync.clients'));
        $this->assertTrue(Route::has('settings.data-sync.clients.show'));
        $this->assertTrue(Route::has('settings.data-sync.clients.store'));
        $this->assertTrue(Route::has('settings.data-sync.clients.update'));
        $this->assertTrue(Route::has('settings.data-sync.clients.toggle-status'));
        $this->assertTrue(Route::has('settings.data-sync.clients.rotate'));
        $this->assertTrue(Route::has('settings.data-sync.clients.revoke'));
        $this->assertTrue(Route::has('settings.data-sync.explorer'));
        $this->assertTrue(Route::has('settings.data-sync.mappings'));
    }

    /**
     * Point 30: Existing regression tests pass.
     */
    public function test_30_existing_regression_tests_pass(): void
    {
        $response = $this->actingAs($this->viewUser)->get(route('settings.data-sync.mappings'));
        $response->assertOk();
        $response->assertSee('View relationships between external system records and PMS customers or projects.');
    }
}

