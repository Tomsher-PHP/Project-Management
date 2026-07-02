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

<section>
    <aside class="mt-4 rounded-xl border-l-4 border-warning-300 bg-bamber-50 px-4 py-3 text-bgray-900 dark:bg-darkblack-500 dark:text-bgray-300">
        <strong class="text-warning-300">Note:</strong> New Estimate Time must be the total estimated time. If approved, it replaces the current estimate.<br />
        <em>Example: Current estimate: <strong>04h 30m</strong> → Enter <strong>06h 30m</strong> to update the estimate to 6 hours 30 minutes.</em>
    </aside>
</section>

<div class="mt-6 grid gap-6 sm:grid-cols-2">
    <figure class="overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <a href="{{ asset('assets/images/help/request_estimate.png') }}" target="_blank" rel="noopener noreferrer" class="block cursor-pointer overflow-hidden bg-bgray-50 dark:bg-darkblack-500">
            <img src="{{ asset('assets/images/help/request_estimate.png') }}" alt="Request Estimate Change" class="h-auto w-full object-cover transition hover:opacity-90" />
        </a>
        <figcaption class="border-t border-bgray-100 px-4 py-2.5 text-center text-xs font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
            Click image to view full size — Request Estimate Change
        </figcaption>
    </figure>

    <figure class="overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <a href="{{ asset('assets/images/help/request_estimate_2.png') }}" target="_blank" rel="noopener noreferrer" class="block cursor-pointer overflow-hidden bg-bgray-50 dark:bg-darkblack-500">
            <img src="{{ asset('assets/images/help/request_estimate_2.png') }}" alt="Request Estimate Time Change modal" class="h-auto w-full object-cover transition hover:opacity-90" />
        </a>
        <figcaption class="border-t border-bgray-100 px-4 py-2.5 text-center text-xs font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
            Click image to view full size — Request Estimate Time Change modal
        </figcaption>
    </figure>
</div>
