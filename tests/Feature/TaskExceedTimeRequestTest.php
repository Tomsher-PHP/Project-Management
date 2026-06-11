<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskExtendTimeRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskExceedTimeRequestTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test assignee can submit request successfully.
     */
    public function test_assignee_can_submit_estimate_change_request()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600 // 1 hour
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 150, // 2 hours 30 mins
            'reason' => 'Need extra time for integration testing.'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.'
        ]);

        $this->assertDatabaseHas('task_extend_time_requests', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 150 * 60,
            'status' => 'pending',
            'reason' => 'Need extra time for integration testing.'
        ]);
    }

    /**
     * Test non-assignee cannot submit request.
     */
    public function test_non_assignee_cannot_submit_estimate_change_request()
    {
        $assignee = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-2',
            'current_assignee_id' => $assignee->id,
            'estimated_time_seconds' => 3600
        ]);

        $response = $this->actingAs($otherUser)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 150,
            'reason' => 'Should not be allowed'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Only the task assignee can request an estimate change.'
        ]);

        $this->assertDatabaseMissing('task_extend_time_requests', [
            'task_id' => $task->id
        ]);
    }

    /**
     * Test validation rules for invalid values.
     */
    public function test_cannot_submit_invalid_time_values()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-3',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Negative value
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => -10,
            'reason' => 'Negative'
        ]);
        $response->assertStatus(422);

        // Sum is 0
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 0,
            'reason' => 'Zero'
        ]);
        $response->assertStatus(422);

        // Null value
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => null,
            'reason' => 'Null value'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test editing an existing pending request.
     */
    public function test_assignee_can_update_their_own_pending_request()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-4',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Pre-create pending request
        $pendingRequest = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Original reason'
        ]);

        // Update request
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 180, // 3 hours (10800 seconds)
            'reason' => 'Updated reason'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Estimate change request updated successfully.'
        ]);

        // Verify database is updated and no duplicates were created
        $this->assertEquals(1, TaskExtendTimeRequest::where('task_id', $task->id)->count());

        $pendingRequest->refresh();
        $this->assertEquals(180 * 60, $pendingRequest->new_estimated_time_seconds);
        $this->assertEquals('Updated reason', $pendingRequest->reason);
    }

    /**
     * Test assignee cannot edit someone else's pending request.
     */
    public function test_assignee_cannot_update_someone_elses_pending_request()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-5',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Pre-create pending request under otherUser
        TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Other reason'
        ]);

        // Try to update request as user
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 180,
            'reason' => 'Try updating'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Only the original requester can edit this pending request.'
        ]);
    }

    /**
     * Test approved requests are not modified (creates a new request instead).
     */
    public function test_approved_requests_are_not_modified()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-6',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Pre-create approved request
        $approvedRequest = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'approved',
            'reason' => 'Approved request reason'
        ]);

        // Try to create new request
        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 180,
            'reason' => 'New pending reason'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.'
        ]);

        // Approved request should remain unchanged
        $approvedRequest->refresh();
        $this->assertEquals(7200, $approvedRequest->new_estimated_time_seconds);
        $this->assertEquals('approved', $approvedRequest->status);

        // A new pending request should be created
        $this->assertEquals(2, TaskExtendTimeRequest::where('task_id', $task->id)->count());
        $this->assertDatabaseHas('task_extend_time_requests', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'new_estimated_time_seconds' => 180 * 60,
            'status' => 'pending',
            'reason' => 'New pending reason'
        ]);
    }

    /**
     * Test assignee can get pending request details successfully.
     */
    public function test_assignee_can_get_pending_request_details()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-GET-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $pendingRequest = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200, // 120 minutes
            'status' => 'pending',
            'reason' => 'Need more time for testing.'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.extend-time-requests.pending', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'new_estimated_time_minutes' => 120,
                'reason' => 'Need more time for testing.'
            ]
        ]);
    }

    /**
     * Test non-assignee cannot get pending request details.
     */
    public function test_non_assignee_cannot_get_pending_request_details()
    {
        $assignee = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-GET-2',
            'current_assignee_id' => $assignee->id,
            'estimated_time_seconds' => 3600
        ]);

        TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Reason'
        ]);

        $response = $this->actingAs($otherUser)->getJson(route('tasks.extend-time-requests.pending', $task));

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Only the task assignee can request an estimate change.'
        ]);
    }

    /**
     * Test get pending request returns null if no pending request exists.
     */
    public function test_get_pending_request_returns_null_if_no_pending_request()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-GET-3',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Create an approved request (not pending)
        TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'approved',
            'reason' => 'Approved'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.extend-time-requests.pending', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => null
        ]);
    }

    /**
     * Test get pending request returns the newest pending request if multiple exist.
     */
    public function test_get_pending_request_returns_newest_pending_request()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'code' => 'T-GET-4',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        // Create older pending request
        TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200, // 120 minutes
            'status' => 'pending',
            'reason' => 'Older request'
        ]);

        // Create newer pending request
        TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 10800, // 180 minutes
            'status' => 'pending',
            'reason' => 'Newer request'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.extend-time-requests.pending', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'new_estimated_time_minutes' => 180,
                'reason' => 'Newer request'
            ]
        ]);
    }

    /**
     * Test notification is dispatched to reporter chain when time extend request is created or updated.
     */
    public function test_notification_dispatched_to_reporter_chain_on_create_and_update()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $reporter = User::factory()->create();
        $assignee = User::factory()->create();

        // Setup user details so reporter is assignee's manager/reporter
        \App\Models\UserDetail::create([
            'user_id' => $assignee->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $reporter->id,
        ]);

        // Enable notifications for reporter
        \App\Models\UserNotificationSetting::create([
            'user_id' => $reporter->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => true,
            'in_app' => true,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Notification Task',
            'code' => 'T-NOTIF-1',
            'current_assignee_id' => $assignee->id,
            'estimated_time_seconds' => 3600
        ]);

        // 1. Test notification on creation
        $response = $this->actingAs($assignee)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 120,
            'reason' => 'Need time'
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $reporter,
            \App\Notifications\TaskAssignedNotification::class,
            function ($notification, $channels) use ($reporter) {
                return str_contains($notification->toArray($reporter)['message'], 'requested to extend time for task');
            }
        );

        // Reset notification fake
        \Illuminate\Support\Facades\Notification::fake();

        // 2. Test notification on update
        $response = $this->actingAs($assignee)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 150,
            'reason' => 'Need more time'
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $reporter,
            \App\Notifications\TaskAssignedNotification::class
        );
    }

    /**
     * Test notification is NOT dispatched if settings are disabled.
     */
    public function test_notification_not_dispatched_if_setting_disabled()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $reporter = User::factory()->create();
        $assignee = User::factory()->create();

        // Setup user details so reporter is assignee's manager/reporter
        \App\Models\UserDetail::create([
            'user_id' => $assignee->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $reporter->id,
        ]);

        // Disable notifications for reporter
        \App\Models\UserNotificationSetting::create([
            'user_id' => $reporter->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => false,
            'in_app' => false,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Test Notification Task 2',
            'code' => 'T-NOTIF-2',
            'current_assignee_id' => $assignee->id,
            'estimated_time_seconds' => 3600
        ]);

        $response = $this->actingAs($assignee)->postJson(route('tasks.extend-time-requests.store', $task), [
            'new_estimated_time_minutes' => 120,
            'reason' => 'Need time'
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $reporter,
            \App\Notifications\TaskAssignedNotification::class
        );
    }
}
