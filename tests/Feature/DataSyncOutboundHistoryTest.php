<?php

namespace Tests\Feature;

use App\Models\DataSyncOutboundApi;
use App\Models\DataSyncOutboundConnection;
use App\Models\DataSyncOutboundHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DataSyncOutboundHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $authorizedUser;
    protected User $unauthorizedUser;
    protected DataSyncOutboundConnection $connection;
    protected DataSyncOutboundApi $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        foreach (['data_sync_settings.view', 'data_sync_settings.create', 'data_sync_settings.edit', 'data_sync_settings.delete'] as $perm) {
            $permission = Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
            $this->authorizedUser->givePermissionTo($permission);
        }

        $this->unauthorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $this->connection = DataSyncOutboundConnection::create([
            'name' => 'Test External CRM',
            'base_url' => 'https://crm.example.com/api',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'internal-conn-token'],
            'is_active' => true,
        ]);

        $this->api = DataSyncOutboundApi::create([
            'connection_id' => $this->connection->id,
            'module' => 'projects',
            'operation' => 'create',
            'method' => 'POST',
            'endpoint' => '/projects',
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_view_history(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get('/settings/data-sync/outbound/history');
        $response->assertStatus(302);

        $jsonResponse = $this->actingAs($this->unauthorizedUser)->getJson('/settings/data-sync/outbound/history');
        $jsonResponse->assertStatus(403);
    }

    public function test_authorized_user_can_view_history_empty_state(): void
    {
        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history');

        $response->assertStatus(200);
        $response->assertSee('No Outbound History Found');
        $response->assertSee('Outbound sync execution logs, payloads, and delivery status will appear here once executed.');
    }

    public function test_authorized_user_can_view_history_listing_with_records(): void
    {
        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'entity_id' => 101,
            'request_url' => 'https://crm.example.com/api/projects/alpha-listing-test',
            'request_payload' => ['title' => 'Alpha Project', 'budget' => 50000],
            'response_code' => 201,
            'response_body' => json_encode(['id' => 'crm_101', 'status' => 'created']),
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
        ]);

        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history');

        $response->assertStatus(200);
        $response->assertSee('Test External CRM');
        $response->assertSee('Projects');
        $response->assertSee('Create');
        $response->assertSee('Success');
        $response->assertSee('POST /projects');
    }

    public function test_can_filter_history_by_connection(): void
    {
        $otherConn = DataSyncOutboundConnection::create([
            'name' => 'Other HR System',
            'base_url' => 'https://hr.example.com',
            'auth_type' => 'none',
            'is_active' => true,
        ]);

        $otherApi = DataSyncOutboundApi::create([
            'connection_id' => $otherConn->id,
            'module' => 'employees',
            'operation' => 'update',
            'method' => 'PUT',
            'endpoint' => '/employees/1',
            'is_active' => true,
        ]);

        $history1 = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/unique-crm-endpoint',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 200,
        ]);

        $history2 = DataSyncOutboundHistory::create([
            'outbound_api_id' => $otherApi->id,
            'request_url' => 'https://hr.example.com/employees/unique-hr-endpoint',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 200,
        ]);

        $response = $this->actingAs($this->authorizedUser)->get("/settings/data-sync/outbound/history?connection_id={$otherConn->id}");

        $response->assertStatus(200);
        $response->assertSee('PUT /employees/1');
        $response->assertDontSee('POST /projects');
    }

    public function test_can_filter_history_by_module_and_operation(): void
    {
        $customerApi = DataSyncOutboundApi::create([
            'connection_id' => $this->connection->id,
            'module' => 'customers',
            'operation' => 'delete',
            'method' => 'DELETE',
            'endpoint' => '/customers/42',
            'is_active' => true,
        ]);

        DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/module-op-test',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
        ]);

        DataSyncOutboundHistory::create([
            'outbound_api_id' => $customerApi->id,
            'request_url' => 'https://crm.example.com/api/customers/42',
            'status' => DataSyncOutboundHistory::STATUS_FAILED,
        ]);

        $response = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history?module=customers&operation=delete');

        $response->assertStatus(200);
        $response->assertSee('DELETE /customers/42');
        $response->assertDontSee('POST /projects');
    }

    public function test_can_filter_history_by_status(): void
    {
        $successHistory = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/status-success-record',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 200,
        ]);

        $failedHistory = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/status-failed-record',
            'status' => DataSyncOutboundHistory::STATUS_FAILED,
            'response_code' => 502,
            'error_message' => 'Connection bad gateway failure',
        ]);

        $responseSuccess = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history?status=success');
        $responseSuccess->assertStatus(200);
        $responseSuccess->assertSee("data-history-id=\"{$successHistory->id}\"", false);
        $responseSuccess->assertDontSee("data-history-id=\"{$failedHistory->id}\"", false);

        $responseFailed = $this->actingAs($this->authorizedUser)->get('/settings/data-sync/outbound/history?status=failed');
        $responseFailed->assertStatus(200);
        $responseFailed->assertSee("data-history-id=\"{$failedHistory->id}\"", false);
        $responseFailed->assertDontSee("data-history-id=\"{$successHistory->id}\"", false);
    }

    public function test_can_filter_history_by_date_range(): void
    {
        $pastHistory = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/past-date-record',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 200,
        ]);
        \DB::table('data_sync_outbound_histories')->where('id', $pastHistory->id)->update([
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        $recentHistory = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects/recent-date-record',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
            'response_code' => 201,
        ]);

        $yesterday = Carbon::now()->subDay()->format('Y-m-d');
        $tomorrow = Carbon::now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->authorizedUser)->get("/settings/data-sync/outbound/history?date_from={$yesterday}&date_to={$tomorrow}");

        $response->assertStatus(200);
        $response->assertSee("data-history-id=\"{$recentHistory->id}\"", false);
        $response->assertDontSee("data-history-id=\"{$pastHistory->id}\"", false);
    }

    public function test_can_view_history_detail_endpoint_and_token_redaction(): void
    {
        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'entity_id' => 999,
            'request_url' => 'https://crm.example.com/api/projects?token=query-secret-token&page=1',
            'request_payload' => [
                'name' => 'Project Phoenix',
                'secret_key' => 'top-secret-12345',
                'bearer_token' => 'super-secret-bearer',
                'meta' => [
                    'auth_token' => 'nested-secret-token',
                    'tag' => 'high-priority',
                ],
            ],
            'response_code' => 400,
            'response_body' => json_encode([
                'error' => 'Invalid token',
                'access_token' => 'returned-sensitive-token',
            ]),
            'status' => DataSyncOutboundHistory::STATUS_FAILED,
            'error_message' => 'HTTP 400 Bad Request: Invalid token provided.',
        ]);

        $response = $this->actingAs($this->authorizedUser)->getJson("/settings/data-sync/outbound/history/{$history->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'history' => [
                'id' => $history->id,
                'connection_name' => 'Test External CRM',
                'module' => 'Projects',
                'operation' => 'Create',
                'http_method' => 'POST',
                'endpoint' => '/projects',
                'response_code' => 400,
                'status' => 'failed',
                'error_message' => 'HTTP 400 Bad Request: Invalid token provided.',
            ],
        ]);

        $content = $response->getContent();

        // Ensure non-sensitive data is present
        $this->assertStringContainsString('Project Phoenix', $content);
        $this->assertStringContainsString('high-priority', $content);

        // Ensure sensitive credentials are redacted
        $this->assertStringNotContainsString('top-secret-12345', $content);
        $this->assertStringNotContainsString('super-secret-bearer', $content);
        $this->assertStringNotContainsString('nested-secret-token', $content);
        $this->assertStringNotContainsString('query-secret-token', $content);
        $this->assertStringNotContainsString('returned-sensitive-token', $content);

        // Ensure REDACTED placeholder is present
        $this->assertStringContainsString('***REDACTED***', $content);
    }

    public function test_unauthorized_user_cannot_access_history_detail(): void
    {
        $history = DataSyncOutboundHistory::create([
            'outbound_api_id' => $this->api->id,
            'request_url' => 'https://crm.example.com/api/projects',
            'status' => DataSyncOutboundHistory::STATUS_SUCCESS,
        ]);

        $response = $this->actingAs($this->unauthorizedUser)->getJson("/settings/data-sync/outbound/history/{$history->id}");
        $response->assertStatus(403);
    }
}
