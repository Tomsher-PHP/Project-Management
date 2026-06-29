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

<figure class="mt-6 overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <a href="{{ asset('assets/images/help/request_estimate.png') }}" target="_blank" rel="noopener noreferrer" class="block cursor-pointer overflow-hidden bg-bgray-50 dark:bg-darkblack-500">
        <img src="{{ asset('assets/images/help/request_estimate.png') }}" alt="Request Estimate Change" class="h-auto w-full object-cover transition hover:opacity-90" />
    </a>
    <figcaption class="border-t border-bgray-100 px-4 py-2.5 text-center text-xs font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
        Click image to view full size — Request Estimate Change
    </figcaption>
</figure>
