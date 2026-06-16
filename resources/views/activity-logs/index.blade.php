@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-activity-log-page>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                @can('activity_log.delete')
                    <button type="button" id="bulk-delete-btn" data-bulk-delete-url="{{ route('activity.log.bulkDelete') }}"
                        class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50"
                        disabled>
                        Bulk Delete
                    </button>
                @endcan

                <span id="selected-count" class="hidden">0 selected</span>
            </div>
        </div>

        @if (!empty($filteredProject))
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-success-200 bg-success-50/70 px-4 py-3 shadow-sm dark:border-success-900/30 dark:bg-darkblack-600">
                <div>
                    <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                        Showing activity for {{ $filteredProject->name }}
                    </p>
                    <p class="text-sm text-bgray-600 dark:text-bgray-300">
                        Includes this project and all related child records.
                    </p>
                </div>

                <a href="{{ route('activity.log') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-bgray-200 bg-white px-4 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                    Clear Filter
                </a>
            </div>
        @elseif (!empty($filteredTask))
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-success-200 bg-success-50/70 px-4 py-3 shadow-sm dark:border-success-900/30 dark:bg-darkblack-600">
                <div>
                    <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                        Showing activity for task {{ $filteredTask->name }}
                    </p>
                    <p class="text-sm text-bgray-600 dark:text-bgray-300">
                        Includes this task and its related comments, notes, and time logs.
                    </p>
                </div>

                <a href="{{ route('activity.log') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-bgray-200 bg-white px-4 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                    Clear Filter
                </a>
            </div>
        @endif

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                @can('activity_log.delete')
                                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                        <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    </th>
                                @endcan
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="log_name" label="Module" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="event" label="Action" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Record</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Changes</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">By</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="created_at" label="Logged At" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($activities as $activity)
                                @php
                                    $subject = $activity->subject;
                                    $subjectLabel = $subject?->name ?? ($subject?->title ?? ($subject?->original_name ?? ($subject?->file_name ?? ($subject?->project_code ?? ($subject?->customer_code ?? ($subject?->employee_id ?? ($activity->subject_id ? '#' . $activity->subject_id : '--')))))));
                                    $subjectType = $activity->subject_type ? \Illuminate\Support\Str::headline(class_basename($activity->subject_type)) : '--';
                                    $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'];
                                    $labels = collect($activity->getExtraProperty('labels', []));
                                    $changedFields = collect($activity->changes->get('attributes', []))
                                        ->keys()
                                        ->merge(collect($activity->changes->get('old', []))->keys())
                                        ->unique()
                                        ->reject(fn($field) => in_array($field, $ignoredFields, true))
                                        ->map(fn($field) => $labels->get($field, (string) \Illuminate\Support\Str::of($field)->replace('_id', '')->replace('_', ' ')->title()))
                                        ->values();
                                    $event = $activity->event ?? 'updated';
                                    $eventClasses = match ($event) {
                                        'created' => 'bg-success-50 text-success-400',
                                        'deleted' => 'bg-red-50 text-red-500',
                                        'restored' => 'bg-warning-50 text-warning-500',
                                        default => 'bg-blue-50 text-blue-500',
                                    };
                                    $currentModuleLabel = \Illuminate\Support\Str::headline($activity->log_name ?? 'default');
                                    $resolvedParentType = $activity->parent_type ? \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($activity->parent_type) ?? $activity->parent_type : null;
                                    $parentModuleLabel = $resolvedParentType ? \Illuminate\Support\Str::headline(class_basename($resolvedParentType)) : null;
                                    $milestoneLabel = $parentModuleLabel ?: $currentModuleLabel;
                                    $milestoneSubtitle = $parentModuleLabel && $parentModuleLabel !== $currentModuleLabel ? $currentModuleLabel : null;
                                    $changeSummary = match ($event) {
                                        'created' => $changedFields->isNotEmpty() ? 'Created ' . $changedFields->count() . ' ' . \Illuminate\Support\Str::plural('field', $changedFields->count()) . '.' : 'Created a new record.',
                                        'deleted' => $changedFields->isNotEmpty() ? 'Deleted record with ' . $changedFields->count() . ' tracked ' . \Illuminate\Support\Str::plural('field', $changedFields->count()) . '.' : 'Deleted a record.',
                                        'restored' => $changedFields->isNotEmpty() ? 'Restored ' . $changedFields->count() . ' ' . \Illuminate\Support\Str::plural('field', $changedFields->count()) . '.' : 'Restored a record.',
                                        default => $changedFields->isNotEmpty() ? 'Updated ' . $changedFields->count() . ' ' . \Illuminate\Support\Str::plural('field', $changedFields->count()) . '.' : 'Updated a record.',
                                    };
                                    $activityDescription = \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->description));
                                @endphp

                                <tr class="group {{ config('assets.classes.table_row_hover') }}">
                                    @can('activity_log.delete')
                                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                            <input type="checkbox" class="activity-checkbox h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" value="{{ $activity->id }}">
                                        </td>
                                    @endcan
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex flex-col rounded-md px-4 py-1.5">
                                            <span class="text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                {{ $milestoneLabel }}
                                            </span>

                                            @if ($milestoneSubtitle)
                                                <span class="mt-1 text-xs font-medium text-bgray-700 dark:text-bgray-300">
                                                    {{ $milestoneSubtitle }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $eventClasses }}">
                                            {{ \Illuminate\Support\Str::headline($event) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex flex-col">
                                            <span class="text-base font-semibold text-bgray-900 dark:text-white">
                                                {{ $subjectLabel }}
                                            </span>
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $subjectType }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex max-w-[280px] flex-col">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $changeSummary }}
                                            </span>

                                            @if ($changedFields->isNotEmpty())
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($changedFields->take(3) as $label)
                                                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                                            {{ $label }}
                                                        </span>
                                                    @endforeach

                                                    @if ($changedFields->count() > 3)
                                                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                                            +{{ $changedFields->count() - 3 }} more
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="mt-1 text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                    {{ $activityDescription }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $activity->causer?->name ?? 'System' }}
                                            </span>
                                            @if ($activity->causer?->email)
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ $activity->causer->email }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $activity->created_at?->timezone($globalTimezone)?->format($globalDateFormat) ?? '--' }}
                                            </span>
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $activity->created_at?->timezone($globalTimezone)?->format($globalTimeFormat) ?? '--' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex items-center space-x-2">
                                            <x-activity-log.view-button :activity="$activity" />

                                            @can('activity_log.delete')
                                                <x-delete-form :action="route('activity.log.destroy', $activity->id)" form-class="activity-log-delete-form" />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="auth()->user()->can('activity_log.delete') ? 8 : 7" message="No activity logs found." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$activities" :per-page="$perPage" />
        </section>
    </main>

    <x-filters.drawer>
        @if (!empty($filteredProject))
            <input type="hidden" name="project_id" value="{{ $filteredProject->id }}">
        @elseif (!empty($filteredTask))
            <input type="hidden" name="task_id" value="{{ $filteredTask->id }}">
        @endif

        <x-filters.input-search name="search" label="Search" />
        <x-filters.multi-select name="log_name" label="Module" :options="$logNames" />
        <x-filters.select name="event" label="Action" :options="$eventOptions" />
        <x-filters.multi-select name="causer_id" label="User" :options="$causers" />

        <div class="flex flex-col gap-2">
            <label for="date_from" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">From Date</label>
            <input type="text" id="date_from" name="date_from" value="{{ request('date_from') }}" class="datepicker w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="Y-m-d" data-alt-input="true" data-alt-format="{{ $globalDateFormat }}" placeholder="Select start date" autocomplete="off">
        </div>

        <div class="flex flex-col gap-2">
            <label for="date_to" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">To Date</label>
            <input type="text" id="date_to" name="date_to" value="{{ request('date_to') }}" class="datepicker w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="Y-m-d" data-alt-input="true" data-alt-format="{{ $globalDateFormat }}" placeholder="Select end date" autocomplete="off">
        </div>
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/activity-logs.js')
@endpush
