@extends('layouts.master')

@section('page-content')
<div class="w-full space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <x-back-button href="{{ route('meetings.index') }}" />
            <div>
                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">
                    {{ $meeting->title }}
                </h2>
                <p class="text-xs text-bgray-500 dark:text-bgray-400">
                    Created by {{ $meeting->addedBy?->name ?? 'System' }} on @appDate($meeting->created_at)
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('meeting.edit')
                <a href="{{ route('meetings.index', ['edit' => $meeting->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 px-3.5 py-2 text-xs font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Meeting
                </a>
            @endcan
        </div>
    </div>

    {{-- Content Layout --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left Column: Core Meeting Info & Description --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Core Overview Card --}}
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600 space-y-5">
                
                {{-- Badges Header --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-bgray-200 pb-4 dark:border-darkblack-400">
                    {{-- Status Badge --}}
                    @if ($meeting->meetingStatus)
                        @php $statusColor = $meeting->meetingStatus->color ?: '#3B82F6'; @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $statusColor }}15; color: {{ $statusColor }};">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                            {{ $meeting->meetingStatus->name }}
                        </span>
                    @endif

                    {{-- Type Badge --}}
                    @if ($meeting->meetingType)
                        @php $typeColor = $meeting->meetingType->color ?: '#10B981'; @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background-color: {{ $typeColor }}15; color: {{ $typeColor }};">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $typeColor }};"></span>
                            {{ $meeting->meetingType->name }}
                        </span>
                    @endif

                    {{-- Project Badge --}}
                    @if ($meeting->project)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-darkblack-500 dark:text-blue-400">
                            Project: {{ $meeting->project->name }} ({{ $meeting->project->project_code }})
                        </span>
                    @endif
                </div>

                {{-- Key Meta Grid --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Start Time</span>
                        <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                            @appDateTime($meeting->start_at)
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">End Time</span>
                        <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                            @appDateTime($meeting->end_at)
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Location</span>
                        <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $meeting->meetingLocation?->name ?? 'Not Specified' }}
                            @if ($meeting->location_details)
                                <span class="text-xs font-normal text-bgray-500">({{ $meeting->location_details }})</span>
                            @endif
                        </p>
                    </div>

                    @if ($meeting->url)
                        <div>
                            <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Join URL</span>
                            <div class="mt-0.5">
                                <a href="{{ $meeting->url }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-400 transition hover:bg-success-100 dark:bg-darkblack-500 dark:text-success-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Open Meeting Link
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Tags --}}
                @if ($meeting->tags->count() > 0)
                    <div class="border-t border-bgray-200 pt-4 dark:border-darkblack-400">
                        <span class="mb-2 block text-xs font-medium text-bgray-500 dark:text-bgray-400">Tags</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($meeting->tags as $tag)
                                @php $tagColor = $tag->color ?: '#6B7280'; @endphp
                                <span class="rounded-md px-2.5 py-1 text-xs font-medium" style="background-color: {{ $tagColor }}15; color: {{ $tagColor }};">
                                    #{{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Description Card --}}
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">
                <h3 class="mb-3 text-base font-bold text-bgray-900 dark:text-white">
                    Description / Notes
                </h3>
                @if ($meeting->description)
                    <div class="prose prose-sm max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-300">
                        {!! $meeting->description !!}
                    </div>
                @else
                    <p class="text-xs text-bgray-400 italic">No description provided for this meeting.</p>
                @endif
            </div>

        </div>

        {{-- Right Column: Organizer & Participants --}}
        <div class="space-y-6">

            {{-- Organizer Card --}}
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-darkblack-600">
                <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                    Organizer
                </h4>
                @if ($meeting->organizer)
                    <div class="flex items-center gap-3">
                        <x-user-avatar :user="$meeting->organizer" size="md" />
                        <div>
                            <p class="text-sm font-bold text-bgray-900 dark:text-white">
                                {{ $meeting->organizer->name }}
                            </p>
                            <p class="text-xs text-bgray-500 dark:text-bgray-400">
                                {{ $meeting->organizer->email }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-bgray-400">Unspecified</p>
                @endif
            </div>

            {{-- Participants List Card --}}
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-darkblack-600">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                        Participants ({{ $meeting->participants->count() }})
                    </h4>
                </div>

                <div class="space-y-3">
                    @forelse ($meeting->participants as $participant)
                        <div class="flex items-start justify-between rounded-lg border border-bgray-200 p-3 dark:border-darkblack-400">
                            <div class="flex items-center gap-2.5">
                                @if ($participant->user)
                                    <x-user-avatar :user="$participant->user" size="sm" />
                                    <div>
                                        <p class="text-xs font-semibold text-bgray-900 dark:text-white">
                                            {{ $participant->name ?: $participant->user->name }}
                                        </p>
                                        <p class="text-[11px] text-bgray-500 dark:text-bgray-400">
                                            {{ $participant->email ?: $participant->user->email }}
                                        </p>
                                    </div>
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-600 dark:bg-darkblack-500 dark:text-purple-400">
                                        EXT
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-bgray-900 dark:text-white">
                                            {{ $participant->name }}
                                        </p>
                                        <p class="text-[11px] text-bgray-500 dark:text-bgray-400">
                                            {{ $participant->email }}
                                        </p>
                                        @if ($participant->phone)
                                            <p class="text-[10px] text-bgray-400">
                                                {{ $participant->phone }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div>
                                @if ($participant->is_external)
                                    <span class="rounded bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-600 dark:bg-darkblack-500 dark:text-purple-300">
                                        External
                                    </span>
                                @else
                                    <span class="rounded bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-600 dark:bg-darkblack-500 dark:text-green-300">
                                        Internal
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-bgray-400 italic">No participants added to this meeting.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
