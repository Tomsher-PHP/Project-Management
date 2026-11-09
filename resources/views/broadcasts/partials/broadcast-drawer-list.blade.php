@php
    $today = $today ?? now()->toDateString();
    $todayBirthdays = $todayBirthdays ?? collect();
    $upcomingBirthdays = $upcomingBirthdays ?? collect();

    $upcomingBroadcasts = $broadcasts->filter(fn($b) => $b->start_date && $b->start_date->format('Y-m-d') > $today);
    $currentBroadcasts = $broadcasts->reject(fn($b) => $b->start_date && $b->start_date->format('Y-m-d') > $today);

    $hasCurrentItems = $currentBroadcasts->isNotEmpty() || $todayBirthdays->isNotEmpty();
    $hasUpcomingItems = $upcomingBroadcasts->isNotEmpty() || $upcomingBirthdays->isNotEmpty();
    $hasAnyItems = $hasCurrentItems || $hasUpcomingItems;

    // Chronologically sorted upcoming items (birthdays + upcoming broadcasts)
    $upcomingItems = collect();
    foreach ($upcomingBirthdays as $birthdayItem) {
        $upcomingItems->push([
            'type' => 'birthday',
            'date' => $birthdayItem->next_birthday->format('Y-m-d'),
            'item' => $birthdayItem,
        ]);
    }
    foreach ($upcomingBroadcasts as $broadcastItem) {
        $upcomingItems->push([
            'type' => 'broadcast',
            'date' => $broadcastItem->start_date ? $broadcastItem->start_date->format('Y-m-d') : '9999-12-31',
            'item' => $broadcastItem,
        ]);
    }
    $upcomingItems = $upcomingItems->sortBy('date')->values();
@endphp

@if (! $hasAnyItems)
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-bgray-100 dark:bg-darkblack-500 mb-3 text-bgray-400 dark:text-bgray-700">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <h4 class="text-base font-semibold text-bgray-900 dark:text-white">No Broadcasts Available</h4>
        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">There are no current or upcoming broadcasts for you.</p>
    </div>
@else
    <div id="broadcast-drawer-list-view" class="space-y-6">
        <div id="broadcast-drawer-cards-container" class="space-y-6">

            @if ($hasCurrentItems)
                <div class="space-y-3" data-broadcast-section="current">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-success-400 dark:text-success-300">
                        <span class="inline-block h-2 w-2 rounded-full bg-success-400"></span>
                        <span>Current</span>
                        <span class="text-[11px] font-normal text-bgray-700 dark:text-bgray-300">({{ $currentBroadcasts->count() + $todayBirthdays->count() }})</span>
                    </div>
                    <div class="space-y-3" data-broadcast-current-items>
                        @foreach ($todayBirthdays as $birthday)
                            @include('broadcasts.partials.broadcast-drawer-birthday-item', ['birthday' => $birthday, 'isUpcoming' => false])
                        @endforeach

                        @foreach ($currentBroadcasts as $broadcast)
                            @include('broadcasts.partials.broadcast-drawer-item', ['broadcast' => $broadcast, 'today' => $today])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($hasUpcomingItems)
                <div class="space-y-3" data-broadcast-section="upcoming">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                        <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                        <span>Upcoming</span>
                        <span class="text-[11px] font-normal text-bgray-700 dark:text-bgray-300">({{ $upcomingItems->count() }})</span>
                    </div>
                    <div class="space-y-3" data-broadcast-upcoming-items>
                        @foreach ($upcomingItems as $entry)
                            @if ($entry['type'] === 'birthday')
                                @include('broadcasts.partials.broadcast-drawer-birthday-item', ['birthday' => $entry['item'], 'isUpcoming' => true])
                            @else
                                @include('broadcasts.partials.broadcast-drawer-item', ['broadcast' => $entry['item'], 'today' => $today])
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Extra items loaded via pagination -->
        <div id="broadcast-drawer-more-items" class="space-y-3 hidden" data-broadcast-more-items></div>

        <!-- Load More Container -->
        @if ($broadcasts->hasMorePages())
            <div class="flex justify-center pt-2 pb-4" data-broadcast-load-more-container>
                <button type="button" data-broadcast-load-more-btn data-next-page="{{ $broadcasts->currentPage() + 1 }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-4 py-2 text-xs font-semibold text-bgray-700 shadow-sm transition hover:bg-bgray-50 hover:text-bgray-900 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                    <span data-load-more-label>Load More</span>
                    <svg data-load-more-spinner class="hidden h-3.5 w-3.5 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        @endif
    </div>
@endif
