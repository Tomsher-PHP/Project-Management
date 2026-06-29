<p class="text-bgray-700 dark:text-bgray-300">If additional time is required, send an estimate change request before the current estimate expires.</p>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-bgray-300">Steps</h4>
    <ol class="mt-3 space-y-3 text-bgray-700 dark:text-bgray-300">
        @foreach (['Open the task.', 'Click Request Estimate Change.', 'Enter the new estimated hours and minutes.', 'Submit the request.'] as $step)
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-success-50 text-xs font-bold text-success-400 dark:bg-darkblack-500">{{ $loop->iteration }}</span>
                <span>{{ $step }}</span>
            </li>
        @endforeach
    </ol>
    <p class="mt-4 text-bgray-700 dark:text-bgray-300">The request is sent for approval.</p>
</section>

<figure class="flex min-h-44 items-center justify-center rounded-xl border-2 border-dashed border-bgray-200 bg-bgray-50 p-6 text-center dark:border-darkblack-400 dark:bg-darkblack-500">
    <figcaption>
        <span class="block text-xs font-semibold uppercase tracking-wide text-bgray-500">Screenshot placeholder</span>
        <span class="block text-sm text-bgray-700 dark:text-bgray-300">Request Estimate Change</span>
    </figcaption>
</figure>
