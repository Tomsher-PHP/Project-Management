<div class="w-full">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white">
            Project Timelines
        </h3>
        @if ($canEdit)
            @can('project.edit')
                <button type="button" data-target="#create-timeline-modal" data-module="Project Timeline" class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Timeline
                </button>
            @endcan
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-darkblack-400">
        <table class="w-full text-left text-sm text-gray-500 dark:text-bgray-50">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Name</th>
                    <th scope="col" class="px-6 py-3">Type</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Dates</th>
                    <th scope="col" class="px-6 py-3">Estimates</th>
                    @if ($canEdit)
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($project->projectTimelines as $timeline)
                    <tr class="border-b bg-white hover:bg-gray-50 dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:bg-darkblack-500">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $timeline->name }}
                        </td>
                        <td class="px-6 py-4 capitalize">
                            {{ config('project_constants.project_timeline_types.' . $timeline->type, $timeline->type) }}
                        </td>
                        <td class="px-6 py-4 capitalize">
                            {{ config('project_constants.project_timeline_statuses.' . $timeline->status, 'Unknown') }}
                        </td>
                        <td class="px-6 py-4">
                            <div><span class="font-semibold">Start:</span> @appDate($timeline->start_date)</div>
                            <div><span class="font-semibold">End:</span> @appDate($timeline->end_date)</div>
                            @can('project.customer_end_date')
                                <div><span class="font-semibold">Cust End:</span> @appDate($timeline->customer_end_date)</div>
                            @endcan
                        </td>
                        <td class="px-6 py-4">
                            <div><span class="font-semibold">Est:</span> {{ $timeline->estimated_time_seconds ? formatSecondsToHoursMinutes($timeline->estimated_time_seconds) : '--' }}</div>
                            @can('project.customer_end_date')
                                <div><span class="font-semibold">Cust Est:</span> {{ $timeline->customer_estimate_seconds ? formatSecondsToHoursMinutes($timeline->customer_estimate_seconds) : '--' }}</div>
                            @endcan
                        </td>
                        @if ($canEdit)
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" data-target="#edit-timeline-modal" data-module="Project Timeline" data-timeline="{{ json_encode($timeline) }}" data-action="{{ route('projects.timelines.update', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}" class="modal-open edit-timeline-btn text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Edit
                                    </button>
                                    @if ($timeline->type !== 'original')
                                        <button type="button" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 delete-timeline-btn" data-url="{{ route('projects.timelines.destroy', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No timelines found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
