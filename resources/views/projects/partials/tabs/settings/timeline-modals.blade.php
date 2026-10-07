<!-- Create Timeline Modal -->
<x-form-modal modalId="create-timeline-modal" module="Project Timeline" formId="projectTimelineCreateForm" action="{{ route('projects.timelines.store', $project->id) }}" button="Create Timeline" maxWidth="max-w-5xl">
    <!-- Name and Type naturally fall into 2 columns of form-modal-fields -->
    <div>
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
        <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
    </div>

    <div>
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Type <x-red-star /></label>
        <select name="type" required class="tom-select-no-search w-full">
            @foreach(config('project_constants.project_timeline_types') as $key => $label)
                @if($key !== 'original')
                    <option value="{{ $key }}" {{ $key === \App\Models\ProjectTimeline::DEFAULT_TYPE ? 'selected' : '' }}>{{ $label }}</option>
                @endif
            @endforeach
        </select>
    </div>

    <!-- Dates (3 columns) -->
    @php
        $latestTimeline = $project->projectTimelines->where('status', '!=', \App\Models\ProjectTimeline::STATUS_CANCELLED)->sortByDesc('end_date')->first();
        $suggestedStartDate = $latestTimeline && $latestTimeline->end_date 
            ? \Carbon\Carbon::parse($latestTimeline->end_date)->addDay()->format('Y-m-d')
            : ($project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('Y-m-d') : now()->format('Y-m-d'));
        $suggestedEndDate = \Carbon\Carbon::parse($suggestedStartDate)->addDays(30)->format('Y-m-d');
        $minDate = $latestTimeline && $latestTimeline->end_date ? \Carbon\Carbon::parse($latestTimeline->end_date)->addDay()->format('Y-m-d') : '';
    @endphp
    <div class="grid grid-cols-3 gap-4" style="grid-column: 1 / -1;">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Start Date</label>
            <input type="text" name="start_date" value="{{ $suggestedStartDate }}" data-min-date="{{ $minDate }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">End Date</label>
            <input type="text" name="end_date" value="{{ $suggestedEndDate }}" data-min-date="{{ $minDate }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        @can('project.customer_end_date')
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Customer End Date</label>
                <input type="text" name="customer_end_date" data-min-date="{{ $minDate }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        @else
            <div></div> <!-- To maintain grid -->
        @endcan
    </div>

    <!-- Estimates -->
    <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="0" />

    @can('project.customer_end_date')
        <x-forms.estimated-time-input label="Customer Estimate Time" name="customer_estimate_minutes" :total-minutes="0" />
    @else
        <div></div>
    @endcan

    <div style="grid-column: 1 / -1;">
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Notes</label>
        <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>
    </div>
</x-form-modal>

<!-- Edit Timeline Modal -->
<x-form-modal modalId="edit-timeline-modal" module="Project Timeline" formId="projectTimelineEditForm" action="" method="PUT" button="Update Timeline" maxWidth="max-w-5xl">
    <div>
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
        <input type="text" name="name" id="edit_timeline_name" required class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
    </div>

    <div>
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Type <x-red-star /></label>
        <select name="type" id="edit_timeline_type" required class="tom-select-no-search w-full">
            @foreach(config('project_constants.project_timeline_types') as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>



    <div class="grid grid-cols-3 gap-4" style="grid-column: 1 / -1;">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Start Date</label>
            <input type="text" name="start_date" id="edit_timeline_start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">End Date</label>
            <input type="text" name="end_date" id="edit_timeline_end_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        @can('project.customer_end_date')
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Customer End Date</label>
                <input type="text" name="customer_end_date" id="edit_timeline_customer_end_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        @else
            <div></div>
        @endcan
    </div>

    <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" id="edit_timeline_estimated_time_minutes" :total-minutes="0" />

    @can('project.customer_end_date')
        <x-forms.estimated-time-input label="Customer Estimate Time" name="customer_estimate_minutes" id="edit_timeline_customer_estimate_minutes" :total-minutes="0" />
    @else
        <div></div>
    @endcan

    <div style="grid-column: 1 / -1;">
        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Notes</label>
        <textarea name="notes" id="edit_timeline_notes" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>
    </div>
</x-form-modal>

