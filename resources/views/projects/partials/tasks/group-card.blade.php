@php
    $isLoaded = $isOpen;
@endphp

<article class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm transition dark:border-darkblack-400 dark:bg-darkblack-600" data-project-task-group data-group-key="{{ $group['key'] }}" data-expanded="{{ $isOpen ? 'true' : 'false' }}" data-load-url="{{ route('projects.tasks.groups.show', ['project' => $project, 'group' => $group['key']]) }}" style="border-left-width: 4px; border-left-color: {{ $group['accent_color'] }};">
    <div class="flex items-center justify-between gap-4 overflow-x-auto px-4 py-3 text-left transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/70">
        <div class="flex min-w-0 flex-1 items-center gap-3 whitespace-nowrap">
            <button type="button" class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-bgray-200 bg-white text-bgray-700 shadow-sm transition hover:border-primary hover:bg-bgray-50 hover:text-primary dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100 dark:hover:border-success-300 dark:hover:text-success-300" data-project-task-group-toggle aria-label="Toggle sprint tasks">
                <svg class="h-4.5 w-4.5 transition duration-200 {{ $isOpen ? 'rotate-90' : '' }}" data-project-task-group-icon viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="flex min-w-0 flex-1 items-center gap-3 select-text">
                <h4 class="truncate text-base font-semibold text-bgray-900 dark:text-white">{{ $group['name'] }}</h4>

                @if ($group['is_unscheduled'] && empty($group['is_linear_group']))
                    <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                        Backlog
                    </span>
                @endif

                @if ($group['subtitle'])
                    <span class="truncate text-sm text-bgray-600 dark:text-bgray-300">{{ $group['subtitle'] }}</span>
                @endif

                @if (!empty($group['date_label']))
                    <span class="text-sm text-bgray-600 dark:text-bgray-300">{{ $group['date_label'] }}</span>
                @endif

                @if ($group['created_label'])
                    <span class="text-sm text-bgray-600 dark:text-bgray-300">Created {{ $group['created_label'] }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-shrink-0 items-center gap-2 whitespace-nowrap select-text">
            <span title="Task count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Tasks <span class="ml-1">{{ $group['task_count'] }}</span>
            </span>

            <span title="Estimated time" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Estimate <span class="ml-1">{{ $group['estimated_label'] }}</span>
            </span>

            @can('task.create')
                @if (!$group['is_unscheduled'] && empty($group['is_linear_group']))
                    <span class="inline-flex cursor-pointer items-center rounded-full bg-success-300 px-3 py-1 text-xs font-semibold text-white transition hover:bg-success-400" data-project-task-modal-open data-project-task-sprint-id="{{ $group['sprint_id'] }}">
                        + Task
                    </span>
                @endif
            @endcan
        </div>
    </div>

    <div class="{{ $isOpen ? '' : 'hidden' }}" data-project-task-group-panel>
        <div class="border-t border-bgray-200 px-3 py-3 dark:border-darkblack-400 sm:px-5" data-project-task-group-body data-loaded="{{ $isLoaded ? 'true' : 'false' }}">
            @if ($isLoaded)
                @include('projects.partials.tasks.group-body', [
                    'project' => $project,
                    'group' => $group,
                    'tasks' => $tasks,
                ])
            @endif
        </div>
    </div>
</article>
