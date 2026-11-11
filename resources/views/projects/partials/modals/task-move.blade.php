@php
    $project = $project ?? null;
    $projectTimelines = collect($projectTimelines ?? []);
    $projectMilestones = collect($projectMilestones ?? []);
    $projectSprints = collect($projectSprints ?? []);
    $isDeletedProjectView = $isDeletedProjectView ?? false;
    $taskMovePlacementOptions = [
        'timelines' => $projectTimelines
            ->map(
                fn($projectTimeline) => [
                    'value' => (string) $projectTimeline->id,
                    'text' => $projectTimeline->name,
                    'subtype' => ucfirst((string) config('project_constants.project_timeline_statuses.' . $projectTimeline->status, '')),
                ],
            )
            ->values(),
        'milestones' => $projectMilestones
            ->map(
                fn($projectMilestone) => [
                    'value' => (string) $projectMilestone->id,
                    'text' => $projectMilestone->name,
                ],
            )
            ->values(),
        'sprints' => $projectSprints
            ->map(
                fn($projectSprint) => [
                    'value' => (string) $projectSprint->id,
                    'text' => $projectSprint->name . ($projectSprint->projectMilestone?->name ? ' - ' . $projectSprint->projectMilestone->name : ''),
                    'project_milestone_id' => (string) ($projectSprint->project_milestone_id ?? ''),
                ],
            )
            ->values(),
    ];
@endphp

@if (!$isDeletedProjectView && auth()->user()?->can('task.move'))
    <div class="modal fixed inset-0 z-[75] hidden items-center justify-center overflow-y-auto" data-project-task-move-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-move-close></div>

        <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
            <div class="relative z-10 w-full max-w-5xl transition-all duration-200">
                <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]" data-project-task-move-panel>
                    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Move Task</h3>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-move-close>
                            ✕
                        </button>
                    </div>

                    <form class="space-y-4 overflow-y-auto px-6 pb-5" data-project-task-move-form data-task-move-placement='@json($taskMovePlacementOptions)' data-dependencies-url-template="{{ route('projects.task-create-dependencies', ['project' => '__PROJECT_ID__']) }}" data-initial-source-project-id="{{ $project?->id }}" data-source-project-id="{{ $project?->id }}" data-source-project-flow="{{ $project?->project_flow }}">
                        <input type="hidden" name="_method" value="PATCH">

                        <div class="rounded-lg border border-bgray-200 bg-bgray-50/70 p-3.5 text-sm text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500/40 dark:text-bgray-300 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-bgray-700 dark:text-bgray-300">Current Project:</span>
                                <span class="inline-flex items-center gap-1.5 font-medium text-bgray-900 dark:text-white">
                                    <span data-project-task-move-current-project-icon>
                                        @if ($project)
                                            <x-project-flow-icon :flow="$project->project_flow" size="sm" />
                                        @endif
                                    </span>
                                    <span data-project-task-move-current-project-name>{{ $project?->name ?? '--' }}</span>
                                    <span class="text-xs font-normal text-bgray-700 dark:text-bgray-300" data-project-task-move-current-project-flow>@if ($project)({{ ucfirst($project->project_flow) }})@endif</span>
                                </span>
                            </div>
                            <p class="text-xs py-1">
                                <span class="text-bgray-700 dark:text-bgray-300">Current Sprint:</span>
                                <span class="font-medium text-bgray-900 dark:text-white" data-project-task-move-current-sprint>--</span>
                            </p>
                            <p class="text-xs py-1">
                                <span class="text-bgray-700 dark:text-bgray-300">Current Timeline:</span>
                                <span class="font-medium text-bgray-900 dark:text-white" data-project-task-move-current-timeline>--</span>
                            </p>
                            <p class="text-xs py-1">
                                <span class="text-bgray-700 dark:text-bgray-300">Task Name:</span>
                                <span class="font-medium text-bgray-900 dark:text-white" data-project-task-move-task-name>--</span>
                            </p>
                            <p class="text-xs py-1">
                                <span class="text-bgray-700 dark:text-bgray-300">Task Code:</span>
                                <span class="font-medium text-bgray-900 dark:text-white" data-project-task-move-task-code>--</span>
                            </p>
                        </div>

                        <!-- Move to another project Checkbox -->
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="project_task_move_to_another_project" name="move_to_another_project" value="1" class="h-4 w-4 rounded border-gray-300 text-success-500 focus:ring-success-400 dark:border-darkblack-400 dark:bg-darkblack-500" data-project-task-move-another-project-toggle>
                            <label for="project_task_move_to_another_project" class="cursor-pointer text-sm font-medium text-bgray-700 dark:text-bgray-300 select-none">
                                Move to another project
                            </label>
                        </div>

                        <!-- Project selection (shown when checkbox is checked) -->
                        <div class="hidden space-y-1" data-project-task-move-project-container>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                Project <x-red-star />
                            </label>
                            <select id="move_target_project_id" name="target_project_id" class="tom-select-lazy w-full" data-route="{{ route('projects.search') }}" data-exclude-id="{{ $project?->id }}" data-sort="0" data-project-task-move-project-select disabled>
                                <option value="">Search project here..</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="target_project_id"></p>

                            <div class="mt-2 hidden rounded-md border border-bgray-200 bg-bgray-50/80 px-3 py-2 text-xs text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500/60 dark:text-bgray-300" data-project-task-move-target-flow-container>
                                <span class="text-bgray-700 dark:text-bgray-300">Target Project Flow:</span>
                                <span class="inline-flex items-center gap-1.5 font-semibold text-bgray-900 dark:text-white ml-1" data-project-task-move-target-flow-text></span>
                            </div>
                        </div>

                        <!-- Project Timeline selection -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                Project Timeline <x-red-star />
                            </label>
                            <select name="project_timeline_id" class="tom-select w-full" data-sort="0" data-project-task-move-timeline-select>
                                <option value="">Select project timeline</option>
                                @foreach ($projectTimelines as $projectTimeline)
                                    <option value="{{ $projectTimeline->id }}" data-subtype="{{ ucfirst((string) config('project_constants.project_timeline_statuses.' . $projectTimeline->status, '')) }}">
                                        {{ $projectTimeline->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 hidden text-xs text-bgray-700 dark:text-bgray-300" data-project-task-move-timeline-hint></p>
                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="project_timeline_id"></p>
                        </div>

                        <!-- Cross-Flow Notice Box -->
                        <div class="hidden rounded-lg border border-bgray-200 bg-bgray-50/80 dark:bg-bgray-500/40 p-3.5 text-sm text-warning-300 dark:border-warning-400 dark:text-warning-300" data-project-task-move-flow-notice>
                            <div class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0 text-warning-300 dark:text-warning-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                <div class="space-y-1">
                                    <p class="font-semibold text-warning-300 dark:text-warning-300">
                                        Notice: Moving task across different project flows (<span data-project-task-move-source-flow-label>Agile</span> &rarr; <span data-project-task-move-target-flow-label>Linear</span>)
                                    </p>
                                    <ul class="list-disc pl-4 space-y-0.5 text-warning-700 dark:text-warning-300">
                                        <li>Task timer will stop, if it is running.</li>
                                        <li>Task status will automatically change to <strong data-project-task-move-target-status-name>Pending</strong> (default for <span data-project-task-move-target-flow-label-2>Linear</span> projects).</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone selection -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                Milestone
                            </label>
                            <select name="project_milestone_id" class="tom-select w-full" data-sort="0" data-project-task-move-module-select>
                                <option value="">All milestones</option>
                                @foreach ($projectMilestones as $projectMilestone)
                                    <option value="{{ $projectMilestone->id }}">{{ $projectMilestone->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300" data-project-task-move-milestone-hint>
                                Optional. Choose a milestone to narrow the sprint list.
                            </p>
                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="project_milestone_id"></p>
                        </div>

                        <!-- Sprint selection -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                Sprint
                                <span data-project-task-move-sprint-star><x-red-star /></span>
                            </label>
                            <select name="project_sprint_id" class="tom-select w-full" data-sort="0">
                                <option value="">Select sprint</option>
                                @foreach ($projectSprints as $projectSprint)
                                    <option value="{{ $projectSprint->id }}" data-module-id="{{ $projectSprint->project_milestone_id }}">
                                        {{ $projectSprint->name }}@if ($projectSprint->projectMilestone?->name)
                                            - {{ $projectSprint->projectMilestone->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300 hidden" data-project-task-move-sprint-hint></p>
                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="project_sprint_id"></p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300" data-project-task-move-close>
                                Cancel
                            </button>

                            <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-project-task-move-submit>
                                Move
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
