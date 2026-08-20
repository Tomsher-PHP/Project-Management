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
                    <a href="{{ route('handoff_requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
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
                                    <x-sorting.sortable-column column="project.name" label="Project" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="purpose" label="Purpose" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="user.name" label="Requested By" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Target User</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="status" label="Status" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="created_at" label="Date" />
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
                                    $targetUser = $request->targetUser;
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
                                <tr class="group {{ config('assets.classes.table_row_hover') }}">

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
                                        <div class="flex min-w-[180px] items-center gap-3">
                                            <x-user-avatar :user="$requestUser" :image="$requestUser?->profile_image_url" :name="$requestUser?->name ?? 'Unknown User'" size="md" />
                                            <div>
                                                <p class="font-semibold text-bgray-900 dark:text-white">{{ $requestUser?->name ?? 'Unknown User' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex min-w-[180px] items-center gap-3">
                                            @if ($targetUser)
                                                <x-user-avatar :user="$targetUser" :image="$targetUser->profile_image_url" :name="$targetUser->name" size="md" />
                                                <p class="font-semibold text-bgray-900 dark:text-white">{{ $targetUser->name }}</p>
                                            @else
                                                <span class="text-sm text-bgray-800 dark:text-bgray-300">--</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[100px]">
                                            <span class="inline-flex rounded-lg px-3 py-1 text-xs font-semibold {{ $currentStatusClass }}">
                                                {{ $currentStatusLabel }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[120px] text-sm text-bgray-800 dark:text-bgray-300">
                                            @appDateTime($request->created_at)
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 text-center dark:border-darkblack-400">
                                        <div class="flex items-center justify-center gap-2">

                                            @if (auth()->user()->can('task.create') && !auth()->user()->can('request-task'))
                                                @if (in_array($request->status, [App\Models\HandoffRequest::STATUS_PENDING, App\Models\HandoffRequest::STATUS_NOTED]))
                                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-success-500 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Assign Task" data-task-create-open data-handoff-assign-btn data-handoff-request-id="{{ $request->id }}" data-project-id="{{ $request->project_id ?? '' }}" data-project-milestone-id="{{ $request->project_milestone_id ?? '' }}" data-project-sprint-id="{{ $request->project_sprint_id ?? '' }}" data-target-user-id="{{ $request->target_user_id ?? '' }}" data-description="{{ e($request->description ?? '') }}" data-purpose="{{ e($request->purpose ?? '') }}">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endif

                                            @can('request-task')
                                                @if ($request->target_user_id === auth()->id() && in_array($request->status, [App\Models\HandoffRequest::STATUS_PENDING, App\Models\HandoffRequest::STATUS_NOTED]))
                                                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-success-500 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Request Task" data-task-create-open data-task-create-request-type="self" data-handoff-assign-btn data-handoff-request-id="{{ $request->id }}" data-project-id="{{ $request->project_id ?? '' }}" data-project-milestone-id="{{ $request->project_milestone_id ?? '' }}" data-project-sprint-id="{{ $request->project_sprint_id ?? '' }}" data-target-user-id="{{ $request->target_user_id ?? '' }}" data-description="{{ e($request->description ?? '') }}" data-purpose="{{ e($request->purpose ?? '') }}">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endcan

                                            @can('handoff_request.note')
                                                @if ($request->status == App\Models\HandoffRequest::STATUS_PENDING)
                                                    <form method="POST" action="{{ route('handoff_requests.note', $request->id) }}" class="inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="button" onclick="confirmHandoffNote(this)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-success-500 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Mark as Noted">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            @if ($request->status == \App\Models\HandoffRequest::STATUS_PENDING && $request->user_id === auth()->id())
                                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300 group" title="Edit Request" data-handoff-edit-btn data-handoff-request-id="{{ $request->id }}" data-project-id="{{ $request->project_id ?? '' }}" data-project-milestone-id="{{ $request->project_milestone_id ?? '' }}" data-project-sprint-id="{{ $request->project_sprint_id ?? '' }}" data-source-task-id="{{ $request->source_task_id ?? '' }}" data-target-user-id="{{ $request->target_user_id ?? '' }}" data-purpose="{{ e($request->purpose ?? '') }}" data-description="{{ e($request->description ?? '') }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            @else
                                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300 group" title="View Details"
                                                    onclick="openHandoffViewModal({{ json_encode([
                                                        'date' => $request->created_at->format('Y-m-d H:i:s'),
                                                        'requestedBy' => $requestUser?->name ?? '--',
                                                        'targetUser' => $request->targetUser?->name ?? '--',
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
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="8" message="No handoff requests found." sub-message="There are no handoff requests available for your access level." />
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
            <div class="w-full max-w-4xl rounded-2xl bg-white p-6 shadow-xl dark:bg-darkblack-600">
                <div class="mb-4 flex items-center justify-between border-b border-bgray-200 pb-3 dark:border-darkblack-400">
                    <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Handoff Request Details</h3>
                    <button type="button" onclick="closeHandoffViewModal()" class="text-bgray-700 hover:text-error-300 transition">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Project</span>
                            <p id="viewModalProject" class="mt-1 flex items-center gap-2 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Purpose</span>
                            <p id="viewModalPurpose" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Milestone</span>
                            <p id="viewModalMilestone" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Sprint</span>
                            <p id="viewModalSprint" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Source Task</span>
                            <p id="viewModalSourceTask" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Status</span>
                            <p id="viewModalStatus" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Requested By</span>
                            <p id="viewModalRequestedBy" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                            <p id="viewModalDate" class="mt-0.5 text-xs text-bgray-600 dark:text-bgray-400"></p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Target User</span>
                            <p id="viewModalTargetUser" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Created Task</span>
                        <p id="viewModalCreatedTask" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white"></p>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-bgray-700 dark:text-bgray-300">Full Description</span>
                        <div id="viewModalDescription" class="mt-1 min-h-[100px] max-h-60 overflow-y-auto break-words rounded-lg bg-bgray-50 p-4 text-sm leading-relaxed text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-300 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:my-2 [&_ul]:space-y-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:my-2 [&_ol]:space-y-1 [&_li]:my-0.5 [&_li[data-list=bullet]]:list-disc [&_li[data-list=ordered]]:list-decimal [&_p]:my-1.5 [&_h1]:text-xl [&_h1]:font-extrabold [&_h1]:text-bgray-900 dark:[&_h1]:text-white [&_h1]:mt-3 [&_h1]:mb-1.5 [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-bgray-900 dark:[&_h2]:text-white [&_h2]:mt-2.5 [&_h2]:mb-1.5 [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-bgray-900 dark:[&_h3]:text-white [&_h3]:mt-2 [&_h3]:mb-1 [&_h4]:text-sm [&_h4]:font-bold [&_h4]:text-bgray-900 dark:[&_h4]:text-white [&_h4]:mt-1.5 [&_h4]:mb-1 [&_a]:text-success-500 [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-bgray-300 [&_blockquote]:pl-3 [&_blockquote]:italic"></div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="closeHandoffViewModal()" class="rounded-lg bg-bgray-200 px-4 py-2 text-sm font-semibold text-bgray-800 transition hover:bg-bgray-300 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        Close
                    </button>
                </div>
            </div>
        </div>

        @if (auth()->user()->can('task.create') || auth()->user()->can('request-task'))
            @include('tasks.partials.create-modal')
            <script id="task-create-dependencies" type="application/json">
                @json($taskCreateDependencies)
            </script>
        @endif

        @include('tasks.partials.handoff-create-modal')

    </main>
@endsection

@push('scripts')
    @if (auth()->user()->can('task.create') || auth()->user()->can('request-task'))
        @vite('resources/js/modules/task-list-create.js')
    @endif
    @vite('resources/js/modules/tasks/handoff.js')
    @vite('resources/js/modules/tasks/handoff-blend.js')
@endpush
