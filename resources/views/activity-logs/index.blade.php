@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]" data-activity-log-page>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-filters.button />

            @can('activity_log.delete')
                <div class="flex flex-wrap items-center justify-end gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span id="selected-count" class="inline-flex h-11 items-center rounded-lg bg-bgray-100 px-4 text-sm font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                        0 selected
                    </span>

                    <button
                        type="button"
                        id="bulk-delete-btn"
                        data-bulk-delete-url="{{ route('activity.log.bulkDelete') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-red-200 bg-red-50 px-5 text-sm font-semibold leading-none text-red-600 shadow-sm transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-200 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:bg-bgray-100 disabled:text-bgray-400 disabled:shadow-none dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-500 dark:disabled:text-bgray-500"
                        disabled
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Bulk Delete</span>
                    </button>
                </div>
            @endcan
        </div>

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td>
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    @can('activity_log.delete')
                                        <td class="px-6 py-5 xl:px-0">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-success-500 focus:ring-success-500">
                                        </td>
                                    @endcan
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="log_name" label="Module" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="event" label="Action" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Record</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Changes</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">By</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="created_at" label="Logged At" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </td>
                                </tr>

                                @php
                                    $startNumber = ($activities->currentPage() - 1) * $activities->perPage();
                                @endphp

                                @forelse ($activities as $activity)
                                    @php
                                        $subject = $activity->subject;
                                        $subjectLabel = $subject?->name ?? ($subject?->title ?? ($subject?->project_code ?? ($subject?->customer_code ?? ($subject?->employee_id ?? ($activity->subject_id ? '#' . $activity->subject_id : '--')))));
                                        $subjectType = $activity->subject_type ? \Illuminate\Support\Str::headline(class_basename($activity->subject_type)) : '--';
                                        $changedFields = collect($activity->changes->get('attributes', []))
                                            ->except(['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'])
                                            ->keys();
                                        $event = $activity->event ?? 'updated';
                                        $eventClasses = match ($event) {
                                            'created' => 'bg-success-50 text-success-400',
                                            'deleted' => 'bg-red-50 text-red-500',
                                            'restored' => 'bg-warning-50 text-warning-500',
                                            default => 'bg-blue-50 text-blue-500',
                                        };
                                    @endphp

                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                {{ $startNumber + $loop->iteration }}
                                            </span>
                                        </td>
                                        @can('activity_log.delete')
                                            <td class="px-6 py-5 xl:px-0">
                                                <input type="checkbox" class="activity-checkbox rounded border-gray-300 text-success-500 focus:ring-success-500" value="{{ $activity->id }}">
                                            </td>
                                        @endcan
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                {{ \Illuminate\Support\Str::headline($activity->log_name ?? 'default') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $eventClasses }}">
                                                {{ \Illuminate\Support\Str::headline($event) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex flex-col">
                                                <span class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $subjectLabel }}
                                                </span>
                                                <span class="text-sm text-bgray-500 dark:text-bgray-300">
                                                    {{ $subjectType }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex max-w-[280px] flex-col">
                                                @if ($changedFields->isNotEmpty())
                                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                        {{ $changedFields->take(3)->map(fn($field) => \Illuminate\Support\Str::headline($field))->implode(', ') }}
                                                        @if ($changedFields->count() > 3)
                                                            +{{ $changedFields->count() - 3 }} more
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                        {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->description)) }}
                                                    </span>
                                                @endif

                                                <span class="text-sm text-bgray-500 dark:text-bgray-300">
                                                    {{ $activity->description }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                    {{ $activity->causer?->name ?? 'System' }}
                                                </span>
                                                @if ($activity->causer?->email)
                                                    <span class="text-sm text-bgray-500 dark:text-bgray-300">
                                                        {{ $activity->causer->email }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                    {{ $activity->created_at?->timezone($globalTimezone)?->format($globalDateFormat) ?? '--' }}
                                                </span>
                                                <span class="text-sm text-bgray-500 dark:text-bgray-300">
                                                    {{ $activity->created_at?->timezone($globalTimezone)?->format($globalTimeFormat) ?? '--' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center space-x-2">
                                                @can('activity_log.delete')
                                                    <x-delete-form
                                                        :action="route('activity.log.destroy', $activity->id)"
                                                        form-class="activity-log-delete-form"
                                                    />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="auth()->user()->can('activity_log.delete') ? 9 : 8" message="No activity logs found." />
                                @endforelse
                            </table>
                        </div>

                        <x-pagination :paginator="$activities" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
    </main>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Search" />
        <x-filters.multi-select name="log_name" label="Module" :options="$logNames" />
        <x-filters.select name="event" label="Action" :options="$eventOptions" />
        <x-filters.multi-select name="causer_id" label="User" :options="$causers" />

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">From Date</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">To Date</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/activity-logs.js')
@endpush
