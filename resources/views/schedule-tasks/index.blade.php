@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-schedule-task-page>
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('task.create')
                <x-button.create-button type="button" data-schedule-task-open title="Schedule a recurring task" label="Schedule Task" />
            @endcan

            <x-filters.button />
        </div>

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                @foreach (['Name', 'Project', 'Assignee', 'Frequency', 'Start Date', 'End Date', 'Status', 'Created By', 'Actions'] as $heading)
                                    <th class="border-b border-bgray-200 px-4 py-4 text-left text-base font-medium text-bgray-600 dark:border-b-darkblack-400 dark:text-bgray-50">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($taskSchedules as $taskSchedule)
                                @php
                                    $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
                                    $shortDayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
                                    $frequencyLabel = match ($taskSchedule->frequency_type) {
                                        \App\Models\TaskSchedule::FREQUENCY_WEEKDAYS => collect($taskSchedule->week_days)->map(fn($day) => $shortDayNames[$day] ?? null)->filter()->join(', '),
                                        \App\Models\TaskSchedule::FREQUENCY_WEEKLY => 'Every ' . ($dayNames[$taskSchedule->weekly_day] ?? 'week'),
                                        \App\Models\TaskSchedule::FREQUENCY_MONTHLY => 'Monthly (Days: ' . collect($taskSchedule->month_days)->join(', ') . ')',
                                        default => 'Daily',
                                    };
                                @endphp
                                <tr class="border-b border-bgray-200 {{ config('assets.classes.table_row_hover') }} dark:border-darkblack-400">
                                    <td class="px-4 py-4 font-semibold text-bgray-900 dark:text-white">{{ $taskSchedule->name }}</td>
                                    <td class="px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->project?->name ?? '--' }}</td>
                                    <td class="px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->currentAssignee?->name ?? '--' }}</td>
                                    <td class="px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $frequencyLabel }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->start_date?->format(config('constants.date_format')) ?? '--' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->end_date?->format(config('constants.date_format')) ?? 'No End Date' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $taskSchedule->is_active ? 'bg-success-50 text-success-400' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                            {{ $taskSchedule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->addedBy?->name ?? '--' }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            @can('task.edit')
                                                <button type="button" class="rounded-lg border border-bgray-200 px-3 py-1.5 text-sm font-medium text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-300" data-schedule-task-edit data-url="{{ route('schedule-tasks.edit', $taskSchedule) }}">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('task.delete')
                                                <button type="button" class="rounded-lg border px-3 py-1.5 text-sm font-medium transition {{ $taskSchedule->is_active ? 'border-red-200 text-red-500 hover:bg-red-50' : 'border-success-200 text-success-400 hover:bg-success-50' }}" data-schedule-task-toggle data-url="{{ route('schedule-tasks.toggle-status', $taskSchedule) }}" data-active="{{ $taskSchedule->is_active ? 'true' : 'false' }}">
                                                    {{ $taskSchedule->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                                <button type="button" class="rounded-lg border border-bgray-200 px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50 dark:border-darkblack-400" data-schedule-task-delete data-url="{{ route('schedule-tasks.destroy', $taskSchedule) }}">
                                                    Delete
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="9" message="No scheduled tasks found." sub-message="Create a schedule to automate recurring task setup." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$taskSchedules" :per-page="$perPage" />
        </section>

        @can('task.create')
            @include('schedule-tasks.partials.create-modal', ['taskSchedule' => null])
        @endcan
        <div data-schedule-task-edit-host></div>
    </main>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Name" />
        <x-filters.date-range />
        <x-filters.multi-select name="project_id" label="Project" :options="$filterProjects" />
        <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$filterAssignees" />
        <x-filters.select name="status" label="Status" :options="['active' => 'Active', 'disabled' => 'Disabled']" />
    </x-filters.drawer>

    <script id="schedule-task-dependencies" type="application/json">@json($scheduleDependencies)</script>
@endsection

@push('scripts')
    @vite('resources/js/modules/tasks/schedule-task.js')
@endpush
