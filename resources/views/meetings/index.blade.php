@extends('layouts.master')

@section('page-content')
    <div class="w-full">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                    Meetings
                </h2>
                <p class="text-sm text-bgray-500 dark:text-bgray-300">
                    Manage and track project and team meetings.
                </p>
            </div>

            @can('meeting.create')
                <div>
                    <button type="button" id="open_create_meeting_modal_btn" class="flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Meeting
                    </button>
                </div>
            @endcan
        </div>

        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="mb-5 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filters Card -->
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm dark:bg-darkblack-600">
            <form method="GET" action="{{ route('meetings.index') }}" class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="min-w-[200px] flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or location..." class="w-full rounded-lg border border-bgray-300 px-3.5 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>

                <!-- Project Filter -->
                <div class="min-w-[160px]">
                    <select name="project_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Meeting Type Filter -->
                <div class="min-w-[140px]">
                    <select name="meeting_type_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Types</option>
                        @foreach ($meetingTypes as $type)
                            <option value="{{ $type->id }}" {{ request('meeting_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="min-w-[140px]">
                    <select name="meeting_status_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach ($meetingStatuses as $status)
                            <option value="{{ $status->id }}" {{ request('meeting_status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-lg bg-bgray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-bgray-800 dark:bg-darkblack-500 dark:hover:bg-darkblack-400 transition">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'project_id', 'meeting_type_id', 'meeting_status_id']))
                        <a href="{{ route('meetings.index') }}" class="rounded-lg border border-bgray-300 px-3 py-2 text-xs font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Meetings Table Card -->
        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-bgray-200 bg-bgray-50 text-xs font-semibold uppercase text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            <th class="px-6 py-4">Title</th>
                            <th class="px-6 py-4">Project</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Organizer</th>
                            <th class="px-6 py-4">Start / End</th>
                            <th class="px-6 py-4">Participants</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bgray-200 text-sm dark:divide-darkblack-400">
                        @forelse ($meetings as $meeting)
                            @php
                                $statusColor = $meeting->meetingStatus?->color ?: '#3B82F6';
                                $typeColor = $meeting->meetingType?->color ?: '#10B981';
                            @endphp
                            <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/50 transition">
                                {{-- Title & Link --}}
                                <td class="px-6 py-4">
                                    <a href="{{ route('meetings.show', $meeting->id) }}" class="font-semibold text-bgray-900 hover:text-success-400 dark:text-white">
                                        {{ $meeting->title }}
                                    </a>
                                    @if ($meeting->url)
                                        <div class="mt-0.5">
                                            <a href="{{ $meeting->url }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-500 hover:underline">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                Join Link
                                            </a>
                                        </div>
                                    @endif
                                </td>

                                <!-- Project -->
                                <td class="px-6 py-4">
                                    @if ($meeting->project)
                                        <span class="font-medium text-bgray-700 dark:text-bgray-300">
                                            {{ $meeting->project->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-bgray-400">--</span>
                                    @endif
                                </td>

                                <!-- Type -->
                                <td class="px-6 py-4">
                                    @if ($meeting->meetingType)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $typeColor }}15; color: {{ $typeColor }};">
                                            <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $typeColor }};"></span>
                                            {{ $meeting->meetingType->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-bgray-400">--</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    @if ($meeting->meetingStatus)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $statusColor }}15; color: {{ $statusColor }};">
                                            <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                            {{ $meeting->meetingStatus->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-bgray-400">--</span>
                                    @endif
                                </td>

                                <!-- Organizer -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-user-avatar :user="$meeting->organizer" size="xs" />
                                        <span class="text-xs font-medium text-bgray-800 dark:text-bgray-200">
                                            {{ $meeting->organizer?->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Start / End -->
                                <td class="px-6 py-4 text-xs text-bgray-600 dark:text-bgray-300">
                                    <div>@appDateTime($meeting->start_at)</div>
                                    <div class="text-[11px] text-bgray-400">to @appTime($meeting->end_at)</div>
                                </td>

                                <!-- Participants -->
                                <td class="px-6 py-4">
                                    <span class="rounded-md bg-bgray-100 px-2 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $meeting->participants->count() }} Participant(s)
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- View -->
                                        <a href="{{ route('meetings.show', $meeting->id) }}" class="rounded p-1.5 text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white" title="View Details">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <!-- Edit -->
                                        @can('meeting.edit')
                                            <button type="button" class="edit-meeting-btn rounded p-1.5 text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white" data-id="{{ $meeting->id }}" data-url="{{ route('meetings.edit', $meeting->id) }}" data-update-url="{{ route('meetings.update', $meeting->id) }}" title="Edit Meeting">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        @endcan

                                        <!-- Delete -->
                                        @can('meeting.delete')
                                            <form method="POST" action="{{ route('meetings.destroy', $meeting->id) }}" class="inline-block delete-meeting-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="delete-meeting-btn rounded p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-darkblack-500" title="Delete Meeting">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm font-medium text-bgray-500 dark:text-bgray-400">
                                    No meetings found matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($meetings->hasPages())
                <div class="border-t border-bgray-200 p-4 dark:border-darkblack-400">
                    {{ $meetings->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Include Reusable Form Modal -->
    @include('meetings.form-modal')
@endsection

@push('scripts')
    @vite('resources/js/modules/meetings/meetings.js')
@endpush
