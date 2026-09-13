<?php

namespace Tests\Feature;

use App\Http\Resources\MeetingResource;
use App\Models\ApiClient;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\MeetingParticipant;
use App\Models\MeetingStatus;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use App\Models\Project;
use App\Models\User;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActiveMeetingsApiStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_meetings_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_active_meetings_endpoint_rejects_invalid_api_key(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer azo_live_invalid.azo_sec_invalid')
            ->getJson('/api/meetings/active');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_active_meetings_endpoint_returns_403_forbidden_if_client_lacks_meetings_read_scope(): void
    {
        $keyId = 'azo_live_noscope123';
        $secret = 'azo_sec_' . \Illuminate\Support\Str::random(40);
        $plainTextKey = $keyId . '.' . $secret;

        ApiClient::create([
            'name' => 'No Scope Client',
            'key_id' => $keyId,
            'secret_hash' => hash('sha256', $plainTextKey),
            'scopes' => [], // Empty scope
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden.',
            ]);
    }

    public function test_active_meetings_endpoint_returns_200_ok_with_valid_client_and_scope(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Valid Client', [ApiClient::SCOPE_MEETINGS_READ]);
        $plainTextKey = $result['plain_text_key'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_active_meetings_query_filters_scheduled_and_current_meetings(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Test Client', [ApiClient::SCOPE_MEETINGS_READ]);
        $plainTextKey = $result['plain_text_key'];

        $scheduledStatus = MeetingStatus::create(['name' => 'Scheduled', 'code' => 'scheduled', 'color' => '#10B981', 'type' => 'open', 'is_active' => true]);
        $rescheduledStatus = MeetingStatus::create(['name' => 'Rescheduled', 'code' => 'rescheduled', 'color' => '#F59E0B', 'type' => 'rescheduled', 'is_active' => true]);
        $completedStatus = MeetingStatus::create(['name' => 'Completed', 'code' => 'completed', 'color' => '#6B7280', 'type' => 'completed', 'is_active' => true]);
        $cancelledStatus = MeetingStatus::create(['name' => 'Cancelled', 'code' => 'cancelled', 'color' => '#EF4444', 'type' => 'cancelled', 'is_active' => true]);
        $inProgressStatus = MeetingStatus::create(['name' => 'In Progress', 'code' => 'in_progress', 'color' => '#3B82F6', 'type' => 'in_progress', 'is_active' => true]);

        $type = MeetingType::create(['name' => 'General', 'is_active' => true]);

        // 1. Scheduled future meeting -> SHOULD BE RETURNED
        $futureMeeting = Meeting::create([
            'title' => 'Future Scheduled Meeting',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // 2. Scheduled currently running meeting -> SHOULD BE RETURNED
        $runningMeeting = Meeting::create([
            'title' => 'Currently Running Scheduled Meeting',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->subMinutes(15),
            'end_at' => now()->addMinutes(45),
        ]);

        // 3. Scheduled ended meeting (end_at in past) -> SHOULD NOT BE RETURNED
        Meeting::create([
            'title' => 'Past Scheduled Meeting',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->subHours(3),
            'end_at' => now()->subHour(),
        ]);

        // 4. Rescheduled meeting -> SHOULD NOT BE RETURNED
        Meeting::create([
            'title' => 'Rescheduled Meeting',
            'meeting_status_id' => $rescheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // 5. Completed meeting -> SHOULD NOT BE RETURNED
        Meeting::create([
            'title' => 'Completed Meeting',
            'meeting_status_id' => $completedStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // 6. Cancelled meeting -> SHOULD NOT BE RETURNED
        Meeting::create([
            'title' => 'Cancelled Meeting',
            'meeting_status_id' => $cancelledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // 7. In Progress meeting -> SHOULD NOT BE RETURNED
        Meeting::create([
            'title' => 'In Progress Meeting',
            'meeting_status_id' => $inProgressStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Exactly 2 meetings returned: runningMeeting (start_at earlier) and futureMeeting (start_at later)
        $this->assertCount(2, $data);
        $this->assertEquals($runningMeeting->id, $data[0]['id']);
        $this->assertEquals('Currently Running Scheduled Meeting', $data[0]['title']);
        $this->assertEquals($futureMeeting->id, $data[1]['id']);
        $this->assertEquals('Future Scheduled Meeting', $data[1]['title']);
    }

    public function test_reschedule_chain_returns_only_active_scheduled_meeting(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Test Client', [ApiClient::SCOPE_MEETINGS_READ]);
        $plainTextKey = $result['plain_text_key'];

        $scheduledStatus = MeetingStatus::create(['name' => 'Scheduled', 'code' => 'scheduled', 'color' => '#10B981', 'type' => 'open', 'is_active' => true]);
        $rescheduledStatus = MeetingStatus::create(['name' => 'Rescheduled', 'code' => 'rescheduled', 'color' => '#F59E0B', 'type' => 'rescheduled', 'is_active' => true]);
        $type = MeetingType::create(['name' => 'General', 'is_active' => true]);

        // Meeting #1: Rescheduled
        $m1 = Meeting::create([
            'title' => 'Original Meeting #1',
            'meeting_status_id' => $rescheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // Meeting #2: Rescheduled from #1
        $m2 = Meeting::create([
            'title' => 'Second Meeting #2',
            'meeting_status_id' => $rescheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHours(3),
            'end_at' => now()->addHours(4),
            'rescheduled_from_id' => $m1->id,
        ]);

        // Meeting #3: Scheduled from #2 -> SHOULD BE THE ONLY ONE RETURNED
        $m3 = Meeting::create([
            'title' => 'Final Active Scheduled Meeting #3',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => now()->addHours(5),
            'end_at' => now()->addHours(6),
            'rescheduled_from_id' => $m2->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($m3->id, $data[0]['id']);
        $this->assertEquals('Final Active Scheduled Meeting #3', $data[0]['title']);
        $this->assertEquals($m2->id, $data[0]['rescheduled_from_id']);
    }

    public function test_multiple_active_meetings_ordered_chronologically_by_start_at_asc_and_id_asc(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Test Client', [ApiClient::SCOPE_MEETINGS_READ]);
        $plainTextKey = $result['plain_text_key'];

        $scheduledStatus = MeetingStatus::create(['name' => 'Scheduled', 'code' => 'scheduled', 'color' => '#10B981', 'type' => 'open', 'is_active' => true]);
        $type = MeetingType::create(['name' => 'General', 'is_active' => true]);

        $startLater = now()->addDays(2);
        $startEarlier = now()->addDay();

        $mLater = Meeting::create([
            'title' => 'Meeting A (Later)',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => $startLater,
            'end_at' => $startLater->copy()->addHour(),
        ]);

        $mEarlier1 = Meeting::create([
            'title' => 'Meeting B (Earlier First)',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => $startEarlier,
            'end_at' => $startEarlier->copy()->addHour(),
        ]);

        $mEarlier2 = Meeting::create([
            'title' => 'Meeting C (Earlier Second - Same Time)',
            'meeting_status_id' => $scheduledStatus->id,
            'meeting_type_id' => $type->id,
            'start_at' => $startEarlier,
            'end_at' => $startEarlier->copy()->addHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Expected order: B (earlier, lower ID), C (earlier, higher ID), A (later)
        $this->assertEquals($mEarlier1->id, $data[0]['id']);
        $this->assertEquals($mEarlier2->id, $data[1]['id']);
        $this->assertEquals($mLater->id, $data[2]['id']);
    }

    public function test_meeting_resource_serialization_structure_and_security_exposure(): void
    {
        $organizer = User::factory()->create([
            'name' => 'Jane Organizer',
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
            'remember_token' => 'secret_token_val',
        ]);

        $project = Project::factory()->create([
            'name' => 'Alpha Project',
        ]);

        $status = MeetingStatus::create([
            'name' => 'Scheduled',
            'code' => 'scheduled',
            'color' => '#10B981',
            'type' => 'open',
            'is_active' => true,
        ]);

        $type = MeetingType::create([
            'name' => 'Client Review',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $location = MeetingLocation::create([
            'name' => 'Google Meet',
            'is_active' => true,
        ]);

        $meeting = Meeting::create([
            'project_id' => $project->id,
            'meeting_type_id' => $type->id,
            'meeting_location_id' => $location->id,
            'meeting_status_id' => $status->id,
            'organizer_id' => $organizer->id,
            'title' => 'Project Kickoff Meeting',
            'description' => 'Discuss project scope and deliverables.',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'url' => 'https://meet.google.com/abc-defg-hij',
            'location_details' => 'Room 301',
            'rescheduled_from_id' => null,
        ]);

        $participantUser = User::factory()->create([
            'name' => 'John Participant',
            'email' => 'john@example.com',
        ]);

        // Internal participant
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $participantUser->id,
            'is_external' => false,
            'name' => $participantUser->name,
            'email' => $participantUser->email,
        ]);

        // External participant (user_id = null)
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => null,
            'is_external' => true,
            'name' => 'External Vendor',
            'email' => 'vendor@external.com',
        ]);

        $tag = MeetingTag::create([
            'name' => 'Important',
            'color' => '#EF4444',
            'is_active' => true,
        ]);

        $meeting->tags()->attach($tag->id);

        $service = app(ApiClientService::class);
        $result = $service->createClient('Contract Client', [ApiClient::SCOPE_MEETINGS_READ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $result['plain_text_key'])
            ->getJson('/api/meetings/active');

        $response->assertStatus(200);

        $jsonContent = $response->getContent();

        // HTTP response sensitive data exposure assertions
        $this->assertStringNotContainsString('password', $jsonContent);
        $this->assertStringNotContainsString('remember_token', $jsonContent);
        $this->assertStringNotContainsString('password_otp', $jsonContent);
        $this->assertStringNotContainsString('secret_hash', $jsonContent);
        $this->assertStringNotContainsString('permissions', $jsonContent);
        $this->assertStringNotContainsString('roles', $jsonContent);

        $data = $response->json('data.0');

        $this->assertEquals($meeting->id, $data['id']);
        $this->assertEquals('Project Kickoff Meeting', $data['title']);
        $this->assertEquals('Discuss project scope and deliverables.', $data['description']);
        $this->assertEquals('https://meet.google.com/abc-defg-hij', $data['url']);
        $this->assertEquals('Room 301', $data['location_details']);

        // Check Status
        $this->assertEquals('scheduled', $data['status']['code']);
        $this->assertEquals('Scheduled', $data['status']['name']);

        // Check Type
        $this->assertEquals('Client Review', $data['meeting_type']['name']);

        // Check Location
        $this->assertEquals('Google Meet', $data['location']['name']);

        // Check Project
        $this->assertEquals($project->id, $data['project']['id']);
        $this->assertEquals('Alpha Project', $data['project']['name']);

        // Check Organizer
        $this->assertEquals($organizer->id, $data['organizer']['id']);
        $this->assertEquals('Jane Organizer', $data['organizer']['name']);
        $this->assertEquals('jane@example.com', $data['organizer']['email']);

        // Check Participants (Internal & External)
        $this->assertCount(2, $data['participants']);
        $this->assertEquals($participantUser->id, $data['participants'][0]['user_id']);
        $this->assertFalse($data['participants'][0]['is_external']);
        $this->assertNull($data['participants'][1]['user_id']);
        $this->assertTrue($data['participants'][1]['is_external']);
        $this->assertEquals('External Vendor', $data['participants'][1]['name']);
    }

    public function test_meeting_resource_handles_nullable_relations_safely(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('Null Test Client', [ApiClient::SCOPE_MEETINGS_READ]);

        $status = MeetingStatus::create(['name' => 'Scheduled', 'code' => 'scheduled', 'color' => '#10B981', 'type' => 'open', 'is_active' => true]);
        $type = MeetingType::create(['name' => 'General', 'is_active' => true]);

        // Meeting with null project, location, url, location_details
        Meeting::create([
            'project_id' => null,
            'meeting_type_id' => $type->id,
            'meeting_location_id' => null,
            'meeting_status_id' => $status->id,
            'organizer_id' => null,
            'title' => 'Standalone Meeting',
            'description' => null,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'url' => null,
            'location_details' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $result['plain_text_key'])
            ->getJson('/api/meetings/active');

        $response->assertStatus(200);

        $data = $response->json('data.0');
        $this->assertNull($data['project']);
        $this->assertNull($data['location']);
        $this->assertNull($data['organizer']);
        $this->assertNull($data['url']);
        $this->assertNull($data['location_details']);
        $this->assertEmpty($data['participants']);
        $this->assertEmpty($data['tags']);
    }

    public function test_eager_loading_prevents_n_plus_one_queries(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->createClient('N Plus One Test Client', [ApiClient::SCOPE_MEETINGS_READ]);
        $plainTextKey = $result['plain_text_key'];

        $status = MeetingStatus::create(['name' => 'Scheduled', 'code' => 'scheduled', 'color' => '#10B981', 'type' => 'open', 'is_active' => true]);
        $type = MeetingType::create(['name' => 'General', 'is_active' => true]);

        // Create 5 meetings each with participants and tags
        for ($i = 1; $i <= 5; $i++) {
            $m = Meeting::create([
                'title' => "Meeting #{$i}",
                'meeting_status_id' => $status->id,
                'meeting_type_id' => $type->id,
                'start_at' => now()->addHours($i),
                'end_at' => now()->addHours($i + 1),
            ]);

            $pUser = User::factory()->create();
            MeetingParticipant::create([
                'meeting_id' => $m->id,
                'user_id' => $pUser->id,
                'name' => $pUser->name,
                'email' => $pUser->email,
            ]);
        }

        DB::enableQueryLog();

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextKey)
            ->getJson('/api/meetings/active');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));

        // Query count should be small and bounded (authentication + client update + meeting query with eager loading)
        // Definitely far below N+1 scale (5 meetings * 8 relations = 40 queries)
        $this->assertLessThan(15, $queryCount);
    }
}
