@php
    $canCustomerEndDate = auth()->user()->can('project.customer_end_date');
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

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

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

                @if($canCustomerEndDate)
                    <span>
                        <strong>Customer End Date:</strong> {{ optional($project->customer_end_date)->format($globalDateFormat) ?? '--' }}
                    </span>
                @endcan

            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">Project Timeline</p>
                            <p class="text-xs text-bgray-500 dark:text-bgray-300">
                                {{ $projectTimeline['start_label'] }} to {{ $projectTimeline['end_label'] }}
                            </p>
                        </div>
                        <span class="text-sm font-bold {{ $projectTimeline['text_class'] }}">
                            {{ $projectTimeline['percentage'] }}%
                        </span>
                    </div>

                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-500">
                        <div
                            class="h-full rounded-full transition-all duration-300 {{ $projectTimeline['bar_class'] }}"
                            style="width: {{ $projectTimeline['percentage'] }}%;"
                        ></div>
                    </div>
                </div>

                @if ($canCustomerEndDate)
                    <div class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">Customer Timeline</p>
                                <p class="text-xs text-bgray-500 dark:text-bgray-300">
                                    {{ $customerTimeline['start_label'] }} to {{ $customerTimeline['end_label'] }}
                                </p>
                            </div>
                            <span class="text-sm font-bold {{ $customerTimeline['text_class'] }}">
                                {{ $customerTimeline['percentage'] }}%
                            </span>
                        </div>

                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-500">
                            <div
                                class="h-full rounded-full transition-all duration-300 {{ $customerTimeline['bar_class'] }}"
                                style="width: {{ $customerTimeline['percentage'] }}%;"
                            ></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- RIGHT: Status + Priority -->
        <div class="flex items-center gap-3">

            <!-- Status -->
            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $project->status ? 'bg-success-50 text-success-500' : 'bg-gray-100 text-gray-500' }}">
                {{ $project->projectStatus->name ?? 'No Status' }}
            </span>

            <!-- Project Priority -->
            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $priority['bg_class'] ?? 'bg-gray-100 text-gray-500' }} {{ $priority['bg_text'] ?? 'text-gray-500' }}">
                {{ $priority['label'] ?? '--' }}
            </span>

        </div>

    </div>
</div>
