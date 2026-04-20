@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        @php
            $tabs = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('tasks.requests.index', ['status' => $status]) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-200 dark:hover:bg-darkblack-500' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Task</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested By</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Due Date</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($tasks as $task)
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[220px]">
                                            <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-bgray-900 transition hover:text-success-300 dark:text-white dark:hover:text-success-300">
                                                {{ $task->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">{{ $task->code }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[180px]">
                                            @if ($task->project && auth()->user()?->can('project.view'))
                                                <a href="{{ route('projects.edit', $task->project_id) }}" class="text-sm font-medium text-bgray-700 transition hover:text-success-300 dark:text-bgray-100 dark:hover:text-success-300">
                                                    {{ $task->project->name }}
                                                </a>
                                            @elseif ($task->project)
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-100">{{ $task->project->name }}</p>
                                            @else
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-100">--</p>
                                            @endif
                                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">{{ $task->projectModule?->name ?? 'No module' }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-100">{{ $task->currentAssignee?->name ?? '--' }}</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $task->request_status === 'pending' ? 'bg-warning-50 text-warning-300' : ($task->request_status === 'approved' ? 'bg-success-50 text-success-300' : 'bg-error-50 text-error-300') }}">
                                            {{ ucfirst($task->request_status) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-600 dark:text-bgray-200">{{ $task->due_date?->format($globalDateFormat) ?? '--' }}</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        @if ($task->request_status === 'pending')
                                            <div class="flex min-w-[320px] flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('tasks.requests.action', [$task, 'approve']) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('tasks.requests.action', [$task, 'reject']) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="reason" class="w-44 rounded-lg border border-bgray-200 px-3 py-2 text-xs focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Reject reason" required>
                                                    <button type="submit" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif ($task->request_status === 'rejected')
                                            <span class="text-xs text-bgray-500 dark:text-bgray-300" title="{{ $task->rejection_reason }}">{{ \Illuminate\Support\Str::limit($task->rejection_reason ?? '--', 60) }}</span>
                                        @else
                                            <span class="text-xs text-bgray-500 dark:text-bgray-300">No action needed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="6" message="No {{ strtolower($tabs[$selectedStatus]) }} task requests found." sub-message="There are no task requests to display for this tab." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$tasks" :per-page="$perPage" />
        </section>
    </main>
@endsection
