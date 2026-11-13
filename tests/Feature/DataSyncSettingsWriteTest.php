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

class DataSyncSettingsWriteTest extends TestCase
{
    use RefreshDatabase;

    protected string $writeKey;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        $result = app(ApiClientService::class)->createClient('Write Sync Client', [
            ApiClient::SCOPE_SETTINGS_WRITE,
        ]);

        $this->writeKey = $result['plain_text_key'];
    }

    public function test_create_and_update_technology(): void
    {
        // Auto-generated 6-digit code when omitted
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/technologies', [
                'name' => 'Elixir',
                'sort_order' => 5,
                'is_active' => true,
                'is_system' => true, // must be ignored (server-managed)
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Record created successfully.')
            ->assertJsonPath('data.name', 'Elixir')
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.is_active', true);

        $code = $createResponse->json('data.code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code);

        // Partial update preserves unspecified fields & code
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson("/api/data-sync/settings/technologies/{$code}", [
                'name' => 'Elixir OTP',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Record updated successfully.')
            ->assertJsonPath('data.name', 'Elixir OTP')
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_active', true);

        // Reject attempt to change code
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson("/api/data-sync/settings/technologies/{$code}", [
                'code' => '999999',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['code']]);

        // Update non-existent record returns 404
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/technologies/000000', [
                'name' => 'Missing',
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Record not found.');
    }

    public function test_create_and_update_industry_with_parent_code_and_hierarchy_guards(): void
    {
        $parentResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/industries', [
                'name' => 'Automotive',
                'code' => '845621',
                'parent_code' => null,
                'sort_order' => 15,
                'is_active' => true,
            ]);

        $parentResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Record created successfully.',
                'data' => [
                    'name' => 'Automotive',
                    'code' => '845621',
                    'parent_code' => null,
                    'sort_order' => 15,
                    'is_system' => false,
                    'is_active' => true,
                ],
            ]);

        $childResponse = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/industries', [
                'name' => 'EV Manufacturing',
                'code' => '845622',
                'parent_code' => '845621',
                'sort_order' => 16,
            ]);

        $childResponse->assertStatus(201)
            ->assertJsonPath('data.parent_code', '845621');

        // Self-parenting rejected
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/industries/845621', [
                'parent_code' => '845621',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_code']]);

        // Circular parent (parent -> child) rejected
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/industries/845621', [
                'parent_code' => '845622',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_code']]);

        // Soft-deleted parent rejected
        $softDeletedParent = Industry::create([
            'name' => 'Old Parent',
            'code' => '777777',
            'sort_order' => 1,
        ]);
        $softDeletedParent->delete();

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/industries', [
                'name' => 'Orphan',
                'parent_code' => '777777',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_code']]);

        // Omitting parent_code preserves existing parent
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/industries/845622', [
                'name' => 'EV Manufacturing Updated',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.parent_code', '845621');

        // Explicit parent_code: null removes parent relationship
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/industries/845622', [
                'parent_code' => null,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.parent_code', null);
    }

    public function test_create_and_update_project_category(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/project_categories', [
                'name' => 'AI Integration',
                'code' => '556677',
                'sort_order' => 3,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.code', '556677')
            ->assertJsonPath('data.name', 'AI Integration');

        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/project_categories/556677', [
                'name' => 'AI & ML Integration',
                'is_active' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'AI & ML Integration')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_create_and_update_project_status_business_rules(): void
    {
        // Create default open status
        $first = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/project_statuses', [
                'name' => 'Backlog',
                'code' => '900001',
                'color' => '#112233',
                'type' => ProjectStatus::TYPE_OPEN,
                'sort_order' => 1,
                'is_default' => true,
                'is_completed' => true, // must be ignored & derived from type
            ]);

        $first->assertStatus(201)
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.is_completed', false);

        // Creating a second default status clears the first atomically
        $second = $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/project_statuses', [
                'name' => 'Done',
                'code' => '900002',
                'type' => ProjectStatus::TYPE_COMPLETED,
                'sort_order' => 2,
                'is_default' => true,
            ]);

        $second->assertStatus(201)
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.is_completed', true);

        $this->assertFalse((bool) ProjectStatus::where('code', '900001')->first()->is_default);

        // Updating first status back to default clears second
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->putJson('/api/data-sync/settings/project_statuses/900001', [
                'is_default' => true,
                'type' => ProjectStatus::TYPE_COMPLETED,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.is_completed', true);

        $this->assertFalse((bool) ProjectStatus::where('code', '900002')->first()->is_default);

        // Invalid status type rejected
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/project_statuses', [
                'name' => 'Invalid Type',
                'type' => 'unknown_type',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['type']]);
    }

    public function test_duplicate_code_returns_409_including_soft_deleted_and_idempotency_key(): void
    {
        $deleted = Technology::create([
            'name' => 'Soft Deleted Tech',
            'code' => '654321',
            'sort_order' => 1,
        ]);
        $deleted->delete();

        // Explicit duplicate code (even soft-deleted) returns 409 Conflict
        $this->withHeader('Authorization', 'Bearer ' . $this->writeKey)
            ->postJson('/api/data-sync/settings/technologies', [
                'name' => 'New Tech',
                'code' => '654321',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'A record with this code already exists.',
            ]);

        // Idempotency-Key returns cached 201 without creating a duplicate
        $idempotencyKey = 'sync-req-001';
        $payload = ['name' => 'GraphQL', 'code' => '123789'];

        $first = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/technologies', $payload);

        $first->assertStatus(201);

        $second = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->writeKey,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/data-sync/settings/technologies', $payload);

        $second->assertStatus(201)
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, Technology::where('code', '123789')->count());
    }
}

