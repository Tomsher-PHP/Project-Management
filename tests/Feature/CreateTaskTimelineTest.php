<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTimeline;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTaskTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($this->user);
    }

    public function test_task_create_uses_the_selected_choosable_timeline(): void
    {
        $project = $this->createProject();
        $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $completedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');

        $response = $this->postJson(route('tasks.store'), $this->validPayload($project, [
            'project_timeline_id' => $completedTimeline->id,
        ]));

        $response->assertOk()->assertJson(['status' => true]);

        $task = Task::query()->findOrFail($response->json('task_id'));
        $this->assertSame($completedTimeline->id, $task->project_timeline_id);
    }

    public function test_task_create_rejects_a_timeline_that_is_not_choosable(): void
    {
        $project = $this->createProject();
        $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $plannedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_PLANNED, 'Planned Timeline');

        $this->postJson(route('tasks.store'), $this->validPayload($project, [
            'project_timeline_id' => $plannedTimeline->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_timeline_id');
    }

    public function test_task_create_defaults_to_the_project_display_timeline(): void
    {
        $project = $this->createProject();
        $activeTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');

        $response = $this->postJson(route('tasks.store'), $this->validPayload($project));

        $response->assertOk()->assertJson(['status' => true]);

        $task = Task::query()->findOrFail($response->json('task_id'));
        $this->assertSame($activeTimeline->id, $task->project_timeline_id);
    }

    public function test_task_request_ignores_a_submitted_timeline(): void
    {
        $project = $this->createProject();
        $project->membersAll()->attach($this->user->id);
        $activeTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $completedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');

        $response = $this->postJson(route('tasks.request.store'), $this->validPayload($project, [
            'request_type' => Task::REQUEST_TYPE_SELF,
            'project_timeline_id' => $completedTimeline->id,
        ]));

        $response->assertOk()->assertJson(['status' => true]);

        $task = Task::query()->findOrFail($response->json('task_id'));
        $this->assertSame($activeTimeline->id, $task->project_timeline_id);
        $this->assertSame(Task::REQUEST_TYPE_SELF, $task->request_type);
    }

    private function createProject(): Project
    {
        return Project::factory()->create([
            'project_flow' => 'linear',
            'added_by' => $this->user->id,
            'is_system' => false,
        ]);
    }

    private function createTimeline(Project $project, int $status, string $name): ProjectTimeline
    {
        return ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => $name,
            'type' => ProjectTimeline::DEFAULT_TYPE,
            'status' => $status,
            'sort_order' => ((int) $project->projectTimelines()->max('sort_order')) + 1,
            'created_by' => $this->user->id,
        ]);
    }

    private function validPayload(Project $project, array $overrides = []): array
    {
        return array_merge([
            'project_id' => $project->id,
            'request_type' => Task::REQUEST_TYPE_ASSIGNED,
            'name' => 'Timeline task',
            'due_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
        ], $overrides);
    }
}
