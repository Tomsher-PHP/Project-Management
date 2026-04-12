@php
    $allPagesLoaded = $allPagesLoaded ?? true;
    $showEmptyState = $showEmptyState ?? true;
@endphp

@forelse ($projectSprints as $projectSprint)
    @php
        $isProtectedSprint = (bool) ($projectSprint->is_backlog || $projectSprint->is_system);
        $estimatedSeconds = (int) ($projectSprint->estimated_time_seconds ?? 0);
        $derivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);
        $actualSeconds = (int) ($projectSprint->actual_time_seconds ?? 0);
        $timeDifferenceSeconds = $derivedSeconds - $estimatedSeconds;
        $hasTimeDifference = $timeDifferenceSeconds !== 0;
        $timeDifferenceClasses = $timeDifferenceSeconds > 0 ? 'bg-red-50 text-red-500 dark:bg-darkblack-600 dark:text-red-400' : 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-300';
        $timeDifferencePrefix = $timeDifferenceSeconds > 0 ? '+' : '-';
        $statusName = $projectSprint->status?->name ?? 'No status';
        $formatDate = static fn($date) => \App\Providers\AppServiceProvider::formatAppDate($date);
        $dragHandleClasses = $allPagesLoaded
            ? 'cursor-move'
            : 'cursor-not-allowed opacity-50';
    @endphp
    <div class="overflow-hidden rounded-none border border-bgray-200 bg-bgray-50 shadow-sm transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500" data-project-sprint-card data-project-sprint-id="{{ $projectSprint->id }}" draggable="false" style="border-color: {{ $projectSprint->color ?: '#D1D5DB' }}">
        <div class="flex flex-col gap-3 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                @if ($isProtectedSprint)
                    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-400 opacity-70 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-400" title="System sprint">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                        </svg>
                    </span>
                @else
                    @can('project_sprint.edit')
                        <button type="button" class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 {{ $dragHandleClasses }}" data-project-sprint-drag-handle @if (! $allPagesLoaded) disabled title="Scroll to load all sprints before reordering" @endif>
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                            </svg>
                        </button>
                    @endcan
                @endif

                <div class="flex items-center gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $projectSprint->color ?: '#E5E7EB' }}"></span>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $projectSprint->name }}</p>
                            <span title="Estimated Time: {{ $projectSprint->estimated_time_formatted }}" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                Estimate <span class="ml-1">{{ $projectSprint->estimated_time_formatted }}</span>
                            </span>
                            <span title="Derived Time: {{ $projectSprint->derived_time_formatted }}" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                Derived <span class="ml-1">{{ $projectSprint->derived_time_formatted }}</span>
                            </span>
                            <span title="Actual Time: {{ $projectSprint->actual_time_formatted }}" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                Actual <span class="ml-1">{{ $projectSprint->actual_time_formatted }}</span>
                            </span>
                            @if ($hasTimeDifference)
                                <span title="{{ $timeDifferenceSeconds > 0 ? 'Exceeds estimate by' : 'Under estimate by' }} {{ sprintf('%02d h : %02d m', floor(abs($timeDifferenceSeconds) / 3600), floor((abs($timeDifferenceSeconds) % 3600) / 60)) }}" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $timeDifferenceClasses }}">
                                    {{ $timeDifferencePrefix }}{{ sprintf('%02d h : %02d m', floor(abs($timeDifferenceSeconds) / 3600), floor((abs($timeDifferenceSeconds) % 3600) / 60)) }}
                                </span>
                            @endif
                            <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                Tasks {{ $projectSprint->task_count }}
                            </span>
                        </div>
                        @if ($projectSprint->description)
                            <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                                {{ \Illuminate\Support\Str::limit($projectSprint->description, 100) }}
                            </p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium text-bgray-600 dark:text-bgray-300">
                            <span class="inline-flex rounded-full bg-white px-2.5 py-1 dark:bg-darkblack-600">Status: {{ $statusName }}</span>
                            <span class="inline-flex rounded-full bg-white px-2.5 py-1 dark:bg-darkblack-600">Start: {{ $formatDate($projectSprint->start_date) }}</span>
                            <span class="inline-flex rounded-full bg-white px-2.5 py-1 dark:bg-darkblack-600">End: {{ $formatDate($projectSprint->end_date) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                @if (! $isProtectedSprint)
                    @can('project_sprint.edit')
                        <button type="button" class="project-sprint-builder-edit inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" data-project-sprint-id="{{ $projectSprint->id }}" data-project-module-id="{{ $projectSprint->project_module_id }}" data-project-module-name="{{ $module->name }}"
                            data-project-sprint-load-url="{{ route('projects.modules.sprints.index', [$project, $module]) }}" title="Edit sprint">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                            </svg>
                        </button>
                    @endcan

                    @can('project_sprint.delete')
                        <x-delete-form :action="route('projects.sprints.destroy', [$project, $projectSprint])" ajax render-target="[data-project-module-section]" render-mode="replace_outer" />
                    @endcan
                @endif
            </div>
        </div>
    </div>
@empty
    @if ($showEmptyState)
        <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-5 py-6 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <p class="text-sm font-medium text-bgray-600 dark:text-bgray-100">No sprints added under this module yet.</p>
        </div>
    @endif
@endforelse
