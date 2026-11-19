<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Industry;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncSettingsCommonSyncTest extends TestCase
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
     * Scenario 1: Create Technology.
     */
    public function test_1_create_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Laravel',
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'technologies',
                    'code' => '222222',
                    'name' => 'Laravel',
                    'sort_order' => 1,
                    'is_active' => true,
                    'operation' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('technologies', [
            'code' => '222222',
            'name' => 'Laravel',
        ]);
    }

    /**
     * Scenario 2: Update Technology.
     */
    public function test_2_update_technology(): void
    {
        Technology::create([
            'code' => '222222',
            'name' => 'Laravel',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Laravel Framework',
                'sort_order' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => [
                    'module' => 'technologies',
                    'code' => '222222',
                    'name' => 'Laravel Framework',
                    'sort_order' => 5,
                    'is_active' => true,
                    'operation' => 'updated',
                ],
            ]);

        $this->assertSame(1, Technology::where('code', '222222')->count());
        $this->assertDatabaseHas('technologies', [
            'code' => '222222',
            'name' => 'Laravel Framework',
            'sort_order' => 5,
        ]);
    }

    /**
     * Scenario 3: Create Industry.
     */
    public function test_3_create_industry(): void
    {
        $parent = Industry::create([
            'name' => 'Technology Parent',
            'code' => '100011',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '333333',
                'name' => 'Information Technology',
                'parent_code' => '100011',
                'sort_order' => 2,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'industries',
                    'code' => '333333',
                    'name' => 'Information Technology',
                    'parent_code' => '100011',
                    'sort_order' => 2,
                    'is_active' => true,
                    'operation' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('industries', [
            'code' => '333333',
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Scenario 4: Update Industry.
     */
    public function test_4_update_industry(): void
    {
        $parent1 = Industry::create(['name' => 'Parent 1', 'code' => '100011', 'sort_order' => 1]);
        $parent2 = Industry::create(['name' => 'Parent 2', 'code' => '100012', 'sort_order' => 2]);

        $child = Industry::create([
            'name' => 'IT Services',
            'code' => '333333',
            'parent_id' => $parent1->id,
            'sort_order' => 3,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '333333',
                'name' => 'Information Technology Services',
                'parent_code' => '100012',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => [
                    'module' => 'industries',
                    'code' => '333333',
                    'name' => 'Information Technology Services',
                    'parent_code' => '100012',
                    'operation' => 'updated',
                ],
            ]);

        $this->assertDatabaseHas('industries', [
            'code' => '333333',
            'parent_id' => $parent2->id,
        ]);
    }

    /**
     * Scenario 5: Create Project Category.
     */
    public function test_5_create_project_category(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_categories',
                'code' => '444444',
                'name' => 'Web Development',
                'sort_order' => 3,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'project_categories',
                    'code' => '444444',
                    'name' => 'Web Development',
                    'sort_order' => 3,
                    'is_active' => true,
                    'operation' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('project_categories', [
            'code' => '444444',
            'name' => 'Web Development',
        ]);
    }

    /**
     * Scenario 6: Update Project Category.
     */
    public function test_6_update_project_category(): void
    {
        ProjectCategory::create([
            'code' => '444444',
            'name' => 'Web Development',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_categories',
                'code' => '444444',
                'name' => 'Web Application Development',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => [
                    'module' => 'project_categories',
                    'code' => '444444',
                    'name' => 'Web Application Development',
                    'sort_order' => 3,
                    'is_active' => false,
                    'operation' => 'updated',
                ],
            ]);
    }

    /**
     * Scenario 7: Create Project Status.
     */
    public function test_7_create_project_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_statuses',
                'code' => '900001',
                'name' => 'In Review',
                'color' => '#0d6efd',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'sort_order' => 6,
                'is_default' => false,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'module' => 'project_statuses',
                    'code' => '900001',
                    'name' => 'In Review',
                    'color' => '#0d6efd',
                    'type' => ProjectStatus::TYPE_IN_PROGRESS,
                    'is_default' => false,
                    'is_completed' => false,
                    'operation' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('project_statuses', [
            'code' => '900001',
            'is_completed' => false,
        ]);
    }

    /**
     * Scenario 8: Update Project Status.
     */
    public function test_8_update_project_status(): void
    {
        ProjectStatus::create([
            'code' => '900001',
            'name' => 'In Review',
            'color' => '#0d6efd',
            'type' => ProjectStatus::TYPE_IN_PROGRESS,
            'is_completed' => false,
            'sort_order' => 6,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_statuses',
                'code' => '900001',
                'name' => 'Finished & Closed',
                'type' => ProjectStatus::TYPE_COMPLETED,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
                'data' => [
                    'module' => 'project_statuses',
                    'code' => '900001',
                    'name' => 'Finished & Closed',
                    'type' => ProjectStatus::TYPE_COMPLETED,
                    'is_completed' => true,
                    'operation' => 'updated',
                ],
            ]);

        $this->assertDatabaseHas('project_statuses', [
            'code' => '900001',
            'is_completed' => true,
        ]);
    }

    /**
     * Scenario 9: Missing module returns 422.
     */
    public function test_9_missing_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'code' => '222222',
                'name' => 'No Module',
            ])
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
     * Scenario 10: Unsupported module returns 422.
     */
    public function test_10_unsupported_module_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'unsupported_module',
                'code' => '222222',
                'name' => 'Unsupported',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['module']);
    }

    /**
     * Scenario 11: Missing code returns 422.
     */
    public function test_11_missing_code_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'name' => 'Missing Code',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'code' => [
                        'The code field is required.',
                    ],
                ],
            ]);
    }

    /**
     * Scenario 12: Invalid code format returns 422.
     */
    public function test_12_invalid_code_format_returns_422(): void
    {
        // 5 digits
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '12345',
                'name' => 'Five Digits',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // 7 digits
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '1234567',
                'name' => 'Seven Digits',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Non-numeric
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => 'abcdef',
                'name' => 'Alpha Code',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Scenario 13: Missing name on create returns 422.
     */
    public function test_13_missing_name_on_create_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    /**
     * Scenario 14: Existing record update without name preserves its name.
     */
    public function test_14_existing_record_update_without_name_preserves_name(): void
    {
        Technology::create([
            'code' => '222222',
            'name' => 'Original Name',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'is_active' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Original Name')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('technologies', [
            'code' => '222222',
            'name' => 'Original Name',
            'is_active' => false,
        ]);
    }

    /**
     * Scenario 15: Invalid industry parent returns 422.
     */
    public function test_15_invalid_industry_parent_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '333333',
                'name' => 'Invalid Parent Industry',
                'parent_code' => '999999',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_code']);
    }

    /**
     * Scenario 16: Circular industry parent returns 422.
     */
    public function test_16_circular_industry_parent_returns_422(): void
    {
        $indA = Industry::create(['name' => 'Ind A', 'code' => '111111', 'sort_order' => 1]);

        // Self-parenting on update
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '111111',
                'parent_code' => '111111',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_code']);

        $indB = Industry::create(['name' => 'Ind B', 'code' => '222222', 'parent_id' => $indA->id, 'sort_order' => 2]);

        // Making Ind A's parent Ind B (circular)
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '111111',
                'parent_code' => '222222',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_code']);
    }

    /**
     * Scenario 17: Invalid project status type returns 422.
     */
    public function test_17_invalid_project_status_type_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_statuses',
                'code' => '900001',
                'name' => 'Bad Type',
                'type' => 'nonexistent_status_type',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Scenario 18: Direct is_completed modification is rejected according to existing rules.
     */
    public function test_18_direct_is_completed_modification_is_rejected_according_to_existing_rules(): void
    {
        // When type is open, sending is_completed: true is ignored and derived as false
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_statuses',
                'code' => '900001',
                'name' => 'Backlog Task',
                'type' => ProjectStatus::TYPE_OPEN,
                'is_completed' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', ProjectStatus::TYPE_OPEN)
            ->assertJsonPath('data.is_completed', false);

        $this->assertDatabaseHas('project_statuses', [
            'code' => '900001',
            'is_completed' => false,
        ]);
    }

    /**
     * Scenario 19: Existing code updates instead of creating duplicate.
     */
    public function test_19_existing_code_updates_instead_of_creating_duplicate(): void
    {
        $original = Technology::create([
            'code' => '222222',
            'name' => 'Original',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Updated',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $original->id)
            ->assertJsonPath('data.operation', 'updated');

        $this->assertSame(1, Technology::where('code', '222222')->count());
    }

    /**
     * Scenario 20: New code creates a record.
     */
    public function test_20_new_code_creates_a_record(): void
    {
        $this->assertSame(0, Technology::where('code', '555555')->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '555555',
                'name' => 'Brand New Tech',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.operation', 'created');

        $this->assertSame(1, Technology::where('code', '555555')->count());
    }

    /**
     * Scenario 21: Soft-deleted code returns 409.
     */
    public function test_21_soft_deleted_code_returns_409(): void
    {
        $tech = Technology::create([
            'code' => '654321',
            'name' => 'Deleted Tech',
            'sort_order' => 1,
        ]);
        $tech->delete();

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '654321',
                'name' => 'Attempt to Recreate',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'The supplied code belongs to a deleted record.',
            ]);

        // Verify record is still soft-deleted and not restored or altered
        $this->assertSoftDeleted('technologies', ['code' => '654321']);
    }

    /**
     * Scenario 22: Omitted update fields remain unchanged.
     */
    public function test_22_omitted_update_fields_remain_unchanged(): void
    {
        Technology::create([
            'code' => '777777',
            'name' => 'Keep This Name',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '777777',
                'sort_order' => 20,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Keep This Name')
            ->assertJsonPath('data.sort_order', 20)
            ->assertJsonPath('data.is_active', true);
    }

    /**
     * Scenario 23: is_default switching preserves existing status rules.
     */
    public function test_23_is_default_switching_preserves_existing_status_rules(): void
    {
        $first = ProjectStatus::create([
            'code' => '900001',
            'name' => 'First Default',
            'type' => ProjectStatus::TYPE_OPEN,
            'is_default' => true,
        ]);

        $this->assertTrue((bool) $first->refresh()->is_default);

        $second = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'project_statuses',
                'code' => '900002',
                'name' => 'Second Default',
                'type' => ProjectStatus::TYPE_IN_PROGRESS,
                'is_default' => true,
            ]);

        $second->assertStatus(201)
            ->assertJsonPath('data.is_default', true);

        // Previous default was cleared atomically
        $this->assertFalse((bool) ProjectStatus::where('code', '900001')->first()->is_default);
    }

    /**
     * Scenario 24: Module-specific fields are preserved.
     */
    public function test_24_module_specific_fields_are_preserved(): void
    {
        $parent = Industry::create(['name' => 'Parent Ind', 'code' => '100011', 'sort_order' => 1]);
        $child = Industry::create(['name' => 'Child Ind', 'code' => '200022', 'parent_id' => $parent->id]);

        // Omitting parent_code preserves existing parent
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '200022',
                'name' => 'Child Ind Renamed',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.parent_code', '100011');

        // Explicit parent_code: null clears parent
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => '200022',
                'parent_code' => null,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.parent_code', null);
    }

    /**
     * Scenario 25: Identical codes in different modules are resolved independently.
     */
    public function test_25_identical_codes_in_different_modules_are_resolved_independently(): void
    {
        $sharedCode = '888888';

        // Create Technology with code 888888
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => $sharedCode,
                'name' => 'Shared Tech',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.module', 'technologies')
            ->assertJsonPath('data.name', 'Shared Tech');

        // Create Industry with same code 888888
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'industries',
                'code' => $sharedCode,
                'name' => 'Shared Industry',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.module', 'industries')
            ->assertJsonPath('data.name', 'Shared Industry');

        // Both records exist independently in their respective tables
        $this->assertDatabaseHas('technologies', ['code' => $sharedCode, 'name' => 'Shared Tech']);
        $this->assertDatabaseHas('industries', ['code' => $sharedCode, 'name' => 'Shared Industry']);

        // Update Technology does not affect Industry
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => $sharedCode,
                'name' => 'Updated Tech Only',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('technologies', ['code' => $sharedCode, 'name' => 'Updated Tech Only']);
        $this->assertDatabaseHas('industries', ['code' => $sharedCode, 'name' => 'Shared Industry']);
    }

    /**
     * Scenario 26: Repeated requests do not create duplicate records (Idempotency).
     */
    public function test_26_repeated_requests_do_not_create_duplicate_records(): void
    {
        $idempotencyKey = 'sync-test-key-001';
        $payload = [
            'module' => 'technologies',
            'code' => '445566',
            'name' => 'Idempotent Tech',
        ];

        // First request creates record
        $first = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/sync', $payload);

        $first->assertStatus(201)
            ->assertJsonPath('data.operation', 'created');

        // Second request with same idempotency key returns cached 201 response
        $second = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/sync', $payload);

        $second->assertStatus(201)
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, Technology::where('code', '445566')->count());
    }

    /**
     * Scenario 27: Reusing idempotency key with different payload returns 409.
     */
    public function test_27_reusing_idempotency_key_with_different_payload_returns_409(): void
    {
        $idempotencyKey = 'sync-test-key-002';

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/sync', [
            'module' => 'technologies',
            'code' => '445577',
            'name' => 'First Tech',
        ])->assertStatus(201);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/sync', [
            'module' => 'technologies',
            'code' => '445577',
            'name' => 'Different Tech Name',
        ])->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Idempotency-Key reuse with different request payload.',
            ]);
    }

    /**
     * Scenario 28: Missing API key returns 401.
     */
    public function test_28_missing_api_key_returns_401(): void
    {
        $this->postJson('/api/data-sync/settings/sync', [
            'module' => 'technologies',
            'code' => '222222',
            'name' => 'Tech',
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Scenario 29: Invalid API key returns 401.
     */
    public function test_29_invalid_api_key_returns_401(): void
    {
        $this->withHeader('Authorization', 'Bearer azo_live_badkey.azo_sec_badsecret')
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Tech',
            ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Scenario 30: Missing settings.write returns 403.
     */
    public function test_30_missing_settings_write_returns_403(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Tech',
            ])->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Tech',
            ])->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * Scenario 31: Existing settings.read scope alone cannot create/update.
     */
    public function test_31_existing_settings_read_scope_alone_cannot_create_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->postJson('/api/data-sync/settings/sync', [
                'module' => 'technologies',
                'code' => '222222',
                'name' => 'Tech',
            ])->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    /**
     * Scenario 32: Existing module-specific POST works.
     */
    public function test_32_existing_module_specific_post_works(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/technologies', [
                'name' => 'Rust Language',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
            ])
            ->assertJsonStructure(['data' => ['code', 'name']]);

        $this->assertNotEmpty($response->json('data.code'));
    }

    /**
     * Scenario 33: Existing module-specific PUT works.
     */
    public function test_33_existing_module_specific_put_works(): void
    {
        Technology::create([
            'code' => '334455',
            'name' => 'Old Tech Name',
            'sort_order' => 1,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/technologies/334455', [
                'name' => 'New Tech Name',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record updated successfully.',
            ])
            ->assertJsonPath('data.name', 'New Tech Name');
    }

    /**
     * Scenario 34: Existing Settings List/Get/Delete remain unchanged.
     */
    public function test_34_existing_settings_list_get_delete_remain_unchanged(): void
    {
        Technology::create(['code' => '112233', 'name' => 'Tech To Delete', 'sort_order' => 1]);

        // GET /{module}
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination']);

        // GET /{module}/{code}
        $this->withHeader('Authorization', 'Bearer ' . $this->readKey)
            ->getJson('/api/data-sync/settings/technologies/112233')
            ->assertStatus(200)
            ->assertJsonPath('data.code', '112233');

        // DELETE /{module}/{code}
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->deleteJson('/api/data-sync/settings/technologies/112233')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);

        $this->assertSoftDeleted('technologies', ['code' => '112233']);
    }

    /**
     * Scenario 35: Existing Project Data Sync API remains unaffected.
     */
    public function test_35_existing_project_data_sync_api_remains_unaffected(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->projectOnlyKey)
            ->postJson('/api/data-sync/projects')
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ]);
    }

    /**
     * Scenario 36: Existing Meeting API remains unaffected.
     */
    public function test_36_existing_meeting_api_remains_unaffected(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->meetingOnlyKey)
            ->getJson('/api/meetings/active')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}

