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
                                @foreach (['Name', 'Project', 'Assignee', 'Frequency', 'Start Date', 'End Date', 'Created By', 'Is Active', 'Actions'] as $heading)
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
                                    <td class="px-4 py-4 text-bgray-600 dark:text-bgray-300">{{ $taskSchedule->addedBy?->name ?? '--' }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <button type="button" @cannot('task.edit') disabled @endcannot class="status-toggle switch-btn {{ $taskSchedule->is_active ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed" data-schedule-task-toggle data-url="{{ route('schedule-tasks.toggle-status', $taskSchedule) }}" data-active="{{ $taskSchedule->is_active ? 'true' : 'false' }}" data-entity="schedule-task"
                                                role="switch" aria-checked="{{ $taskSchedule->is_active ? 'true' : 'false' }}">
                                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            @can('task.edit')
                                                <button type="button" class="group inline-flex h-8 w-8 items-center justify-center border rounded-lg bg-bgray-100 transition duration-200 hover:bg-bgray-200 dark:bg-darkblack-500 dark:hover:bg-darkblack-400" data-schedule-task-edit data-url="{{ route('schedule-tasks.edit', $taskSchedule) }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-bgray-600 transition group-hover:text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                    </svg>
                                                </button>
                                            @endcan
                                            @can('task.delete')
                                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 shadow-sm transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white dark:border-red-900/40 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-error-300" data-schedule-task-delete data-url="{{ route('schedule-tasks.destroy', $taskSchedule) }}" title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-current transition" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z"></path>
                                                    </svg>
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
        <x-filters.select name="status" label="Is Active" :options="['active' => 'Active', 'disabled' => 'Disabled']" />
    </x-filters.drawer>

    <script id="schedule-task-dependencies" type="application/json">@json($scheduleDependencies)</script>
@endsection

@push('scripts')
    @vite('resources/js/modules/tasks/schedule-task.js')
@endpush
