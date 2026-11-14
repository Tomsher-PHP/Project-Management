<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\User;
use App\Services\ApiClientService;
use App\Services\DataSync\Explorer\DataSyncExplorerRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncSettingsExplorerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $viewUser;
    protected User $unauthorizedUser;
    protected DataSyncExplorerRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(DataSyncExplorerRegistry::class);

        // 1. Authorized View User
        $this->viewUser = User::factory()->create(['is_super_admin' => false]);
        $viewPermission = Permission::firstOrCreate(['name' => 'data_sync_settings.view', 'guard_name' => 'web']);
        $this->viewUser->givePermissionTo($viewPermission);

        // 2. Unauthorized User
        $this->unauthorizedUser = User::factory()->create(['is_super_admin' => false]);
    }

    /**
     * Point 1: Unauthenticated user cannot access API Explorer.
     */
    public function test_unauthenticated_user_cannot_access_api_explorer(): void
    {
        $response = $this->get(route('settings.data-sync.explorer'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Point 2: User without permission cannot access API Explorer.
     */
    public function test_user_without_permission_cannot_access_api_explorer(): void
    {
        $this->actingAs($this->unauthorizedUser);

        // Web redirect test
        $response = $this->get(route('settings.data-sync.explorer'));
        $this->assertTrue($response->isRedirect() || $response->status() === 403);

        // JSON/AJAX 403 Forbidden test
        $jsonResponse = $this->getJson(route('settings.data-sync.explorer'));
        $jsonResponse->assertStatus(403);
    }

    /**
     * Point 3: User with permission can access API Explorer.
     */
    public function test_user_with_permission_can_access_api_explorer(): void
    {
        $this->actingAs($this->viewUser);

        $response = $this->get(route('settings.data-sync.explorer'));

        $response->assertOk();
        $response->assertViewIs('settings.data-sync.index');
        $response->assertViewHas('currentTab', 'explorer');
        $response->assertViewHas('explorerData');
        $response->assertSee('API Modules');
        $response->assertSee('Technologies');
        $response->assertSee('Industries');
        $response->assertSee('Projects');
        $response->assertSee('Meetings');
    }

    /**
     * Point 4: Settings category contains Technologies, Industries, Project Categories, Project Statuses.
     */
    public function test_registry_contains_settings_category_with_all_master_modules(): void
    {
        $categories = $this->registry->getCategories();
        $this->assertArrayHasKey('settings', $categories);

        $settingsModules = $this->registry->getModulesByCategory('settings');
        $expectedSlugs = ['technologies', 'industries', 'project_categories', 'project_statuses'];

        foreach ($expectedSlugs as $slug) {
            $this->assertArrayHasKey($slug, $settingsModules, "Missing master settings module: {$slug}");
            $this->assertEquals('settings', $settingsModules[$slug]['category']);
        }
    }

    /**
     * Point 5: Operations category contains Projects, Customers, Meetings.
     */
    public function test_registry_contains_operations_category_with_all_entities(): void
    {
        $categories = $this->registry->getCategories();
        $this->assertArrayHasKey('operations', $categories);

        $operationModules = $this->registry->getModulesByCategory('operations');
        $expectedSlugs = ['projects', 'customers', 'meetings'];

        foreach ($expectedSlugs as $slug) {
            $this->assertArrayHasKey($slug, $operationModules, "Missing operations module: {$slug}");
            $this->assertEquals('operations', $operationModules[$slug]['category']);
        }
    }

    /**
     * Point 6: Technologies module contains expected operations.
     */
    public function test_technologies_module_contains_expected_operations(): void
    {
        $module = $this->registry->getModule('technologies');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('list', $operations);
        $this->assertContains('show', $operations);
        $this->assertContains('create', $operations);
        $this->assertContains('update', $operations);
        $this->assertContains('toggle_status', $operations);
        $this->assertContains('delete', $operations);
    }

    /**
     * Point 7: Industries module contains expected operations.
     */
    public function test_industries_module_contains_expected_operations(): void
    {
        $module = $this->registry->getModule('industries');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('list', $operations);
        $this->assertContains('show', $operations);
        $this->assertContains('create', $operations);
        $this->assertContains('update', $operations);
        $this->assertContains('toggle_status', $operations);
        $this->assertContains('delete', $operations);
    }

    /**
     * Point 8: Project Categories module contains expected operations.
     */
    public function test_project_categories_module_contains_expected_operations(): void
    {
        $module = $this->registry->getModule('project_categories');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('list', $operations);
        $this->assertContains('show', $operations);
        $this->assertContains('create', $operations);
        $this->assertContains('update', $operations);
        $this->assertContains('toggle_status', $operations);
        $this->assertContains('delete', $operations);
    }

    /**
     * Point 9: Project Statuses module contains expected operations.
     */
    public function test_project_statuses_module_contains_expected_operations(): void
    {
        $module = $this->registry->getModule('project_statuses');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('list', $operations);
        $this->assertContains('show', $operations);
        $this->assertContains('create', $operations);
        $this->assertContains('update', $operations);
        $this->assertContains('toggle_status', $operations);
        $this->assertContains('delete', $operations);
    }

    /**
     * Point 10: Projects module contains create and update operations.
     */
    public function test_projects_module_contains_create_and_update_operations(): void
    {
        $module = $this->registry->getModule('projects');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('create', $operations);
        $this->assertContains('update', $operations);

        $this->assertEquals('POST', $module['operations']['create']['method']);
        $this->assertEquals('PUT', $module['operations']['update']['method']);
    }

    /**
     * Point 11: Customers module contains update operation.
     */
    public function test_customers_module_contains_update_operation(): void
    {
        $module = $this->registry->getModule('customers');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('update', $operations);
        $this->assertEquals('PUT', $module['operations']['update']['method']);
        $this->assertEquals('/api/integrations/customers/{customer_code}', $module['operations']['update']['endpoint']);
    }

    /**
     * Point 12: Meetings module contains active operation.
     */
    public function test_meetings_module_contains_active_operation(): void
    {
        $module = $this->registry->getModule('meetings');
        $this->assertNotNull($module);

        $operations = array_keys($module['operations']);
        $this->assertContains('active', $operations);
        $this->assertEquals('GET', $module['operations']['active']['method']);
        $this->assertEquals('/api/meetings/active', $module['operations']['active']['endpoint']);
    }

    /**
     * Point 13: All endpoints match actual registered Laravel API routes.
     */
    public function test_all_endpoints_match_actual_laravel_routes(): void
    {
        $router = app('router');
        $routes = $router->getRoutes();
        $modules = $this->registry->getModules();

        $sampleSubstitutions = [
            '{code}' => '500001',
            '{project_code}' => 'PRJ-1001',
            '{customer_code}' => 'CUST-2001',
        ];

        foreach ($modules as $modSlug => $module) {
            foreach ($module['operations'] as $opKey => $operation) {
                $rawEndpoint = $operation['endpoint'];
                $method = strtoupper($operation['method']);

                $resolvedEndpoint = str_replace(
                    array_keys($sampleSubstitutions),
                    array_values($sampleSubstitutions),
                    $rawEndpoint
                );

                $request = Request::create($resolvedEndpoint, $method);

                try {
                    $matchedRoute = $routes->match($request);
                    $this->assertNotNull(
                        $matchedRoute,
                        "Route match failed for {$method} {$rawEndpoint} (op: {$operation['id']})"
                    );
                } catch (\Exception $e) {
                    $this->fail("Endpoint '{$method} {$rawEndpoint}' does not match any Laravel route: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Point 14: Required scopes match expected values.
     */
    public function test_required_scopes_match_expected_api_scopes(): void
    {
        $validScopes = ApiClientService::SUPPORTED_SCOPES;
        $modules = $this->registry->getModules();

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $scope = $operation['scope'];
                $this->assertContains(
                    $scope,
                    $validScopes,
                    "Invalid scope '{$scope}' in operation {$operation['id']}"
                );
            }
        }
    }

    /**
     * Point 15: Request payloads are valid JSON or null.
     */
    public function test_request_payloads_are_valid_json_or_null(): void
    {
        $modules = $this->registry->getModules();

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $payload = $operation['request_payload'];
                if ($payload !== null) {
                    $json = json_encode($payload);
                    $this->assertNotFalse($json, "Payload JSON encoding failed for {$operation['id']}");
                    $decoded = json_decode($json, true);
                    $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Payload JSON decoding failed for {$operation['id']}");
                    $this->assertIsArray($decoded);
                }
            }
        }
    }

    /**
     * Point 16: Success responses are valid JSON.
     */
    public function test_success_responses_are_valid_json(): void
    {
        $modules = $this->registry->getModules();

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $responseSuccess = $operation['response_success'];
                $this->assertIsArray($responseSuccess, "Missing response_success in {$operation['id']}");
                $this->assertArrayHasKey('status', $responseSuccess);
                $this->assertContains($responseSuccess['status'], [200, 201]);

                $json = json_encode($responseSuccess['body']);
                $this->assertNotFalse($json, "Response success JSON encode failed for {$operation['id']}");
                $decoded = json_decode($json, true);
                $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Response success decode failed for {$operation['id']}");
                $this->assertIsArray($decoded);
            }
        }
    }

    /**
     * Point 17: Error responses are valid JSON.
     */
    public function test_error_responses_are_valid_json(): void
    {
        $modules = $this->registry->getModules();

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $responseErrors = $operation['response_errors'] ?? [];
                foreach ($responseErrors as $statusCode => $errorBody) {
                    $this->assertIsInt($statusCode, "Error status code must be integer in {$operation['id']}");
                    $this->assertGreaterThanOrEqual(400, $statusCode);

                    $json = json_encode($errorBody);
                    $this->assertNotFalse($json, "Error JSON encode failed for {$operation['id']} ({$statusCode})");
                    $decoded = json_decode($json, true);
                    $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Error JSON decode failed for {$operation['id']} ({$statusCode})");
                    $this->assertIsArray($decoded);
                }
            }
        }
    }

    /**
     * Point 18: cURL command contains placeholder credentials and valid structure.
     */
    public function test_curl_command_contains_placeholder_credentials_and_valid_structure(): void
    {
        $modules = $this->registry->getModules();

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $curl = $this->registry->buildCurlCommand($operation, 'https://pms.example.com');

                $this->assertStringStartsWith('curl --location --request', $curl);
                $this->assertStringContainsString('https://pms.example.com', $curl);
                $this->assertStringContainsString('YOUR_API_KEY', $curl);

                if (in_array(strtoupper($operation['method']), ['POST', 'PUT', 'PATCH']) && !empty($operation['request_payload'])) {
                    $this->assertStringContainsString('--data', $curl);
                }
            }
        }
    }

    /**
     * Point 19: Explorer never exposes real API secrets or sensitive credentials.
     */
    public function test_explorer_never_exposes_real_api_secrets(): void
    {
        $this->actingAs($this->viewUser);

        // Create an active client in DB with actual key_id
        $client = ApiClient::create([
            'name' => 'Secret Test App',
            'key_id' => 'azo_live_publictestkey123',
            'secret_hash' => hash('sha256', 'supersecretrawtoken456'),
            'scopes' => [ApiClient::SCOPE_SETTINGS_READ],
            'is_active' => true,
        ]);

        $response = $this->get(route('settings.data-sync.explorer'));
        $content = $response->getContent();

        $this->assertStringNotContainsString('supersecretrawtoken456', $content);
        $this->assertStringNotContainsString($client->secret_hash, $content);
        $this->assertStringNotContainsString('azo_live_publictestkey123', $content);
    }

    /**
     * Point 20: Regression test — API Clients tab remains fully functional.
     */
    public function test_regression_api_clients_tab_remains_fully_functional(): void
    {
        $this->actingAs($this->viewUser);

        $response = $this->get(route('settings.data-sync.clients'));

        $response->assertOk();
        $response->assertViewIs('settings.data-sync.index');
        $response->assertViewHas('currentTab', 'clients');
        $response->assertViewHas('clients');
        $response->assertSee('API Clients');
    }
}
