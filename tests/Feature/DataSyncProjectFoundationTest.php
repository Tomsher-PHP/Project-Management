<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DataSyncProjectFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        ProjectStatus::firstOrCreate(['is_default' => 1], [
            'name' => 'Planned',
            'code' => '100001',
            'type' => 'open',
            'sort_order' => 1,
            'is_completed' => 0,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        ProjectStage::firstOrCreate(['is_default' => 1], [
            'name' => 'Planning',
            'code' => '200001',
            'color' => '#000000',
            'sort_order' => 1,
            'is_system' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_project_data_sync_route_is_registered_properly(): void
    {
        $route = Route::getRoutes()->getByName('api.data-sync.projects.store');

        $this->assertNotNull($route, 'Route api.data-sync.projects.store is not registered.');
        $this->assertSame(['POST'], $route->methods());
        $this->assertSame('api/data-sync/projects', $route->uri());

        $action = $route->getAction();
        $this->assertSame('App\Http\Controllers\Api\DataSync\ProjectSyncController@store', $action['controller']);

        $middleware = $route->gatherMiddleware();
        $this->assertContains('api.key', $middleware);
        $this->assertContains('throttle:api-client', $middleware);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $this->postJson('/api/data-sync/projects', [
            'name' => 'Acme Website',
        ])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $this->withHeader('Authorization', 'Bearer azo_live_invalid.azo_sec_invalid')
            ->postJson('/api/data-sync/projects', [
                'name' => 'Acme Website',
            ])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_api_client_without_projects_write_scope_receives_403(): void
    {
        $result = app(ApiClientService::class)->createClient('Meeting Only Client', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/projects', [
                'name' => 'Acme Website',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_api_client_with_projects_write_scope_can_reach_projects_endpoint(): void
    {
        $result = app(ApiClientService::class)->createClient('Project Sync Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/projects', [
                'customer' => [
                    'name' => 'Acme Corp',
                    'contacts' => [
                        [
                            'name' => 'John Doe',
                            'email' => 'john@acme.test',
                            'is_primary' => true,
                        ],
                    ],
                ],
                'projects' => [['name' => 'Brand Redesign']],
            ])
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer and projects processed successfully.',
            ]);
    }

    public function test_existing_settings_scopes_do_not_grant_project_creation_permission(): void
    {
        $result = app(ApiClientService::class)->createClient('Settings Full Client', [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/projects', [
                'customer' => ['name' => 'Acme Corp'],
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_existing_meeting_api_authentication_remains_unchanged(): void
    {
        $result = app(ApiClientService::class)->createClient('Meetings App', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);

        $key = $result['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $projectsOnlyResult = app(ApiClientService::class)->createClient('Projects Only App', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);

        $projectsKey = $projectsOnlyResult['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $projectsKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden.',
            ]);
    }

    public function test_existing_settings_data_sync_apis_remain_unchanged(): void
    {
        $settingsClient = app(ApiClientService::class)->createClient('Settings App', [
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $settingsKey = $settingsClient['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $settingsKey)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $settingsKey)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'C++'])
            ->assertStatus(201);

        $projectsOnlyClient = app(ApiClientService::class)->createClient('Projects Only Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);

        $projectsKey = $projectsOnlyClient['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $projectsKey)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $projectsKey)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Elixir'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_existing_api_rate_limiting_continues_working_on_projects_endpoint(): void
    {
        $result = app(ApiClientService::class)->createClient('Throttled Projects Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);

        $key = $result['plain_text_key'];

        $validPayload = [
            'customer' => [
                'name' => 'Throttled Corp',
                'contacts' => [
                    [
                        'name' => 'John Doe',
                        'email' => 'john@throttled.test',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [['name' => 'Rate Limit Test']],
        ];

        $lastResponse = null;
        for ($i = 0; $i < 60; $i++) {
            $lastResponse = $this->withHeader('Authorization', 'Bearer ' . $key)
                ->postJson('/api/data-sync/projects', $validPayload);
            $lastResponse->assertStatus(201);
        }

        $this->assertSame('0', (string) $lastResponse->headers->get('X-RateLimit-Remaining'));

        // 61st request must trigger 429
        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/projects', $validPayload)
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too Many Requests.',
            ])
            ->assertHeader('Retry-After');
    }

    public function test_no_customer_contact_or_project_records_are_created_when_unauthorized(): void
    {
        $initialCustomers = Customer::count();
        $initialContacts = CustomerContact::count();
        $initialProjects = Project::count();

        // 1. Missing key
        $this->postJson('/api/data-sync/projects', [
            'customer' => [
                'name' => 'Acme Corporation',
                'email' => 'contact@acme.test',
                'contacts' => [
                    [
                        'name' => 'Jane Doe',
                        'email' => 'jane@acme.test',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'name' => 'Acme Enterprise Redesign',
                ],
            ],
        ])->assertStatus(401);

        // 2. Client without projects.write scope
        $meetingsClient = app(ApiClientService::class)->createClient('Meetings App', [
            ApiClient::SCOPE_MEETINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $meetingsClient['plain_text_key'])
            ->postJson('/api/data-sync/projects', [
                'customer' => [
                    'name' => 'Acme Corporation',
                    'email' => 'contact@acme.test',
                    'contacts' => [
                        [
                            'name' => 'Jane Doe',
                            'email' => 'jane@acme.test',
                            'is_primary' => true,
                        ],
                    ],
                ],
                'projects' => [
                    [
                        'name' => 'Acme Enterprise Redesign',
                    ],
                ],
            ])->assertStatus(403);

        $this->assertSame($initialCustomers, Customer::count());
        $this->assertSame($initialContacts, CustomerContact::count());
        $this->assertSame($initialProjects, Project::count());
    }

    public function test_api_client_create_command_supports_all_four_scopes_for_single_key(): void
    {
        $this->artisan('api-client:create', [
            '--name' => 'External Business Application',
            '--scope' => [
                'meetings.read',
                'settings.read',
                'settings.write',
                'projects.write',
            ],
            '--expires-at' => 'never',
        ])
            ->expectsOutputToContain('API client created successfully.')
            ->assertExitCode(0);

        $client = ApiClient::where('name', 'External Business Application')->first();
        $this->assertNotNull($client);
        $this->assertSame([
            'meetings.read',
            'settings.read',
            'settings.write',
            'projects.write',
        ], $client->scopes);
    }
}

