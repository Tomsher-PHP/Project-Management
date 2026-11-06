@php
    use App\Models\Broadcast;
    $dateFormat = $globalDateFormat ?? config('constants.date_format', 'd M Y');
    $timeFormat = $globalTimeFormat ?? config('constants.time_format', 'h:i A');
@endphp

<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-2xl dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="flex items-start justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
        <div class="pr-5">
            <h2 class="text-xl font-bold text-bgray-900 dark:text-white">{{ $broadcast->title }}</h2>
            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">Created by {{ $broadcast->creator?->name ?? 'Unknown' }} on {{ $broadcast->created_at?->format($dateFormat) }}</p>
        </div>
        <button type="button" data-broadcast-modal-close class="rounded-md p-2 text-bgray-600 hover:bg-bgray-100 dark:text-bgray-300 dark:hover:bg-darkblack-400" aria-label="Close modal">✕</button>
    </div>

    <div class="max-h-[72vh] overflow-y-auto px-6 py-5">
        <div class="grid grid-cols-2 gap-4 rounded-xl bg-bgray-50 p-4 dark:bg-darkblack-500 sm:grid-cols-4">
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Category</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->category?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Priority</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::priorityOptions()[$broadcast->priority] ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Status</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::statusOptions()[$broadcast->status] ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Audience</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::audienceOptions()[$broadcast->audience_type] ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Starts</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->start_date?->format($dateFormat) ?? 'Immediately' }}</p>
            </div>
            <div>
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Ends</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->end_date?->format($dateFormat) ?? 'No expiry' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-bgray-700 dark:text-bgray-400">Published</p>
                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">@appDateTime($broadcast->published_at)</p>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Content</h3>
            <div class="prose max-w-none max-h-64 overflow-y-auto rounded-xl border border-bgray-200 bg-bgray-50/50 p-4 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500/50 dark:prose-invert dark:text-bgray-300">
                {!! $broadcast->content !!}
            </div>
        </div>

        @if ($broadcast->audience_type === Broadcast::AUDIENCE_TEAMS && (isset($teams) ? $teams->isNotEmpty() : $teamNames->isNotEmpty()))
            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-bgray-900 dark:text-white">Target Audience (Teams)</h3>
                    <span class="text-xs text-bgray-700 dark:text-bgray-400">{{ isset($teams) ? $teams->count() : $teamNames->count() }} team(s)</span>
                </div>
                <div class="max-h-48 overflow-y-auto rounded-xl border border-bgray-200 bg-bgray-50/50 p-3 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:grid-cols-3">
                        @if (isset($teams) && $teams->isNotEmpty())
                            @foreach ($teams as $team)
                                <div class="flex items-center gap-2.5 rounded-lg border border-bgray-200 bg-white p-2.5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-300/10 dark:text-success-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white">{{ $team->name }}</p>
                                        @if (isset($team->users_count))
                                            <p class="text-[11px] text-bgray-700 dark:text-bgray-400">{{ $team->users_count }} member(s)</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            @foreach ($teamNames as $name)
                                <div class="flex items-center gap-2.5 rounded-lg border border-bgray-200 bg-white p-2.5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-300/10 dark:text-success-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white">{{ $name }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($broadcast->audience_type === Broadcast::AUDIENCE_USERS && $broadcast->recipients->isNotEmpty())
            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-bgray-900 dark:text-white">Target Audience (Users)</h3>
                    <span class="text-xs text-bgray-700 dark:text-bgray-400">{{ $broadcast->recipients->count() }} user(s)</span>
                </div>
                <div class="max-h-48 overflow-y-auto rounded-xl border border-bgray-200 bg-bgray-50/50 p-3 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($broadcast->recipients as $recipient)
                            <div class="flex items-center gap-2.5 rounded-lg border border-bgray-200 bg-white p-2.5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-success-50 text-xs font-bold text-success-500 dark:bg-success-300/10 dark:text-success-300">
                                    {{ strtoupper(substr($recipient->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white">{{ $recipient->name }}</p>
                                    @if ($recipient->email)
                                        <p class="truncate text-[11px] text-bgray-700 dark:text-bgray-400">{{ $recipient->email }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($broadcast->attachments->isNotEmpty())
            <div class="mt-6">
                <h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Attachments</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($broadcast->attachments as $attachment)
                        <a href="{{ route('broadcasts.attachments.download', [$broadcast, $attachment]) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-xs font-medium text-success-500 hover:bg-success-50 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:bg-darkblack-500" target="_blank">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.5L14.5 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $attachment->original_name ?: $attachment->file_name }}</span>
                            <span class="text-[11px] text-bgray-700 dark:text-bgray-400">({{ number_format($attachment->file_size / 1024, 1) }} KB)</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
        <button type="button" data-broadcast-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-400">Close</button>
    </div>
</div>
