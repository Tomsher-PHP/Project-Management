@php
    $canCustomerEndDate = auth()->user()->can('project.customer_end_date');
    $canChangeProjectStatus = auth()->user()->can('project.status_change');
    $canChangeProjectStage = auth()->user()->can('project.edit');
    $projectStatusUpdateUrl = route('projects.updateProjectStatus', $project);
    $projectStageUpdateUrl = route('projects.updateProjectStage', $project);
    $projectCreatedAtLabel = $project->created_at
        ? $project->created_at
            ->copy()
            ->timezone($globalTimezone)
            ->format($globalDateFormat . ' ' . $globalTimeFormat)
        : '--';
    $projectTimeline = $projectTimeline ?? [
        'percentage' => 0,
        'bar_class' => 'bg-gray-300',
        'text_class' => 'text-bgray-500 dark:text-bgray-300',
        'start_label' => $project->start_date?->format($globalDateFormat) ?? '--',
        'end_label' => $project->end_date?->format($globalDateFormat) ?? '--',
    ];
    $customerTimeline = $customerTimeline ?? [
        'percentage' => 0,
        'bar_class' => 'bg-gray-300',
        'text_class' => 'text-bgray-500 dark:text-bgray-300',
        'start_label' => $project->start_date?->format($globalDateFormat) ?? '--',
        'end_label' => $project->customer_end_date?->format($globalDateFormat) ?? '--',
    ];
@endphp
<div class="mb-6 rounded-lg bg-white p-5 dark:bg-darkblack-600">

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

        <!-- LEFT: Project Info -->
        <div>
            <div class="flex items-center gap-3">
                <!-- Priority Indicator -->
                <div class="h-10 w-1 rounded {{ $priority['bg_class'] ?? 'bg-gray-300' }}"></div>

                <div>
                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white" id="project-name-display">
                        {{ $project->name }}
                    </h2>
                    <p class="text-sm text-bgray-500">
                        Code: {{ $project->project_code ?? '--' }}
                    </p>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-bgray-600 dark:text-bgray-300">

                <span>
                    <strong>Customer:</strong> {{ $project->customer->name ?? '--' }}
                </span>

                <span>
                    <strong>Project Type:</strong> {{ strtoupper($project->project_type ?? '--') }}
                </span>

                <span>
                    <strong>Start Date:</strong> {{ optional($project->start_date)->format($globalDateFormat) ?? '--' }}
                </span>

                <span>
                    <strong>End Date:</strong> {{ optional($project->end_date)->format($globalDateFormat) ?? '--' }}
                </span>

                {{-- @if ($canCustomerEndDate)
                    <span>
                        <strong>Customer End Date:</strong> {{ optional($project->customer_end_date)->format($globalDateFormat) ?? '--' }}
                    </span>
                @endcan --}}

        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                <p class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Project Timeline</p>

                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-500">
                            <div class="h-full rounded-full transition-all duration-300 {{ $projectTimeline['bar_class'] }}" style="width: {{ $projectTimeline['percentage'] }}%;"></div>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-[11px] font-medium text-bgray-500 dark:text-bgray-300">
                            <span>{{ $projectTimeline['start_label'] }}</span>
                            <span>{{ $projectTimeline['end_label'] }}</span>
                        </div>
                    </div>

                    <span class="shrink-0 text-sm font-bold {{ $projectTimeline['text_class'] }}">
                        {{ $projectTimeline['percentage'] }}%
                    </span>
                </div>
            </div>

            @if ($canCustomerEndDate)
                <div class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                    <p class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Customer Timeline</p>

                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-500">
                                <div class="h-full rounded-full transition-all duration-300 {{ $customerTimeline['bar_class'] }}" style="width: {{ $customerTimeline['percentage'] }}%;"></div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[11px] font-medium text-bgray-500 dark:text-bgray-300">
                                <span>{{ $customerTimeline['start_label'] }}</span>
                                <span>{{ $customerTimeline['end_label'] }}</span>
                            </div>
                        </div>

                        <span class="shrink-0 text-sm font-bold {{ $customerTimeline['text_class'] }}">
                            {{ $customerTimeline['percentage'] }}%
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- RIGHT: Status + Priority -->
    <div class="flex flex-col gap-3 md:min-w-[220px] md:items-end md:self-stretch md:justify-between">
        <div class="flex flex-nowrap items-center gap-3 md:justify-end">
            @if ($canChangeProjectStatus)
                <div class="relative min-w-[150px] shrink-0 sm:min-w-[165px]" data-project-header-dropdown>
                    <button type="button" class="relative flex h-[42px] w-[150px] items-center justify-between rounded-lg px-4 text-sm font-semibold text-white shadow-sm transition duration-200 sm:w-[165px]" data-project-header-trigger style="border: 1px solid {{ $project->projectStatus->color ?? '#6B7280' }}; background-color: {{ $project->projectStatus->color ?? '#6B7280' }};">
                        <span class="truncate whitespace-nowrap">{{ $project->projectStatus->name ?? 'No Status' }}</span>
                        <span>
                            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white">
                                <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </button>
                    <div class="absolute right-0 top-14 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-project-header-menu>
                        <ul class="max-h-72 overflow-y-auto">
                            @foreach ($projectStatuses as $statusOption)
                                <li>
                                    <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-header-option data-url="{{ $projectStatusUpdateUrl }}" data-field="status_id" data-value="{{ $statusOption->id }}" data-current-value="{{ $project->status_id }}">
                                        <span class="flex items-center gap-2 @if ((int) $project->status_id === (int) $statusOption->id) text-success-400 dark:text-success-300 @endif">
                                            <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $statusOption->color ?: '#9CA3AF' }}"></span>
                                            <span>{{ $statusOption->name }}</span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <span class="whitespace-nowrap rounded-full px-4 py-1 text-sm font-semibold text-white" style="border: 1px solid {{ $project->projectStatus->color ?? '#6B7280' }}; background-color: {{ $project->projectStatus->color ?? '#6B7280' }};">
                    {{ $project->projectStatus->name ?? 'No Status' }}
                </span>
            @endif

            @if ($canChangeProjectStage)
                <div class="relative min-w-[150px] shrink-0 sm:min-w-[165px]" data-project-header-dropdown>
                    <button type="button" class="relative flex h-[42px] w-[150px] items-center justify-between rounded-lg border border-bgray-200 bg-bgray-100 px-4 text-sm font-semibold text-blue-500 shadow-sm transition duration-200 hover:text-blue-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-blue-500 dark:hover:bg-darkblack-400 sm:w-[165px]" data-project-header-trigger>
                        <span class="truncate whitespace-nowrap">{{ $project->projectStage->name ?? 'No Stage' }}</span>
                        <span>
                            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-blue-500">
                                <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </button>
                    <div class="absolute right-0 top-14 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-project-header-menu>
                        <ul class="max-h-72 overflow-y-auto">
                            <li>
                                <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-header-option data-url="{{ $projectStageUpdateUrl }}" data-field="project_stage_id" data-value="" data-current-value="{{ $project->project_stage_id ?? '' }}">
                                    <span @if (blank($project->project_stage_id)) class="text-success-400 dark:text-success-300" @endif>No Stage</span>
                                </button>
                            </li>
                            @foreach ($projectStages as $stageOption)
                                <li>
                                    <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-header-option data-url="{{ $projectStageUpdateUrl }}" data-field="project_stage_id" data-value="{{ $stageOption->id }}" data-current-value="{{ $project->project_stage_id ?? '' }}">
                                        <span @if ((int) ($project->project_stage_id ?? 0) === (int) $stageOption->id) class="text-success-400 dark:text-success-300" @endif>{{ $stageOption->name }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <span class="whitespace-nowrap rounded-full border border-bgray-200 bg-bgray-100 px-4 py-1 text-sm font-semibold text-blue-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-blue-500">
                    {{ $project->projectStage->name ?? 'No Stage' }}
                </span>
            @endif
        </div>

        <div class="px-1 py-1 text-right">
            <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                {{ $project->addedBy->name ?? '--' }}
            </p>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                {{ $projectCreatedAtLabel }}
            </p>
        </div>
    </div>

</div>
</div>
