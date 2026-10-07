<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTimeline;
use Illuminate\Http\JsonResponse;
use App\Services\ProjectTimelineService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\ProjectTimelineStoreRequest;
use App\Http\Requests\ProjectTimelineUpdateRequest;

class ProjectTimelineController extends Controller
{
    protected ProjectTimelineService $timelineService;

    public function __construct(ProjectTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    public function store(ProjectTimelineStoreRequest $request, Project $project): JsonResponse
    {
        $timeline = $this->timelineService->store($project, $request->validated());

        return response()->json([
            'status' => true,
            'success' => true,
            'message' => 'Project timeline created successfully.',
            'timeline' => $timeline,
            'html' => view('projects.partials.tabs.settings.timelines-list', [
                'projectTimelines' => $project->projectTimelines()->orderBy('sort_order')->get(),
                'project' => $project,
                'canEdit' => auth()->user()->can('project.edit') && !$project->trashed(),
            ])->render(),
            'render_target' => '#project-timelines-container',
            'render_mode' => 'replace_inner',
        ], Response::HTTP_CREATED);
    }

    public function update(ProjectTimelineUpdateRequest $request, Project $project, ProjectTimeline $projectTimeline): JsonResponse
    {
        abort_unless($projectTimeline->project_id === $project->id, 403, 'Timeline does not belong to this project.');

        $timeline = $this->timelineService->update($projectTimeline, $request->validated());

        return response()->json([
            'status' => true,
            'success' => true,
            'message' => 'Project timeline updated successfully.',
            'timeline' => $timeline,
            'html' => view('projects.partials.tabs.settings.timelines-list', [
                'projectTimelines' => $project->projectTimelines()->orderBy('sort_order')->get(),
                'project' => $project,
                'canEdit' => auth()->user()->can('project.edit') && !$project->trashed(),
            ])->render(),
            'render_target' => '#project-timelines-container',
            'render_mode' => 'replace_inner',
        ], Response::HTTP_OK);
    }

    public function destroy(Project $project, ProjectTimeline $projectTimeline): JsonResponse
    {
        abort_unless($projectTimeline->project_id === $project->id, 403, 'Timeline does not belong to this project.');

        if ($projectTimeline->tasks()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete this timeline because it has associated tasks.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->timelineService->destroy($projectTimeline);

        return response()->json([
            'status' => true,
            'success' => true,
            'message' => 'Project timeline deleted successfully.',
            'html' => view('projects.partials.tabs.settings.timelines-list', [
                'projectTimelines' => $project->projectTimelines()->orderBy('sort_order')->get(),
                'project' => $project,
                'canEdit' => auth()->user()->can('project.edit') && !$project->trashed(),
            ])->render(),
            'render_target' => '#project-timelines-container',
            'render_mode' => 'replace_inner',
        ], Response::HTTP_OK);
    }

    public function activate(Project $project, ProjectTimeline $projectTimeline): JsonResponse
    {
        abort_unless($projectTimeline->project_id === $project->id, 403, 'Timeline does not belong to this project.');

        $timeline = $this->timelineService->activateTimeline($projectTimeline);
        
        return response()->json([
            'status' => true,
            'success' => true,
            'message' => 'Project timeline activated successfully.',
            'timeline' => $timeline,
            'html' => view('projects.partials.tabs.settings.timelines-list', [
                'projectTimelines' => $project->projectTimelines()->orderBy('sort_order')->get(),
                'project' => $project,
                'canEdit' => auth()->user()->can('project.edit') && !$project->trashed(),
            ])->render(),
            'render_target' => '#project-timelines-container',
            'render_mode' => 'replace_inner',
        ], Response::HTTP_OK);
    }

    public function complete(Project $project, ProjectTimeline $projectTimeline): JsonResponse
    {
        abort_unless($projectTimeline->project_id === $project->id, 403, 'Timeline does not belong to this project.');

        $timeline = $this->timelineService->completeTimeline($projectTimeline);
        
        return response()->json([
            'status' => true,
            'success' => true,
            'message' => 'Project timeline completed successfully.',
            'timeline' => $timeline,
            'html' => view('projects.partials.tabs.settings.timelines-list', [
                'projectTimelines' => $project->projectTimelines()->orderBy('sort_order')->get(),
                'project' => $project,
                'canEdit' => auth()->user()->can('project.edit') && !$project->trashed(),
            ])->render(),
            'render_target' => '#project-timelines-container',
            'render_mode' => 'replace_inner',
        ], Response::HTTP_OK);
    }
}
