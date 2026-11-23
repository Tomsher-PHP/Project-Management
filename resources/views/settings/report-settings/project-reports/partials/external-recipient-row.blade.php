<div class="external-recipient-row flex flex-col sm:flex-row items-center gap-3" data-row-index="{{ $idx }}">
    <div class="w-full sm:w-1/3">
        <input type="text" name="external_recipients[{{ $idx }}][name]" value="{{ $ext->name ?? '' }}" placeholder="Recipient Name (Optional)" maxlength="255" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>
    <div class="w-full sm:w-1/2">
        <input type="email" name="external_recipients[{{ $idx }}][email]" value="{{ $ext->email ?? '' }}" placeholder="Email Address *" required maxlength="254" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>
    <div>
        <button type="button" class="btn-remove-external-recipient inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" title="Remove email">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</div>

