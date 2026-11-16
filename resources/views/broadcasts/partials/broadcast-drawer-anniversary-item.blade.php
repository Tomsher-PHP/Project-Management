@php
    $user = $anniversary->user;
    $isSelf = (bool) ($anniversary->is_self ?? false);
    $years = max(1, (int) ($anniversary->years ?? 1));
    $daysRemaining = (int) ($anniversary->days_remaining ?? 0);
    $isUpcoming = $isUpcoming ?? $daysRemaining > 0;
    $anniversaryDate = $anniversary->anniversary_date;
    $formattedDate = $anniversaryDate->format('F j');
    $yearLabel = $years . ' ' . \Illuminate\Support\Str::plural('Year', $years);
    $ordinalYear = match (true) {
        $years % 100 >= 11 && $years % 100 <= 13 => $years . 'th',
        $years % 10 === 1 => $years . 'st',
        $years % 10 === 2 => $years . 'nd',
        $years % 10 === 3 => $years . 'rd',
        default => $years . 'th',
    };
    $firstName = \Illuminate\Support\Str::of($user->name)->explode(' ')->first() ?? $user->name;
    $designationName = $user->details?->designation?->name;
    $departmentName = $user->details?->department?->name;
    $badgeClass = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold';
    $badgeStyle = $isSelf ? 'background-color: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe;' : 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;';
@endphp

<div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm transition hover:border-amber-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-500 dark:hover:border-amber-400/40" data-work-anniversary-card="{{ $user->id }}">
    <!-- Header Row: Indicator & Status Badge -->
    <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap">
            @if (!$isUpcoming)
                <span class="{{ $badgeClass }}" style="{{ $badgeStyle }}">
                    <span>🏆</span>
                    <span>{{ $ordinalYear }} Work Anniversary!</span>
                </span>
            @else
                <span class="{{ $badgeClass }}" style="{{ $badgeStyle }}">
                    <span>🏆</span>
                    <span>{{ $isSelf ? "Your {$ordinalYear} Work Anniversary" : "{$ordinalYear} Work Anniversary" }}</span>
                </span>
            @endif
        </div>

        @if ($isUpcoming)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 border border-amber-200 dark:bg-amber-400/10 dark:text-amber-400 dark:border-amber-400/20">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                {{ $daysRemaining === 1 ? 'In 1 day' : "In {$daysRemaining} days" }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[11px] font-semibold text-success-600 border border-success-200 dark:bg-success-400/10 dark:text-success-400 dark:border-success-400/20">
                <span class="h-1.5 w-1.5 rounded-full bg-success-300"></span>
                Today
            </span>
        @endif
    </div>

    <!-- User Info Row -->
    <div class="flex items-center gap-3">
        <x-user-avatar :user="$user" size="sm" />

        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-bold text-bgray-900 dark:text-white truncate">
                {{ $user->name }}
                @if ($isSelf)
                    <span class="text-xs font-normal text-bgray-600 dark:text-bgray-400">(You)</span>
                @endif
            </h3>

            @if ($designationName || $departmentName)
                <p class="text-xs text-bgray-700 dark:text-bgray-300 truncate">
                    {{ $designationName ?: $departmentName }}
                </p>
            @endif
        </div>
    </div>

    <!-- Greeting / Date Row -->
    @if (!$isUpcoming)
        <div class="mt-2 text-xs text-bgray-700 dark:text-bgray-300">
            @if ($isSelf)
                Congratulations on completing {{ $yearLabel }} with us! Thank you for your dedication.
            @else
                Congratulations to {{ $firstName }} on completing {{ $yearLabel }} with the team!
            @endif
        </div>
    @else
        <div class="mt-2 flex items-center gap-1.5 text-xs text-bgray-700 dark:text-bgray-300">
            <svg class="h-3.5 w-3.5 shrink-0 text-bgray-700 dark:text-bgray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>{{ $formattedDate }}</span>
        </div>
    @endif
</div>
