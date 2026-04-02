<div class="space-y-3 border-l-2 border-dashed border-bgray-200 pl-4 transition duration-200 dark:border-darkblack-400 md:pl-6" data-project-sprint-list>
    @forelse ($projectSprints as $projectSprint)
        @php
            $estimatedSeconds = (int) ($projectSprint->estimated_time_seconds ?? 0);
            $derivedSeconds = (int) ($projectSprint->derived_time_seconds ?? 0);
            $timeDifferenceSeconds = $derivedSeconds - $estimatedSeconds;
            $hasTimeDifference = $timeDifferenceSeconds !== 0;
            $timeDifferenceClasses = $timeDifferenceSeconds > 0 ? 'bg-red-50 text-red-500 dark:bg-darkblack-600 dark:text-red-400' : 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-300';
            $timeDifferencePrefix = $timeDifferenceSeconds > 0 ? '+' : '-';
        @endphp
        <div class="overflow-hidden rounded-2xl border border-bgray-200 bg-bgray-50 shadow-sm transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500" data-project-sprint-card data-project-sprint-id="{{ $projectSprint->id }}" style="border-color: {{ $projectSprint->color ?: '#D1D5DB' }}">
            <div class="flex flex-col gap-3 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $projectSprint->color ?: '#E5E7EB' }}"></span>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $projectSprint->name }}</p>
                            <span title="Estimated Time: {{ $projectSprint->estimated_time_formatted }}" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                {{ $projectSprint->estimated_time_formatted }}
                            </span>
                            <span title="Derived Time: {{ $projectSprint->derived_time_formatted }}" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                {{ $projectSprint->derived_time_formatted }}
                            </span>
                            @if ($hasTimeDifference)
                                <span title="{{ $timeDifferenceSeconds > 0 ? 'Exceeds estimate by' : 'Under estimate by' }} {{ sprintf('%02d h : %02d m', floor(abs($timeDifferenceSeconds) / 3600), floor((abs($timeDifferenceSeconds) % 3600) / 60)) }}" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $timeDifferenceClasses }}">
                                    {{ $timeDifferencePrefix }}{{ sprintf('%02d h : %02d m', floor(abs($timeDifferenceSeconds) / 3600), floor((abs($timeDifferenceSeconds) % 3600) / 60)) }}
                                </span>
                            @endif
                            <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                {{ $projectSprint->task_count }} Tasks
                            </span>
                        </div>
                        @if ($projectSprint->description)
                            <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                                {{ \Illuminate\Support\Str::limit($projectSprint->description, 100) }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    @can('task.create')
                        <button type="button" disabled title="Task creation will be added next" class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-1.5 text-sm font-medium text-success-400 opacity-60 dark:border-success-900/30 dark:bg-darkblack-600 dark:text-success-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Task</span>
                        </button>
                    @endcan

                    @can('project_sprint.edit')
                        <a href="javascript:void(0)" class="edit-record inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" data-modal="project-sprint-modal" data-url="{{ route('projects.sprints.update', [$project, $projectSprint]) }}" data-method="PUT" data-module="Project Sprint"
                            data-module-context="project-sprint" data-project_module_id="{{ $projectSprint->project_module_id }}" data-name="{{ $projectSprint->name }}" data-color="{{ $projectSprint->color }}" data-description="{{ $projectSprint->description }}" data-estimated_time_minutes="{{ $projectSprint->estimated_time_minutes }}" title="Edit sprint">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                            </svg>
                        </a>
                    @endcan

                    @can('project_sprint.delete')
                        <button type="button" disabled title="Sprint deletion will be added next" class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 opacity-60 dark:border-red-900/40 dark:bg-darkblack-600 dark:text-red-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    @endcan

                    <button type="button" @click="activeSprintId = activeSprintId === {{ $projectSprint->id }} ? null : {{ $projectSprint->id }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                        <svg class="h-4 w-4 transition duration-200" :style="{ transform: activeSprintId === {{ $projectSprint->id }} ? 'rotate(180deg)' : 'rotate(0deg)' }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div x-show="activeSprintId === {{ $projectSprint->id }}" x-transition class="border-t border-bgray-200 px-4 py-4 dark:border-darkblack-400">
                <div class="space-y-3 border-l-2 border-dashed border-bgray-200 pl-4 dark:border-darkblack-400 md:ml-4 md:pl-5">
                    @include('projects.partials.module.tasks', ['previewTasks' => []])
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-5 py-6 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <p class="text-sm font-medium text-bgray-600 dark:text-bgray-100">No sprints added under this module yet.</p>
        </div>
    @endforelse
</div>
