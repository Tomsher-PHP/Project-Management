@php
    $user = $anniversary->user;
    $isSelf = (bool) ($anniversary->is_self ?? false);
    $years = max(1, (int) ($anniversary->years ?? 1));
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
    $badgeStyle = $isSelf
        ? 'background-color: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe;'
        : 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;';
@endphp

<div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm transition hover:border-amber-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-500 dark:hover:border-amber-400/40" data-work-anniversary-card="{{ $user->id }}">
    <!-- Header Row: Indicator & Today Badge -->
    <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="{{ $badgeClass }}" style="{{ $badgeStyle }}">
                <span>🏆</span>
                <span>{{ $ordinalYear }} Work Anniversary!</span>
            </span>
        </div>

        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[11px] font-semibold text-success-600 border border-success-200 dark:bg-success-400/10 dark:text-success-400 dark:border-success-400/20">
            <span class="h-1.5 w-1.5 rounded-full bg-success-300"></span>
            Today
        </span>
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

    <!-- Greeting Row -->
    <div class="mt-2 text-xs text-bgray-700 dark:text-bgray-300">
        @if ($isSelf)
            Congratulations on completing {{ $yearLabel }} with us! Thank you for your dedication.
        @else
            Congratulations to {{ $firstName }} on completing {{ $yearLabel }} with the team!
        @endif
    </div>
</div>

