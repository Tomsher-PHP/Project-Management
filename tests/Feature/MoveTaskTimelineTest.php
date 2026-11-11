<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\ProjectTimeline;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveTaskTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($this->user);
    }

    public function test_linear_task_can_move_to_another_timeline_without_sprint_changes(): void
    {
        $project = $this->createProject('linear');
        $activeTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $completedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');
        $task = $this->createTask($project, $activeTimeline);
        $childTask = $this->createTask($project, $activeTimeline, ['parent_task_id' => $task->id]);

        $response = $this->patchJson(route('projects.tasks.move', [$project, $task]), [
            'project_timeline_id' => $completedTimeline->id,
        ]);

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertSame($completedTimeline->id, $task->fresh()->project_timeline_id);
        $this->assertSame($completedTimeline->id, $childTask->fresh()->project_timeline_id);
        $this->assertNull($task->fresh()->project_sprint_id);
        $this->assertNull($childTask->fresh()->project_sprint_id);
    }

    public function test_agile_sprint_only_move_preserves_existing_timeline_behavior(): void
    {
        $project = $this->createProject('agile');
        $timeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        [$sourceMilestone, $sourceSprint] = $this->createPlacement($project, 'Source');
        [$targetMilestone, $targetSprint] = $this->createPlacement($project, 'Target');
        $task = $this->createTask($project, $timeline, [
            'project_milestone_id' => $sourceMilestone->id,
            'project_sprint_id' => $sourceSprint->id,
        ]);
        $childTask = $this->createTask($project, $timeline, [
            'parent_task_id' => $task->id,
            'project_milestone_id' => $sourceMilestone->id,
            'project_sprint_id' => $sourceSprint->id,
        ]);

        $response = $this->patchJson(route('projects.tasks.move', [$project, $task]), [
            'project_timeline_id' => $timeline->id,
            'project_sprint_id' => $targetSprint->id,
        ]);

        $response->assertOk()->assertJson(['status' => true]);

        foreach ([$task->fresh(), $childTask->fresh()] as $movedTask) {
            $this->assertSame($timeline->id, $movedTask->project_timeline_id);
            $this->assertSame($targetMilestone->id, $movedTask->project_milestone_id);
            $this->assertSame($targetSprint->id, $movedTask->project_sprint_id);
        }
    }

    public function test_agile_task_can_move_timeline_and_sprint_together(): void
    {
        $project = $this->createProject('agile');
        $sourceTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $targetTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');
        [$sourceMilestone, $sourceSprint] = $this->createPlacement($project, 'Source');
        [$targetMilestone, $targetSprint] = $this->createPlacement($project, 'Target');
        $task = $this->createTask($project, $sourceTimeline, [
            'project_milestone_id' => $sourceMilestone->id,
            'project_sprint_id' => $sourceSprint->id,
        ]);
        $childTask = $this->createTask($project, $sourceTimeline, [
            'parent_task_id' => $task->id,
            'project_milestone_id' => $sourceMilestone->id,
            'project_sprint_id' => $sourceSprint->id,
        ]);

        $response = $this->patchJson(route('projects.tasks.move', [$project, $task]), [
            'project_timeline_id' => $targetTimeline->id,
            'project_sprint_id' => $targetSprint->id,
        ]);

        $response->assertOk()->assertJson(['status' => true]);

        foreach ([$task->fresh(), $childTask->fresh()] as $movedTask) {
            $this->assertSame($targetTimeline->id, $movedTask->project_timeline_id);
            $this->assertSame($targetMilestone->id, $movedTask->project_milestone_id);
            $this->assertSame($targetSprint->id, $movedTask->project_sprint_id);
        }
    }

    public function test_cross_project_move_uses_selected_timeline_instead_of_display_timeline(): void
    {
        $sourceProject = $this->createProject('linear');
        $targetProject = $this->createProject('linear');
        $sourceTimeline = $this->createTimeline($sourceProject, ProjectTimeline::STATUS_ACTIVE, 'Source Timeline');
        $displayTimeline = $this->createTimeline($targetProject, ProjectTimeline::STATUS_ACTIVE, 'Target Active');
        $selectedTimeline = $this->createTimeline($targetProject, ProjectTimeline::STATUS_COMPLETED, 'Target Completed');
        $task = $this->createTask($sourceProject, $sourceTimeline);
        $childTask = $this->createTask($sourceProject, $sourceTimeline, ['parent_task_id' => $task->id]);

        $this->assertSame($displayTimeline->id, $targetProject->fresh()->display_timeline?->id);

        $response = $this->patchJson(route('projects.tasks.move', [$sourceProject, $task]), [
            'move_to_another_project' => true,
            'target_project_id' => $targetProject->id,
            'project_timeline_id' => $selectedTimeline->id,
        ]);

        $response->assertOk()->assertJson(['status' => true]);

        foreach ([$task->fresh(), $childTask->fresh()] as $movedTask) {
            $this->assertSame($targetProject->id, $movedTask->project_id);
            $this->assertSame($selectedTimeline->id, $movedTask->project_timeline_id);
            $this->assertNull($movedTask->project_sprint_id);
            $this->assertNull($movedTask->project_milestone_id);
        }
    }

    public function test_cross_project_move_preserves_all_agile_and_linear_flow_combinations(): void
    {
        foreach ([
            ['agile', 'agile'],
            ['agile', 'linear'],
            ['linear', 'agile'],
            ['linear', 'linear'],
        ] as [$sourceFlow, $targetFlow]) {
            $sourceProject = $this->createProject($sourceFlow);
            $targetProject = $this->createProject($targetFlow);
            $sourceTimeline = $this->createTimeline($sourceProject, ProjectTimeline::STATUS_ACTIVE, "{$sourceFlow} Source");
            $this->createTimeline($targetProject, ProjectTimeline::STATUS_ACTIVE, "{$targetFlow} Default");
            $selectedTimeline = $this->createTimeline($targetProject, ProjectTimeline::STATUS_COMPLETED, "{$targetFlow} Selected");
            $task = $this->createTask($sourceProject, $sourceTimeline);

            $response = $this->patchJson(route('projects.tasks.move', [$sourceProject, $task]), [
                'move_to_another_project' => true,
                'target_project_id' => $targetProject->id,
                'project_timeline_id' => $selectedTimeline->id,
            ]);

            $response->assertOk()->assertJson(['status' => true]);
            $movedTask = $task->fresh();
            $this->assertSame($targetProject->id, $movedTask->project_id);
            $this->assertSame($selectedTimeline->id, $movedTask->project_timeline_id);

            if ($targetFlow === 'agile') {
                $this->assertNotNull($movedTask->project_milestone_id);
                $this->assertNotNull($movedTask->project_sprint_id);
            } else {
                $this->assertNull($movedTask->project_milestone_id);
                $this->assertNull($movedTask->project_sprint_id);
            }
        }
    }

    public function test_move_rejects_ineligible_foreign_deleted_and_missing_timelines(): void
    {
        $project = $this->createProject('linear');
        $otherProject = $this->createProject('linear');
        $currentTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Current Timeline');
        $plannedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_PLANNED, 'Planned Timeline');
        $cancelledTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_CANCELLED, 'Cancelled Timeline');
        $foreignTimeline = $this->createTimeline($otherProject, ProjectTimeline::STATUS_ACTIVE, 'Foreign Timeline');
        $deletedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Deleted Timeline');
        $deletedTimeline->delete();
        $task = $this->createTask($project, $currentTimeline);

        foreach ([
            $plannedTimeline->id,
            $cancelledTimeline->id,
            $foreignTimeline->id,
            $deletedTimeline->id,
            999999,
        ] as $invalidTimelineId) {
            $this->patchJson(route('projects.tasks.move', [$project, $task]), [
                'project_timeline_id' => $invalidTimelineId,
            ])->assertUnprocessable()->assertJsonValidationErrors('project_timeline_id');
        }

        $this->assertSame($currentTimeline->id, $task->fresh()->project_timeline_id);
    }

    public function test_same_project_move_rejects_submission_when_nothing_changes(): void
    {
        $project = $this->createProject('linear');
        $timeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $task = $this->createTask($project, $timeline);

        $this->patchJson(route('projects.tasks.move', [$project, $task]), [
            'project_timeline_id' => $timeline->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('project_timeline_id');
    }

    public function test_project_dependencies_only_include_eligible_timelines_and_default_display_timeline(): void
    {
        $project = $this->createProject('linear');
        $activeTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_ACTIVE, 'Active Timeline');
        $completedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_COMPLETED, 'Completed Timeline');
        $plannedTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_PLANNED, 'Planned Timeline');
        $cancelledTimeline = $this->createTimeline($project, ProjectTimeline::STATUS_CANCELLED, 'Cancelled Timeline');

        $response = $this->getJson(route('projects.task-create-dependencies', $project));

        $response->assertOk()->assertJsonPath('data.default_timeline_id', (string) $activeTimeline->id);
        $timelineIds = collect($response->json('data.timelines'))->pluck('value')->all();

        $this->assertContains((string) $activeTimeline->id, $timelineIds);
        $this->assertContains((string) $completedTimeline->id, $timelineIds);
        $this->assertNotContains((string) $plannedTimeline->id, $timelineIds);
        $this->assertNotContains((string) $cancelledTimeline->id, $timelineIds);
    }

    private function createProject(string $flow): Project
    {
        return Project::factory()->create([
            'project_flow' => $flow,
            'added_by' => $this->user->id,
            'is_system' => false,
        ]);
    }

    private function createTimeline(Project $project, int $status, string $name): ProjectTimeline
    {
        return ProjectTimeline::create([
            'project_id' => $project->id,
            'name' => $name,
            'type' => 'new',
            'status' => $status,
            'sort_order' => ((int) $project->projectTimelines()->max('sort_order')) + 1,
            'created_by' => $this->user->id,
        ]);
    }

    private function createPlacement(Project $project, string $name): array
    {
        $milestone = ProjectMilestone::create([
            'project_id' => $project->id,
            'name' => "{$name} Milestone",
            'sort_order' => ((int) $project->projectMilestones()->max('sort_order')) + 1,
        ]);

        $sprint = ProjectSprint::create([
            'project_id' => $project->id,
            'project_milestone_id' => $milestone->id,
            'name' => "{$name} Sprint",
            'sort_order' => 1,
        ]);

        return [$milestone, $sprint];
    }

    private function createTask(Project $project, ProjectTimeline $timeline, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'project_id' => $project->id,
            'project_timeline_id' => $timeline->id,
            'name' => 'Move Timeline Task '.uniqid(),
            'priority' => 'medium',
        ], $attributes));
    }
}
