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
            'hours' => 2,
            'minutes' => 30,
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
            'new_estimated_time_seconds' => (2 * 3600) + (30 * 60),
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
            'hours' => 2,
            'minutes' => 30,
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

        // Negative values
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'hours' => -1,
            'minutes' => 30,
            'reason' => 'Negative hours'
        ]);
        $response->assertStatus(422);

        // Minutes too high
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'hours' => 1,
            'minutes' => 60,
            'reason' => 'Invalid minutes'
        ]);
        $response->assertStatus(422);

        // Sum is 0
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'hours' => 0,
            'minutes' => 0,
            'reason' => 'Zero sum'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test duplicate pending request prevention.
     */
    public function test_cannot_submit_duplicate_pending_requests()
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

        // Create first request
        TaskExceedTimeRequest::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'estimated_time_seconds' => 3600,
            'new_estimated_time_seconds' => 7200,
            'status' => 'pending'
        ]);

        // Try second request
        $response = $this->actingAs($user)->postJson(route('tasks.exceed-time-requests.store', $task), [
            'hours' => 3,
            'minutes' => 0,
            'reason' => 'Another request'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'There is already a pending estimate change request for this task.'
        ]);
    }
}
