<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskExceedTimeRequest;
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

        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'new_estimated_time_minutes' => 150, // 2 hours 30 mins
            'reason' => 'Need extra time for integration testing.'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Estimate change request submitted successfully.'
        ]);

        $this->assertDatabaseHas('task_exceed_time_requests', [
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

        $response = $this->actingAs($otherUser)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'new_estimated_time_minutes' => 150,
            'reason' => 'Should not be allowed'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Only the task assignee can request an estimate change.'
        ]);

        $this->assertDatabaseMissing('task_exceed_time_requests', [
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
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'new_estimated_time_minutes' => -10,
            'reason' => 'Negative'
        ]);
        $response->assertStatus(422);

        // Sum is 0
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'new_estimated_time_minutes' => 0,
            'reason' => 'Zero'
        ]);
        $response->assertStatus(422);

        // Null value
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
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
        $pendingRequest = TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Original reason'
        ]);

        // Update request
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'new_estimated_time_minutes' => 180, // 3 hours (10800 seconds)
            'reason' => 'Updated reason'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Estimate change request updated successfully.'
        ]);

        // Verify database is updated and no duplicates were created
        $this->assertEquals(1, TaskExceedTimeRequest::where('task_id', $task->id)->count());
        
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
        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Other reason'
        ]);

        // Try to update request as user
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
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
        $approvedRequest = TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'approved',
            'reason' => 'Approved request reason'
        ]);

        // Try to create new request
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
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
        $this->assertEquals(2, TaskExceedTimeRequest::where('task_id', $task->id)->count());
        $this->assertDatabaseHas('task_exceed_time_requests', [
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

        $pendingRequest = TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200, // 120 minutes
            'status' => 'pending',
            'reason' => 'Need more time for testing.'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.exceed-time-requests.pending', $task));

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

        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending',
            'reason' => 'Reason'
        ]);

        $response = $this->actingAs($otherUser)->getJson(route('tasks.exceed-time-requests.pending', $task));

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
        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'approved',
            'reason' => 'Approved'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.exceed-time-requests.pending', $task));

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
        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200, // 120 minutes
            'status' => 'pending',
            'reason' => 'Older request'
        ]);

        // Create newer pending request
        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 10800, // 180 minutes
            'status' => 'pending',
            'reason' => 'Newer request'
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.exceed-time-requests.pending', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'new_estimated_time_minutes' => 180,
                'reason' => 'Newer request'
            ]
        ]);
    }
}
