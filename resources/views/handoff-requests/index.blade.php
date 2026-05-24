@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-task-create-root>
        @php
            $tabs = [
                'pending' => 'Pending',
                'noted' => 'Noted',
                'assigned' => 'Assigned',
            ];
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />
            </div>

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('handoff_requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-200 dark:hover:bg-darkblack-500' }}">
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
                                    <x-sorting.sortable-column column="created_at" label="Date" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="user.name" label="Requested By" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="project.name" label="Project" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="purpose" label="Purpose" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Description</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="status" label="Status" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-center dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($handoffRequests as $request)
                                @php
                                    $requestUser = $request->user;
                                    $statusClasses = [
                                        0 => 'bg-warning-50 text-warning-300',
                                        1 => 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300',
                                        2 => 'bg-success-50 text-success-300',
                                    ];
                                    $statusLabels = [
                                        0 => 'Pending',
                                        1 => 'Noted',
                                        2 => 'Assigned',
                                    ];
                                    $currentStatusClass = $statusClasses[$request->status] ?? 'bg-gray-50 text-gray-500';
                                    $currentStatusLabel = $statusLabels[$request->status] ?? 'Unknown';
                                @endphp
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[120px] text-sm text-bgray-800 dark:text-bgray-300">
                                            @appDateTime($request->created_at)
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex min-w-[180px] items-center gap-3">
                                            <img src="{{ $requestUser?->profile_image_url ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $requestUser?->name ?? 'Unknown User' }}" class="h-9 w-9 rounded-full object-cover">
                                            <div>
                                                <p class="font-semibold text-bgray-900 dark:text-white">{{ $requestUser?->name ?? 'Unknown User' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[150px] flex items-center gap-2 font-semibold text-bgray-900 dark:text-white">

                                            @if ($request->project)
                                                <a href="{{ route('projects.edit', $request->project) }}" class="inline-flex min-w-0 flex-col items-start gap-1 transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                                                    <span class="inline-flex min-w-0 items-center gap-2">
                                                        <x-project-flow-icon :flow="$request->project->project_flow" size="sm" />
                                                        <span class="truncate" title="{{ $request->project->name }}">{{ \Illuminate\Support\Str::limit($request->project->name, 20, '..') }}</span>
                                                    </span>
                                                    <span class="pl-6 text-xs font-normal text-[#7C97C1] dark:text-bgray-300">
                                                        {{ $request->project->project_code ?: '--' }}
                                                    </span>
                                                </a>
                                            @else
                                                <span class="truncate">--</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[120px] text-sm text-bgray-800 dark:text-bgray-300">
                                            {{ $request->purpose ?? '--' }}
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[200px] text-sm text-bgray-800 dark:text-bgray-300" title="{{ $request->description }}">
                                            {{ \Illuminate\Support\Str::limit($request->description ?? '--', 60) }}
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[100px]">
                                            <span class="inline-flex rounded-lg px-3 py-1 text-xs font-semibold {{ $currentStatusClass }}">
                                                {{ $currentStatusLabel }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 text-center dark:border-darkblack-400">
                                        <div class="flex items-center justify-center gap-2">
                                            @if (auth()->user()->can('task.create'))
                                                @if (in_array($request->status, [App\Models\HandoffRequest::STATUS_PENDING, App\Models\HandoffRequest::STATUS_NOTED]))
                                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-success-500 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Assign Task" data-task-create-open data-handoff-assign-btn data-handoff-request-id="{{ $request->id }}"
                                                        data-project-id="{{ $request->project_id ?? '' }}" data-project-milestone-id="{{ $request->project_milestone_id ?? '' }}" data-project-sprint-id="{{ $request->project_sprint_id ?? '' }}" data-description="{{ $request->description ?? '' }}" data-purpose="{{ $request->purpose ?? '' }}">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endif

                                            @can('handoff_request.note')
                                                @if ($request->status == App\Models\HandoffRequest::STATUS_PENDING)
                                                    <form method="POST" action="{{ route('handoff_requests.note', $request->id) }}" class="inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="button" onclick="confirmHandoffNote(this)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-info-500 shadow-sm transition duration-200 hover:border-info-300 hover:bg-info-50 hover:text-info-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Mark as Noted">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-500 shadow-sm transition duration-200 hover:border-bgray-300 hover:bg-bgray-50 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="View Details"
                                                onclick="openHandoffViewModal({{ json_encode([
                                                    'date' => $request->created_at->format('Y-m-d H:i:s'),
                                                    'requestedBy' => $requestUser?->name ?? '--',
                                                    'project' => $request->project?->name ?? '--',
                                                    'projectFlow' => $request->project?->project_flow ?? '',
                                                    'milestone' => $request->projectMilestone?->name ?? '--',
                                                    'sprint' => $request->projectSprint?->name ?? '--',
                                                    'sourceTask' => $request->sourceTask?->name ?? '--',
                                                    'createdTask' => $request->createdTask?->name ?? '--',
                                                    'purpose' => $request->purpose ?? '--',
                                                    'status' => $currentStatusLabel,
                                                    'description' => $request->description ?? '--',
                                                ]) }})">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="7" message="No handoff requests found." sub-message="There are no handoff requests available for your access level." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$handoffRequests" :per-page="$perPage" />
        </section>

        <x-filters.drawer>
            <input type="hidden" name="request_status" value="{{ $selectedStatus }}">
            <x-filters.input-search name="search" label="Search" />

            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">Date Range</label>
                <input type="text" name="date_range" value="{{ request('date_range') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" placeholder="Select date range">
            </div>

            <x-filters.multi-select name="user_id" label="Requested By" :options="$users" />
            <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
            <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$milestones" />
            <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$sprints" />
            <x-filters.select name="purpose" label="Purpose" :options="$purposes" />
        </x-filters.drawer>

        <!-- View Details Modal -->
        <div id="handoffViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-darkblack-600">
                <div class="mb-4 flex items-center justify-between border-b border-bgray-200 pb-3 dark:border-darkblack-400">
                    <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Handoff Request Details</h3>
                    <button type="button" onclick="closeHandoffViewModal()" class="text-bgray-500 hover:text-error-300 transition">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Date</span>
                            <p id="viewModalDate" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Requested By</span>
                            <p id="viewModalRequestedBy" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Project</span>
                            <p id="viewModalProject" class="mt-1 flex items-center gap-2 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Milestone</span>
                            <p id="viewModalMilestone" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Sprint</span>
                            <p id="viewModalSprint" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Source Task</span>
                            <p id="viewModalSourceTask" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Created Task</span>
                            <p id="viewModalCreatedTask" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Status</span>
                            <p id="viewModalStatus" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Purpose</span>
                        <p id="viewModalPurpose" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-bgray-500 dark:text-bgray-300">Full Description</span>
                        <div id="viewModalDescription" class="mt-1 rounded-lg bg-bgray-50 p-3 text-sm text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-100" style="white-space: pre-wrap;"></div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="closeHandoffViewModal()" class="rounded-lg bg-bgray-200 px-4 py-2 text-sm font-semibold text-bgray-800 transition hover:bg-bgray-300 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:bg-darkblack-400">
                        Close
                    </button>
                </div>
            </div>
        </div>

        @can('task.create')
            @include('tasks.partials.create-modal')
            <script id="task-create-dependencies" type="application/json">
                @json($taskCreateDependencies)
            </script>
        @endcan

    </main>
@endsection

@push('scripts')
    @can('task.create')
        @vite('resources/js/modules/task-list-create.js')
    @endcan
    @vite('resources/js/modules/tasks/handoff-blend.js')
@endpush
