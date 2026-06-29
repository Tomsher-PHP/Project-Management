<p class="text-bgray-700 dark:text-bgray-300">Use these practices to keep project reporting accurate and make task progress clear to your team:</p>

<ul class="grid gap-3 sm:grid-cols-2 text-bgray-700 dark:text-bgray-300">
    @foreach ([
        'Start the timer before beginning work.',
        'Stop the timer whenever you are not working.',
        'Keep the task status updated.',
        'Request estimate extensions before the current estimate expires.',
        'Create a Task Request if no work has been assigned.',
    ] as $practice)
        <li class="flex gap-3 rounded-xl bg-bgray-50 p-4 dark:bg-darkblack-500">
            <svg class="mt-1 shrink-0 stroke-success-400" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke-width="1.5" />
                <path d="m8 12 2.5 2.5L16 9" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>{{ $practice }}</span>
        </li>
    @endforeach
</ul>
