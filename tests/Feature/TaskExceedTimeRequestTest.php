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

    /**
     * Test unauthorized user cannot access management page.
     */
    public function test_unauthorized_user_cannot_access_management_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.extend-time-requests.index'));

        $response->assertStatus(403);
    }

    /**
     * Test authorized user can access management page and see requests.
     */
    public function test_authorized_user_can_access_management_page()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $user = User::factory()->create();
        $user->givePermissionTo('task_time_extend_request.approve_reject');

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Management Test Task',
            'code' => 'T-MGMT-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time for testing'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.extend-time-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('Management Test Task');
        $response->assertSee('Need more time for testing');
    }

    /**
     * Test management page filters work correctly.
     */
    public function test_management_page_filters()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $user = User::factory()->create();
        $user->givePermissionTo('task_time_extend_request.approve_reject');

        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $task1 = Task::create([
            'project_id' => $project1->id,
            'name' => 'Filter Task Alpha',
            'code' => 'T-FILT-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $task2 = Task::create([
            'project_id' => $project2->id,
            'name' => 'Filter Task Beta',
            'code' => 'T-FILT-2',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $request1 = TaskExtendTimeRequest::create([
            'task_id' => $task1->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Alpha Reason'
        ]);

        $request2 = TaskExtendTimeRequest::create([
            'task_id' => $task2->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Beta Reason'
        ]);

        // Search by task name
        $response = $this->actingAs($user)->get(route('tasks.extend-time-requests.index', ['search' => 'Alpha']));
        $response->assertStatus(200);
        $response->assertSee('Filter Task Alpha');
        $response->assertDontSee('Filter Task Beta');

        // Filter by project
        $response = $this->actingAs($user)->get(route('tasks.extend-time-requests.index', ['project_id' => [$project2->id]]));
        $response->assertStatus(200);
        $response->assertSee('Filter Task Beta');
        $response->assertDontSee('Filter Task Alpha');
    }

    /**
     * Test authorized user can reject request.
     */
    public function test_authorized_user_can_reject_request()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $user = User::factory()->create();
        $user->givePermissionTo('task_time_extend_request.approve_reject');

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Reject Test Task',
            'code' => 'T-REJ-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Reject me'
        ]);

        $response = $this->actingAs($user)->post(route('tasks.extend-time-requests.reject', $request), [
            'reason' => 'Not justified'
        ]);

        $response->assertRedirect(route('tasks.extend-time-requests.index'));

        $this->assertDatabaseHas('task_extend_time_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejected_by' => $user->id,
            'rejection_reason' => 'Not justified'
        ]);

        $this->assertNotNull($request->fresh()->rejected_at);
    }

    /**
     * Test rejection validation reason is required.
     */
    public function test_rejection_validation_reason_required()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $user = User::factory()->create();
        $user->givePermissionTo('task_time_extend_request.approve_reject');

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Validation Test Task',
            'code' => 'T-VAL-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Validation check'
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.extend-time-requests.reject', $request), [
            'reason' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    /**
     * Test notification is sent to original requester on rejection.
     */
    public function test_notification_sent_to_requester_on_rejection()
    {
        \Illuminate\Support\Facades\Notification::fake();

        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();

        // Enable notifications for requester
        \App\Models\UserNotificationSetting::create([
            'user_id' => $requester->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => true,
            'in_app' => true,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Notification Reject Task',
            'code' => 'T-NOT-REJ-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->post(route('tasks.extend-time-requests.reject', $request), [
            'reason' => 'Rejection reason text'
        ]);

        $response->assertRedirect(route('tasks.extend-time-requests.index'));

        // Assert notification sent to requester with correct details
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $requester,
            \App\Notifications\TaskAssignedNotification::class,
            function ($notification, $channels) use ($requester, $manager) {
                $data = $notification->toArray($requester);
                return str_contains($data['message'], 'rejected your time extend request')
                    && str_contains($data['message'], 'Notification Reject Task')
                    && str_contains($data['message'], 'Rejection reason text')
                    && str_contains($data['message'], $manager->name);
            }
        );

        // Assert notification NOT sent to manager or other users
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $manager,
            \App\Notifications\TaskAssignedNotification::class
        );
    }

    /**
     * Test rejection notification is not sent if user has disabled notification setting.
     */
    public function test_rejection_notification_not_sent_if_setting_disabled()
    {
        \Illuminate\Support\Facades\Notification::fake();

        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();

        // Disable notifications for requester
        \App\Models\UserNotificationSetting::create([
            'user_id' => $requester->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => false,
            'in_app' => false,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Notification Reject Task 2',
            'code' => 'T-NOT-REJ-2',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->post(route('tasks.extend-time-requests.reject', $request), [
            'reason' => 'Rejection reason text'
        ]);

        $response->assertRedirect(route('tasks.extend-time-requests.index'));

        // Assert notification NOT sent to requester
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $requester,
            \App\Notifications\TaskAssignedNotification::class
        );
    }

    /**
     * Test unauthorized user cannot view details or approve request.
     */
    public function test_unauthorized_user_cannot_view_details_or_approve_request()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'View Details Task',
            'code' => 'T-VD-1',
            'current_assignee_id' => $user->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $responseGet = $this->actingAs($user)->get(route('tasks.extend-time-requests.show', $request));
        $responseGet->assertStatus(403);

        $responsePost = $this->actingAs($user)->post(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 120
        ]);
        $responsePost->assertStatus(403);
    }

    /**
     * Test authorized user can view request details.
     */
    public function test_authorized_user_can_view_request_details()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Details View Task',
            'code' => 'T-DV-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->getJson(route('tasks.extend-time-requests.show', $request));
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'project_name' => $project->name,
                    'task_name' => 'Details View Task',
                    'user_name' => $requester->name,
                    'new_estimated_time_minutes' => 120,
                ]
            ]);
    }

    /**
     * Test authorized user can approve request and estimated times are updated correctly.
     */
    public function test_authorized_user_can_approve_request()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Approve Task',
            'code' => 'T-APP-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'initial_estimated_time_seconds' => 0
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 180
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $task->refresh();
        $request->refresh();

        $this->assertEquals(180 * 60, $task->estimated_time_seconds);
        $this->assertEquals(3600, $task->initial_estimated_time_seconds); // preserved original

        $this->assertEquals('approved', $request->status);
        $this->assertEquals($manager->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertNull($request->rejected_by);
    }

    /**
     * Test second approval preserves the first initial estimated time.
     */
    public function test_second_approval_does_not_overwrite_initial_estimated_time()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Double Approve Task',
            'code' => 'T-DAPP-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 5400,
            'initial_estimated_time_seconds' => 1800 // already has preserved initial estimate
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 5400,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need even more time'
        ]);

        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 150
        ]);

        $response->assertStatus(200);

        $task->refresh();
        $this->assertEquals(150 * 60, $task->estimated_time_seconds);
        $this->assertEquals(1800, $task->initial_estimated_time_seconds); // should remain unchanged (1800, not 5400)
    }

    /**
     * Test approval validation rules.
     */
    public function test_approve_validation_rules()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Validation Task',
            'code' => 'T-VAL-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        // missing field
        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_estimated_time_minutes']);

        // non-integer
        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 'invalid'
        ]);
        $response->assertStatus(422);

        // zero
        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 0
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test cannot approve non-pending request.
     */
    public function test_cannot_approve_non_pending_request()
    {
        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $requester = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Non Pending Task',
            'code' => 'T-NP-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'approved', // already approved
            'reason' => 'Already done'
        ]);

        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 120
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test notifications are sent to requester and approving user's reporter chain upon approval.
     */
    public function test_approved_notifications_sent_to_requester_and_reporter_chain()
    {
        \Illuminate\Support\Facades\Notification::fake();

        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $reporter = User::factory()->create();
        $requester = User::factory()->create();

        // Setup reporter chain for manager
        \App\Models\UserDetail::create([
            'user_id' => $manager->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $reporter->id,
        ]);

        // Enable notifications for requester and reporter
        \App\Models\UserNotificationSetting::create([
            'user_id' => $requester->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => true,
            'in_app' => true,
        ]);
        \App\Models\UserNotificationSetting::create([
            'user_id' => $reporter->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => true,
            'in_app' => true,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Notification Approve Task',
            'code' => 'T-NOT-APP-1',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 120
        ]);

        $response->assertStatus(200);

        // Assert notification sent to requester
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $requester,
            \App\Notifications\TaskAssignedNotification::class,
            function ($notification, $channels) use ($requester, $project, $task, $manager) {
                $data = $notification->toArray($requester);
                return str_contains($data['message'], 'approved')
                    && str_contains($data['message'], $project->name)
                    && str_contains($data['message'], $task->name)
                    && str_contains($data['message'], '1 Hour')
                    && str_contains($data['message'], '2 Hours')
                    && str_contains($data['message'], $manager->name);
            }
        );

        // Assert notification sent to reporter chain
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $reporter,
            \App\Notifications\TaskAssignedNotification::class,
            function ($notification, $channels) use ($reporter, $project, $task, $requester, $manager) {
                $data = $notification->toArray($reporter);
                return str_contains($data['message'], 'approved')
                    && str_contains($data['message'], $project->name)
                    && str_contains($data['message'], $task->name)
                    && str_contains($data['message'], $requester->name)
                    && str_contains($data['message'], '2 Hours')
                    && str_contains($data['message'], $manager->name);
            }
        );
    }

    /**
     * Test approved notifications respect settings.
     */
    public function test_approved_notifications_respect_settings()
    {
        \Illuminate\Support\Facades\Notification::fake();

        \Spatie\Permission\Models\Permission::findOrCreate('task_time_extend_request.approve_reject');
        $manager = User::factory()->create();
        $manager->givePermissionTo('task_time_extend_request.approve_reject');

        $reporter = User::factory()->create();
        $requester = User::factory()->create();

        // Setup reporter chain for manager
        \App\Models\UserDetail::create([
            'user_id' => $manager->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $reporter->id,
        ]);

        // Disable notifications for requester and reporter
        \App\Models\UserNotificationSetting::create([
            'user_id' => $requester->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => false,
            'in_app' => false,
        ]);
        \App\Models\UserNotificationSetting::create([
            'user_id' => $reporter->id,
            'action' => \App\Models\UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            'mail' => false,
            'in_app' => false,
        ]);

        $project = Project::factory()->create();
        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Notification Approve Task 2',
            'code' => 'T-NOT-APP-2',
            'current_assignee_id' => $requester->id,
            'estimated_time_seconds' => 3600
        ]);

        $request = TaskExtendTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $requester->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Need more time'
        ]);

        $response = $this->actingAs($manager)->postJson(route('tasks.extend-time-requests.approve', $request), [
            'new_estimated_time_minutes' => 120
        ]);

        $response->assertStatus(200);

        // Assert notification NOT sent to requester
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $requester,
            \App\Notifications\TaskAssignedNotification::class
        );

        // Assert notification NOT sent to reporter
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $reporter,
            \App\Notifications\TaskAssignedNotification::class
        );
    }

    /**
     * Test running timer is automatically stopped when assignee changes.
     */
    public function test_timer_stopped_automatically_when_assignee_changes()
    {
        $actor = User::factory()->create();
        $oldAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'Timer Test Task',
            'code' => 'T-TIMER-1',
            'current_assignee_id' => $oldAssignee->id,
            'estimated_time_seconds' => 3600,
        ]);

        $assignmentLog = \App\Models\TaskAssignmentLog::create([
            'task_id' => $task->id,
            'user_id' => $oldAssignee->id,
            'assigned_from' => now()->subHours(2),
            'is_current' => true,
        ]);

        $startedAt = now()->subMinutes(30);
        $timeLog = \App\Models\TaskTimeLog::create([
            'task_id' => $task->id,
            'user_id' => $oldAssignee->id,
            'task_assignment_log_id' => $assignmentLog->id,
            'started_at' => $startedAt,
            'is_running' => 1,
            'is_approved' => true,
        ]);

        $validated = [
            'name' => 'Timer Test Task Updated',
            'task_type_id' => $task->task_type_id,
            'task_mode_id' => $task->task_mode_id,
            'priority' => 'medium',
            'current_assignee_id' => $newAssignee->id,
            'estimated_time_minutes' => 60,
        ];

        $service = app(\App\Services\TaskServices::class);

        $this->actingAs($actor);

        $updatedTask = $service->updateTask($task, $validated);

        $this->assertEquals($newAssignee->id, $updatedTask->current_assignee_id);

        $timeLog->refresh();
        $this->assertEquals(0, $timeLog->is_running);
        $this->assertNotNull($timeLog->ended_at);
        $this->assertGreaterThan(0, $timeLog->duration_seconds);

        $assignmentLog->refresh();
        $this->assertGreaterThan(0, $assignmentLog->worked_time_seconds);
        $this->assertEquals($timeLog->duration_seconds, $assignmentLog->worked_time_seconds);

        $this->assertEquals($timeLog->duration_seconds, $updatedTask->actual_time_seconds);
    }

    /**
     * Test assignee changes with no running timer.
     */
    public function test_assignee_changes_with_no_running_timer()
    {
        $actor = User::factory()->create();
        $oldAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();
        $project = Project::factory()->create();

        $task = Task::create([
            'project_id' => $project->id,
            'name' => 'No Timer Test Task',
            'code' => 'T-TIMER-2',
            'current_assignee_id' => $oldAssignee->id,
            'estimated_time_seconds' => 3600,
        ]);

        $validated = [
            'name' => 'No Timer Test Task Updated',
            'task_type_id' => $task->task_type_id,
            'task_mode_id' => $task->task_mode_id,
            'priority' => 'medium',
            'current_assignee_id' => $newAssignee->id,
            'estimated_time_minutes' => 60,
        ];

        $service = app(\App\Services\TaskServices::class);

        $this->actingAs($actor);

        $updatedTask = $service->updateTask($task, $validated);

        $this->assertEquals($newAssignee->id, $updatedTask->current_assignee_id);

        $this->assertEquals(0, \App\Models\TaskTimeLog::where('task_id', $task->id)->count());
    }
}
