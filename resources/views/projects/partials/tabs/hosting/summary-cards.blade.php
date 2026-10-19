@php
    $cards = [
        [
            'label' => 'Total Services',
            'count' => $summaryMetrics['total'] ?? 0,
            'key' => 'total',
            'color' => 'text-bgray-900 dark:text-white',
            'badge' => 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>',
        ],
        [
            'label' => 'Active Services',
            'count' => $summaryMetrics['active'] ?? 0,
            'key' => 'active',
            'color' => 'text-success-400',
            'badge' => 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ],
        [
            'label' => 'Expiring Soon',
            'count' => $summaryMetrics['expiring_soon'] ?? 0,
            'key' => 'expiring_soon',
            'color' => 'text-amber-500',
            'badge' => 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ],
        [
            'label' => 'Expired',
            'count' => $summaryMetrics['expired'] ?? 0,
            'key' => 'expired',
            'color' => 'text-error-300',
            'badge' => 'bg-error-50 text-error-300 dark:bg-darkblack-500 dark:text-error-300',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" data-project-hosting-summary-cards>
    @foreach ($cards as $card)
        <div class="rounded-[8px] border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">
                    {{ $card['label'] }}
                </span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $card['badge'] }}">
                    {!! $card['icon'] !!}
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold {{ $card['color'] }}" data-summary-count="{{ $card['key'] }}">
                    {{ $card['count'] }}
                </span>
            </div>
        </div>
    @endforeach
</div>
