<?php

namespace Tests\Unit;

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_client_can_be_created_and_casts_attributes_correctly(): void
    {
        $client = ApiClient::create([
            'name' => 'Calendar Integration App',
            'key_id' => 'azo_live_testkey123',
            'secret_hash' => hash('sha256', 'plain_secret_value'),
            'scopes' => [ApiClient::SCOPE_MEETINGS_READ],
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ]);

        $this->assertDatabaseHas('api_clients', [
            'key_id' => 'azo_live_testkey123',
            'is_active' => 1,
        ]);

        $freshClient = ApiClient::find($client->id);

        $this->assertIsArray($freshClient->scopes);
        $this->assertContains('meetings.read', $freshClient->scopes);
        $this->assertTrue($freshClient->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $freshClient->expires_at);
        $this->assertNull($freshClient->last_used_at);
    }

    public function test_has_scope_helper(): void
    {
        $client = new ApiClient([
            'scopes' => [ApiClient::SCOPE_MEETINGS_READ],
        ]);

        $this->assertTrue($client->hasScope(ApiClient::SCOPE_MEETINGS_READ));
        $this->assertTrue($client->hasScope('meetings.read'));
        $this->assertFalse($client->hasScope('meetings.write'));
    }

    public function test_active_scope_query(): void
    {
        ApiClient::create([
            'name' => 'Active Client',
            'key_id' => 'azo_live_active1',
            'secret_hash' => hash('sha256', 'secret1'),
            'is_active' => true,
        ]);

        ApiClient::create([
            'name' => 'Inactive Client',
            'key_id' => 'azo_live_inactive1',
            'secret_hash' => hash('sha256', 'secret2'),
            'is_active' => false,
        ]);

        $activeClients = ApiClient::active()->get();

        $this->assertCount(1, $activeClients);
        $this->assertEquals('azo_live_active1', $activeClients->first()->key_id);
    }

    public function test_soft_deletion(): void
    {
        $client = ApiClient::create([
            'name' => 'Soft Delete Test',
            'key_id' => 'azo_live_softdelete',
            'secret_hash' => hash('sha256', 'secret'),
        ]);

        $client->delete();

        $this->assertSoftDeleted('api_clients', [
            'id' => $client->id,
        ]);

        $this->assertNull(ApiClient::find($client->id));
        $this->assertNotNull(ApiClient::withTrashed()->find($client->id));
    }
}
