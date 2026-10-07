<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectTimelineService
{
    protected ProjectServices $projectServices;
    protected NotificationService $notificationService;

    public function __construct(ProjectServices $projectServices, NotificationService $notificationService)
    {
        $this->projectServices = $projectServices;
        $this->notificationService = $notificationService;
    }

    public function store(Project $project, array $data): ProjectTimeline
    {
        return DB::transaction(function () use ($project, $data) {
            $timelineData = $this->prepareTimelineData($data);
            $timelineData['project_id'] = $project->id;
            $timelineData['created_by'] = auth()->id() ?? null;
            
            $timelineData['sort_order'] = ProjectTimeline::where('project_id', $project->id)->max('sort_order') + 1;

            return ProjectTimeline::create($timelineData);
        });
    }

    public function update(ProjectTimeline $timeline, array $data): ProjectTimeline
    {
        return DB::transaction(function () use ($timeline, $data) {
            if ($timeline->getOriginal('type') === 'original' && isset($data['type']) && $data['type'] !== 'original') {
                throw ValidationException::withMessages(['type' => 'The original timeline cannot change its type.']);
            }
            if ($timeline->getOriginal('type') !== 'original' && isset($data['type']) && $data['type'] === 'original') {
                throw ValidationException::withMessages(['type' => 'Cannot change a timeline to original type.']);
            }

            $timelineData = $this->prepareTimelineData($data);
            $timeline->fill($timelineData);
            
            if ($timeline->type === 'original') {
                $this->syncProjectFieldsFromTimeline($timeline->project, $timeline);
            }

            $timeline->save();

            return $timeline;
        });
    }

    public function syncOriginalTimelineFromProject(Project $project): void
    {
        $originalTimeline = $project->projectTimelines()->where('type', 'original')->first();
        if ($originalTimeline) {
            $originalTimeline->update([
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'customer_end_date' => $project->customer_end_date,
                'estimated_time_seconds' => $project->estimated_time_seconds,
                'customer_estimate_seconds' => $project->customer_estimate_seconds,
            ]);
        }
    }

    private function syncProjectFieldsFromTimeline(Project $project, ProjectTimeline $timeline): void
    {
        $fields = [
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'customer_end_date' => 'Customer End Date',
            'estimated_time_seconds' => 'Estimated Time',
            'customer_estimate_seconds' => 'Customer Estimate Time',
        ];
        $originalTimelineValues = $project->only(array_keys($fields));

        $project->start_date = $timeline->start_date;
        $project->end_date = $timeline->end_date;
        $project->customer_end_date = $timeline->customer_end_date;
        $project->estimated_time_seconds = $timeline->estimated_time_seconds;
        $project->customer_estimate_seconds = $timeline->customer_estimate_seconds;

        $timelineChanges = $this->projectServices->buildProjectTimelineChanges($project, $originalTimelineValues);
        
        $project->save();

        if ($timelineChanges !== [] && ($actor = auth()->user())) {
            $this->notificationService->notifyProjectTimelineChanged(
                $project->fresh(),
                $actor,
                $timelineChanges
            );
        }
    }

    public function destroy(ProjectTimeline $timeline): void
    {
        if ($timeline->type === 'original') {
            throw ValidationException::withMessages([
                'timeline' => 'The Original timeline cannot be deleted.',
            ]);
        }

        if ($timeline->tasks()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'timeline' => 'This timeline cannot be deleted because it has assigned tasks.',
            ]);
        }

        $timeline->forceDelete();
    }

    private function prepareTimelineData(array $data): array
    {
        $prepared = $data;

        if (array_key_exists('estimated_time_minutes', $data)) {
            $prepared['estimated_time_seconds'] = $data['estimated_time_minutes'] !== null
                ? (int) $data['estimated_time_minutes'] * 60
                : null;
            unset($prepared['estimated_time_minutes']);
        }

        if (array_key_exists('customer_estimate_minutes', $data)) {
            $prepared['customer_estimate_seconds'] = $data['customer_estimate_minutes'] !== null
                ? (int) $data['customer_estimate_minutes'] * 60
                : null;
            unset($prepared['customer_estimate_minutes']);
        }

        return $prepared;
    }
}
