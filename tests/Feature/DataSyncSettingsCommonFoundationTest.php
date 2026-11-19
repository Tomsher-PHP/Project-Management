<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Services\ApiClientService;
use App\Services\DataSync\Settings\ModuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DataSyncSettingsCommonFoundationTest extends TestCase
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

    /**
     * Requirement 1: All four common routes are registered.
     */
    public function test_all_four_common_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('api.data-sync.settings.sync'));
        $this->assertTrue(Route::has('api.data-sync.settings.common-index'));
        $this->assertTrue(Route::has('api.data-sync.settings.common-show'));
        $this->assertTrue(Route::has('api.data-sync.settings.common-destroy'));

        $syncRoute = Route::getRoutes()->getByName('api.data-sync.settings.sync');
        $this->assertSame(['POST'], $syncRoute->methods());
        $this->assertSame('api/data-sync/settings/sync', $syncRoute->uri());

        $indexRoute = Route::getRoutes()->getByName('api.data-sync.settings.common-index');
        $this->assertContains('GET', $indexRoute->methods());
        $this->assertSame('api/data-sync/settings', $indexRoute->uri());

        $showRoute = Route::getRoutes()->getByName('api.data-sync.settings.common-show');
        $this->assertContains('GET', $showRoute->methods());
        $this->assertSame('api/data-sync/settings/{code}', $showRoute->uri());

        $destroyRoute = Route::getRoutes()->getByName('api.data-sync.settings.common-destroy');
        $this->assertSame(['DELETE'], $destroyRoute->methods());
        $this->assertSame('api/data-sync/settings/{code}', $destroyRoute->uri());
    }

    /**
     * Requirement 2: Existing Settings routes remain registered.
     */
    public function test_existing_settings_routes_remain_registered(): void
    {
        $this->assertTrue(Route::has('api.data-sync.settings.index'));
        $this->assertTrue(Route::has('api.data-sync.settings.show'));
        $this->assertTrue(Route::has('api.data-sync.settings.store'));
        $this->assertTrue(Route::has('api.data-sync.settings.update'));
        $this->assertTrue(Route::has('api.data-sync.settings.destroy'));

        $indexRoute = Route::getRoutes()->getByName('api.data-sync.settings.index');
        $this->assertContains('GET', $indexRoute->methods());
        $this->assertSame('api/data-sync/settings/{module}', $indexRoute->uri());

        $storeRoute = Route::getRoutes()->getByName('api.data-sync.settings.store');
        $this->assertSame(['POST'], $storeRoute->methods());
        $this->assertSame('api/data-sync/settings/{module}', $storeRoute->uri());
    }

    /**
     * Requirement 3: Missing API key returns HTTP 401.
     */
    public function test_missing_api_key_returns_http_401(): void
    {
        $this->postJson('/api/data-sync/settings/sync', ['module' => 'industries'])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->deleteJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Requirement 4: Invalid API key returns HTTP 401.
     */
    public function test_invalid_api_key_returns_http_401(): void
    {
        $invalidHeader = ['Authorization' => 'Bearer azo_live_invalid.azo_sec_invalid'];

        $this->withHeaders($invalidHeader)
            ->postJson('/api/data-sync/settings/sync', ['module' => 'industries'])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->withHeaders($invalidHeader)
            ->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->withHeaders($invalidHeader)
            ->getJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->withHeaders($invalidHeader)
            ->deleteJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Requirement 5: Missing read/write permissions return HTTP 403.
     * Authentication and authorization must execute before exposing module validation errors.
     */
    public function test_missing_read_write_permissions_return_http_403(): void
    {
        // Meeting-only client has neither settings.read nor settings.write
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->postJson('/api/data-sync/settings/sync')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->getJson('/api/data-sync/settings')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->getJson('/api/data-sync/settings/123456')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->deleteJson('/api/data-sync/settings/123456')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        // Read-only client cannot access write endpoints (sync, delete)
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->postJson('/api/data-sync/settings/sync', ['module' => 'industries'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->deleteJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        // Write-only client cannot access read endpoints (index, show)
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->getJson('/api/data-sync/settings/123456?module=industries')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * Requirement 6: Valid module names are accepted.
     */
    public function test_valid_module_names_are_accepted(): void
    {
        $modulePayloads = [
            'technologies' => ['module' => 'technologies', 'code' => '990001', 'name' => 'Valid Tech'],
            'industries' => ['module' => 'industries', 'code' => '990002', 'name' => 'Valid Industry'],
            'project_categories' => ['module' => 'project_categories', 'code' => '990003', 'name' => 'Valid Category'],
            'project_statuses' => ['module' => 'project_statuses', 'code' => '990004', 'name' => 'Valid Status', 'type' => 'open'],
        ];

        foreach (['technologies', 'industries', 'project_categories', 'project_statuses'] as $module) {
            $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
                ->postJson('/api/data-sync/settings/sync', $modulePayloads[$module])
                ->assertStatus(201)
                ->assertJsonPath('data.operation', 'created');

            $code = $modulePayloads[$module]['code'];

            $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
                ->getJson("/api/data-sync/settings?module={$module}")
                ->assertStatus(200)
                ->assertJsonPath('success', true);

            $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
                ->getJson("/api/data-sync/settings/{$code}?module={$module}")
                ->assertStatus(200)
                ->assertJsonPath('data.code', $code);

            $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
                ->deleteJson("/api/data-sync/settings/{$code}?module={$module}")
                ->assertStatus(200)
                ->assertJsonPath('success', true);
        }
    }

    /**
     * Requirement 7: Missing module returns HTTP 422.
     */
    public function test_missing_module_returns_http_422(): void
    {
        $expectedError = [
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => [
                'module' => [
                    'The module field is required.',
                ],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->postJson('/api/data-sync/settings/sync', [])
            ->assertStatus(422)
            ->assertJson($expectedError);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings')
            ->assertStatus(422)
            ->assertJson($expectedError);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings/123456')
            ->assertStatus(422)
            ->assertJson($expectedError);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->deleteJson('/api/data-sync/settings/123456')
            ->assertStatus(422)
            ->assertJson($expectedError);
    }

    /**
     * Requirement 8: Unsupported module returns HTTP 422.
     */
    public function test_unsupported_module_returns_http_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->postJson('/api/data-sync/settings/sync', ['module' => 'unsupported_module'])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['module']);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings?module=unsupported_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['module']);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings/123456?module=unsupported_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['module']);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->deleteJson('/api/data-sync/settings/123456?module=unsupported_module')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['module']);
    }

    /**
     * Requirement 9: The supported module list comes from configuration.
     */
    public function test_supported_module_list_comes_from_configuration(): void
    {
        // When only technologies is enabled in configuration
        config(['constants.data_sync_modules' => ['technologies' => 'technologies']]);

        $resolver = app(ModuleResolver::class);
        $this->assertTrue($resolver->isValid('technologies'));
        $this->assertFalse($resolver->isValid('industries'));
        $this->assertSame(['technologies'], $resolver->supportedModules());

        // Technologies should succeed (HTTP 200)
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings?module=technologies')
            ->assertStatus(200);

        // Industries should now fail validation (HTTP 422)
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['module']);

        // Restore configuration
        config(['constants.data_sync_modules' => [
            'technologies' => 'technologies',
            'industries' => 'industries',
            'project_categories' => 'project_categories',
            'project_statuses' => 'project_statuses',
        ]]);

        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(200);
    }

    /**
     * Requirement 10: Valid authorized requests succeed for all common endpoints.
     */
    public function test_valid_authorized_requests_succeed_for_all_common_endpoints(): void
    {
        // POST /sync is implemented in Job 2 and returns 201 Created
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'name' => 'Information Technology',
                'code' => '222222',
            ])
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'industries',
                    'code' => '222222',
                    'name' => 'Information Technology',
                    'operation' => 'created',
                ],
            ]);

        // GET index succeeds
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings?module=industries')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Records retrieved successfully.',
            ]);

        // GET show succeeds
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/data-sync/settings/222222?module=industries')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
                'data' => [
                    'code' => '222222',
                    'name' => 'Information Technology',
                ],
            ]);

        // DELETE succeeds
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->deleteJson('/api/data-sync/settings/222222?module=industries')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);
    }

    /**
     * Requirement 11: Existing module-specific routes still work.
     */
    public function test_existing_module_specific_routes_still_work(): void
    {
        // GET /{module}
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Records retrieved successfully.',
            ]);

        // POST /{module}
        $postResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/technologies', [
                'name' => 'Elixir',
            ])
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
            ]);

        $createdCode = $postResponse->json('data.code');

        // GET /{module}/{code}
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson("/api/data-sync/settings/technologies/{$createdCode}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record retrieved successfully.',
            ]);

        // PUT /{module}/{code}
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson("/api/data-sync/settings/technologies/{$createdCode}", [
                'name' => 'Elixir Lang',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
            ]);

        // DELETE /{module}/{code}
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson("/api/data-sync/settings/technologies/{$createdCode}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        // Unsupported module returns 404 on legacy route
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/unsupported_module')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Data Sync module.',
            ]);
    }

    /**
     * Requirement 12: /sync is not interpreted as a module.
     */
    public function test_sync_path_is_not_interpreted_as_module(): void
    {
        $resolver = app(ModuleResolver::class);
        $this->assertFalse($resolver->isValid('sync'));

        // POST /api/data-sync/settings/sync hits the common sync endpoint, returning 201 (not 404 "Invalid Data Sync module.")
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '888888',
                'name' => 'Sync Test Tech',
            ])
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'technologies',
                    'code' => '888888',
                    'operation' => 'created',
                ],
            ]);

        // GET /api/data-sync/settings/sync does not hit /{module} as a valid module
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/sync');

        $this->assertContains($response->status(), [404, 405]);
    }

    /**
     * Requirement 13: Six-digit codes are routed correctly.
     */
    public function test_six_digit_codes_are_routed_correctly(): void
    {
        // 6-digit code routes to commonShow (returns 404 when code does not exist)
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/123456?module=technologies')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);

        // 6-digit code routes to commonDestroy (returns 404 when code does not exist)
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/123456?module=technologies')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Record not found.',
            ]);

        // Word 'technologies' routes to module index (returns 200)
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200);
    }

    /**
     * Requirement 14: Existing Project Data Sync APIs remain unaffected.
     */
    public function test_existing_project_data_sync_apis_remain_unaffected(): void
    {
        // Unauthenticated returns 401
        $this->postJson('/api/data-sync/projects')
            ->assertStatus(401);

        // Settings-only key returns 403
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->postJson('/api/data-sync/projects')
            ->assertStatus(403);

        // Projects-authorized key passes authorization (returns 422 on empty payload)
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->postJson('/api/data-sync/projects')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ]);

        // Integration endpoints remain reachable with projects.write (return 404 when target code not found)
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->putJson('/api/integrations/projects/999999', ['name' => 'Updated Project'])
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->putJson('/api/integrations/customers/999999', ['name' => 'Updated Customer'])
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Requirement 15: Existing Meeting API remains unaffected.
     */
    public function test_existing_meeting_api_remains_unaffected(): void
    {
        // Unauthenticated returns 401
        $this->getJson('/api/meetings/active')
            ->assertStatus(401);

        // Settings-only key returns 403
        $this->withHeader('Authorization', 'Bearer ' . $this->fullKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(403);

        // Meetings-authorized key returns 200
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
