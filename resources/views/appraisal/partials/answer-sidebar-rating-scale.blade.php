@php
    $scales = [
        [
            'score' => '1/5',
            'label' => 'Needs Significant Improvement',
            'badge' => 'text-red-700 dark:text-red-400',
        ],
        [
            'score' => '2/5',
            'label' => 'Needs Improvement',
            'badge' => 'text-amber-700 dark:text-amber-400',
        ],
        [
            'score' => '3/5',
            'label' => 'Meets Expectations',
            'badge' => 'text-blue-700 dark:text-blue-400',
        ],
        [
            'score' => '4/5',
            'label' => 'Exceeds Expectations',
            'badge' => 'text-emerald-700 dark:text-emerald-400',
        ],
        [
            'score' => '5/5',
            'label' => 'Outstanding / Exceptional',
            'badge' => 'text-success-300 dark:text-success-300',
        ],
    ];
@endphp

<section class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <h2 class="text-sm font-bold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Rating Milestone Scale</h2>
    <div class="mt-3 space-y-2">
        @foreach ($scales as $scale)
            <div class="flex items-center gap-2.5 px-2.5 text-xs transition">
                <span class="inline-flex h-6 min-w-[2.25rem] shrink-0 items-center justify-center text-[14px] font-bold {{ $scale['badge'] }}">
                    {{ $scale['score'] }}
                </span>
                <span class="font-semibold text-bgray-800 dark:text-bgray-300 text-[12px]">
                    {{ $scale['label'] }}
                </span>
            </div>
        @endforeach
    </div>
</section>
