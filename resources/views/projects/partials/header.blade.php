@php
    $isDeletedProject = $project->trashed();
    $canCustomerEndDate = auth()->user()->can('project.customer_end_date');
    $canChangeProjectStatus = !$isDeletedProject && auth()->user()->can('project.status_change');
    $canChangeProjectStage = !$isDeletedProject && auth()->user()->can('project.edit');
    $canAddProjectPayment = !$isDeletedProject && auth()->user()->can('project.add_payment_status');
    $canViewProjectPayment = auth()->user()->can('project.view_payment_status');

    $projectStatusUpdateUrl = route('projects.updateProjectStatus', $project);
    $projectStageUpdateUrl = route('projects.updateProjectStage', $project);
    $projectPaymentUpdateUrl = route('projects.addProjectPaymentStatus', $project);

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
    $isAgileFlow = $project->project_flow === 'agile';
    $flowLabel = ucfirst($project->project_flow ?? 'linear');
    $projectStatusColor = $project->projectStatus->color ?? '#6B7280';
    $projectStageColor = $project->projectStage->color ?? '#6B7280';
@endphp

<div class="mb-6 rounded-lg bg-white p-5 dark:bg-darkblack-600" data-project-header-card data-project-id="{{ $project->id }}">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-3">
                <div class="h-10 w-1 rounded {{ $priority['bg_class'] ?? 'bg-gray-300' }}"></div>

                <div class="min-w-0">
                    @php
                        $parentProject = $project->parentProject;
                        $parentProjectUrl = $parentProject ? ($parentProject->trashed() ? route('projects.restore.show', $parentProject->id) : route('projects.edit', $parentProject)) : null;
                    @endphp
                    <div class="flex min-w-0 items-center gap-2">
                        <x-project-flow-icon :flow="$project->project_flow" size="sm" />
                        <h2 class="min-w-0 truncate text-xl font-bold text-bgray-900 dark:text-white" id="project-name-display">
                            {{ $project->name }}
                            <span class="text-base font-semibold text-bgray-500 dark:text-bgray-300">({{ $project->project_code ?? '--' }})</span>
                        </h2>
                    </div>
                    @if ($parentProject)
                        <p class="text-sm text-bgray-600 dark:text-bgray-300">
                            Rework for:
                            <a href="{{ $parentProjectUrl }}" class="font-semibold text-success-400 hover:underline">
                                {{ $parentProject->name }}
                            </a>
                            @if ($parentProject->trashed())
                                <span class="text-bgray-500">(deleted)</span>
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <div class="flex items-center gap-2">
                @if ($canChangeProjectStatus)
                    <div class="relative min-w-[142px] shrink-0 sm:min-w-[154px]" data-project-header-dropdown>
                        <button type="button" class="relative flex h-9 w-[142px] items-center justify-between rounded-lg px-3 text-xs font-semibold text-white shadow-sm transition duration-200 sm:w-[154px]" data-project-header-trigger style="border: 1px solid {{ $projectStatusColor }}; background-color: {{ $projectStatusColor }};">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <span class="shrink-0 uppercase tracking-[0.12em] text-[10px]/none text-white/80">Status :</span>
                                <span class="truncate whitespace-nowrap">{{ $project->projectStatus->name ?? 'No Status' }}</span>
                            </span>
                            <span>
                                <svg width="18" height="18" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white">
                                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                        <div class="absolute right-0 top-14 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-project-header-menu>
                            <ul class="max-h-72 overflow-y-auto">
                                @foreach ($projectStatuses as $statusOption)
                                    <li>
                                        <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-change-option data-url="{{ $projectStatusUpdateUrl }}" data-field="status_id" data-value="{{ $statusOption->id }}" data-item-name="{{ $statusOption->name }}" data-item-color="{{ $statusOption->color ?: '#9CA3AF' }}" data-current-value="{{ $project->status_id }}" data-modal-title="Change Project Status"
                                            data-modal-description="Add the effective date and an optional remark for this change." data-submit-label="Update Status" data-min-date="{{ $statusChangeMinDate ?? '' }}" data-min-date-label="{{ $statusChangeMinDateLabel ?? '' }}">
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
                    <span class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-full px-3 text-xs font-semibold text-white" style="border: 1px solid {{ $projectStatusColor }}; background-color: {{ $projectStatusColor }};">
                        <span class="uppercase tracking-[0.12em] text-[10px]/none text-white/80">Status :</span>
                        <span>{{ $project->projectStatus->name ?? 'No Status' }}</span>
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                @if ($canChangeProjectStage)
                    <div class="relative min-w-[142px] shrink-0 sm:min-w-[154px]" data-project-header-dropdown>
                        <button type="button" class="relative flex h-9 w-[142px] items-center justify-between rounded-lg px-3 text-xs font-semibold text-white shadow-sm transition duration-200 sm:w-[154px]" data-project-header-trigger style="border: 1px solid {{ $projectStageColor }}; background-color: {{ $projectStageColor }};">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <span class="shrink-0 uppercase tracking-[0.12em] text-[10px]/none text-white/80">Stage :</span>
                                <span class="truncate whitespace-nowrap">{{ $project->projectStage->name ?? 'No Stage' }}</span>
                            </span>
                            <span>
                                <svg width="18" height="18" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white">
                                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                        <div class="absolute right-0 top-14 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-project-header-menu>
                            <ul class="max-h-72 overflow-y-auto">
                                <li>
                                    <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-change-option data-url="{{ $projectStageUpdateUrl }}" data-field="project_stage_id" data-value="" data-item-name="No Stage" data-item-color="#9CA3AF" data-current-value="{{ $project->project_stage_id ?? '' }}" data-modal-title="Change Project Stage" data-modal-description="Add the effective date and an optional remark for this change."
                                        data-submit-label="Update Stage" data-min-date="{{ $stageChangeMinDate ?? '' }}" data-min-date-label="{{ $stageChangeMinDateLabel ?? '' }}">
                                        <span @if (blank($project->project_stage_id)) class="text-success-400 dark:text-success-300" @endif>No Stage</span>
                                    </button>
                                </li>
                                @foreach ($projectStages as $stageOption)
                                    <li>
                                        <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-project-change-option data-url="{{ $projectStageUpdateUrl }}" data-field="project_stage_id" data-value="{{ $stageOption->id }}" data-item-name="{{ $stageOption->name }}" data-item-color="{{ $stageOption->color ?: '#9CA3AF' }}" data-current-value="{{ $project->project_stage_id ?? '' }}" data-modal-title="Change Project Stage"
                                            data-modal-description="Add the effective date and an optional remark for this change." data-submit-label="Update Stage" data-min-date="{{ $stageChangeMinDate ?? '' }}" data-min-date-label="{{ $stageChangeMinDateLabel ?? '' }}">
                                            <span class="flex items-center gap-2 @if ((int) $project->project_stage_id === (int) $stageOption->id) text-success-400 dark:text-success-300 @endif">
                                                <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $stageOption->color ?: '#9CA3AF' }}"></span>
                                                <span>{{ $stageOption->name }}</span>
                                            </span>
                                        </button>

                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <span class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-full px-3 text-xs font-semibold text-white" style="border: 1px solid {{ $projectStageColor }}; background-color: {{ $projectStageColor }};">
                        <span class="uppercase tracking-[0.12em] text-[10px]/none text-white/80">Stage :</span>
                        <span>{{ $project->projectStage->name ?? 'No Stage' }}</span>
                    </span>
                @endif
            </div>

            @if ($project->is_linear && ($canAddProjectPayment || $canViewProjectPayment))
                <div class="flex items-center gap-2">
                    @if ($canAddProjectPayment || $canViewProjectPayment)
                        <div class="flex flex-col items-start gap-1 sm:items-end">
                            <span class="inline-flex h-9 max-w-[170px] items-center gap-1.5 whitespace-nowrap rounded-full px-3 text-xs font-semibold text-white" style="border: 1px solid {{ $projectPaymentColor }}; background-color: {{ $projectPaymentColor }};" title="{{ $paymentCoverageText }}">
                                <span class="shrink-0 uppercase tracking-[0.12em] text-[10px]/none text-white/80">Payment :</span>
                                <span class="truncate">{{ $paymentSummary['label'] ?? 'Unpaid' }}</span>
                                @if ($canAddProjectPayment && $paymentMetaText)
                                    <span class="hidden truncate text-[10px] font-medium text-white/80 lg:inline">- {{ $paymentMetaText }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    @if ($canAddProjectPayment)
                        <button type="button" class="inline-flex h-9 items-center whitespace-nowrap rounded-lg border border-bgray-200 bg-bgray-50 px-3 text-xs font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300" data-project-payment-modal-open data-url="{{ $projectPaymentUpdateUrl }}">
                            + Payment Status
                        </button>
                    @endif
                </div>
            @endif

            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-header-collapse-toggle aria-expanded="false" aria-label="Expand project header details">
                <svg class="h-4 w-4 transition duration-200" data-project-header-collapse-icon viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mt-5 hidden border-t border-bgray-200 pt-4 dark:border-darkblack-400" data-project-header-expandable>
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
            <div class="space-y-4">
                <div class="rounded-xl border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-500 dark:text-bgray-300">Customer</p>
                            <p class="mt-1 truncate text-sm font-medium text-bgray-900 dark:text-white">{{ $project->customer->name ?? '--' }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-500 dark:text-bgray-300">Project Flow</p>
                            <div class="mt-1 flex items-center gap-2 text-sm font-medium text-bgray-900 dark:text-white">
                                <x-project-flow-icon :flow="$project->project_flow" size="sm" />
                                <span>{{ strtoupper($project->project_flow ?? '--') }}</span>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-500 dark:text-bgray-300">Start Date</p>
                            <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">{{ optional($project->start_date)->format($globalDateFormat) ?? '--' }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-500 dark:text-bgray-300">End Date</p>
                            <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">{{ optional($project->end_date)->format($globalDateFormat) ?? '--' }}</p>
                        </div>
                        @if ($canCustomerEndDate)
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-500 dark:text-bgray-300">Customer End Date</p>
                                <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">{{ optional($project->customer_end_date)->format($globalDateFormat) ?? '--' }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
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
