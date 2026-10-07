@php
    $projectTimelines = $projectTimelines ?? $project->projectTimelines->sortBy('sort_order');
@endphp
<div class="w-full">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white">
            Project Timelines
        </h3>
        @if ($canEdit)
            @can('project.edit')
                <button type="button" data-target="#create-timeline-modal" data-module="Project Timeline" class="add-timeline-btn inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
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
                @forelse ($projectTimelines as $timeline)
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
                                    @php
                                        $prevTimeline = $projectTimelines
                                            ->where('status', '!=', \App\Models\ProjectTimeline::STATUS_CANCELLED)
                                            ->where('sort_order', '<', $timeline->sort_order)
                                            ->sortByDesc('sort_order')
                                            ->first();
                                        
                                        $nextTimeline = $projectTimelines
                                            ->where('status', '!=', \App\Models\ProjectTimeline::STATUS_CANCELLED)
                                            ->where('sort_order', '>', $timeline->sort_order)
                                            ->sortBy('sort_order')
                                            ->first();
                                            
                                        $minDate = $prevTimeline && $prevTimeline->end_date ? \Carbon\Carbon::parse($prevTimeline->end_date)->addDay()->format('Y-m-d') : '';
                                        $maxDate = $nextTimeline && $nextTimeline->start_date ? \Carbon\Carbon::parse($nextTimeline->start_date)->subDay()->format('Y-m-d') : '';
                                    @endphp
                                    @if ($timeline->status === \App\Models\ProjectTimeline::STATUS_PLANNED)
                                        @php
                                            $hasActive = $projectTimelines->where('status', \App\Models\ProjectTimeline::STATUS_ACTIVE)->count() > 0;
                                            $hasPrevNotCompleted = $projectTimelines
                                                ->where('sort_order', '<', $timeline->sort_order)
                                                ->whereNotIn('status', [\App\Models\ProjectTimeline::STATUS_COMPLETED, \App\Models\ProjectTimeline::STATUS_CANCELLED])
                                                ->count() > 0;
                                            $canActivate = !$hasActive && !$hasPrevNotCompleted;
                                        @endphp
                                        <form action="{{ route('projects.timelines.activate', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}" method="POST" class="ajax-form inline-block" title="{{ $canActivate ? '' : 'Cannot activate until previous timelines are completed and no active timeline exists.' }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold {{ $canActivate ? 'bg-success-300 text-white hover:bg-success-400' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}" {{ $canActivate ? '' : 'disabled' }}>
                                                Activate
                                            </button>
                                        </form>
                                    @elseif ($timeline->status === \App\Models\ProjectTimeline::STATUS_ACTIVE)
                                        <form action="{{ route('projects.timelines.complete', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}" method="POST" class="ajax-form inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded bg-blue-500 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-600">
                                                Mark as Completed
                                            </button>
                                        </form>
                                    @endif
                                    <x-edit-button data-target="#edit-timeline-modal" data-module="Project Timeline" data-timeline="{{ json_encode($timeline) }}" data-min-date="{{ $minDate }}" data-max-date="{{ $maxDate }}" data-action="{{ route('projects.timelines.update', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}" class="edit-timeline-btn cursor-pointer" />
                                    @if ($timeline->type !== 'original')
                                        <x-delete-form action="{{ route('projects.timelines.destroy', ['project' => $project->id, 'projectTimeline' => $timeline->id]) }}" ajax="true" renderTarget="#project-timelines-container" renderMode="replace_inner" />
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
