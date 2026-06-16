<section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <h3 class="text-base font-bold text-bgray-900 dark:text-white">Customer Contacts</h3>
        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
            {{ $customer->contacts->count() }} {{ \Illuminate\Support\Str::plural('contact', $customer->contacts->count()) }}
        </span>
    </div>

    <div class="min-h-[260px] p-5">
        @if ($customer->contacts->isEmpty())
            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No contacts have been added for this customer yet.
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($customer->contacts as $contact)
                    <article class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <x-user-avatar :name="$contact->name" size="xs" />
                                    <p class="break-words text-sm font-semibold text-bgray-900 dark:text-white">{{ $contact->name }}</p>
                                </div>
                                <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">{{ $contact->designation ?: 'Designation not specified' }}</p>
                            </div>

                            @if ($contact->is_primary)
                                <span class="inline-flex shrink-0 rounded-full bg-success-50 px-2.5 py-1 text-[11px] font-semibold text-success-400 dark:bg-success-300/10 dark:text-success-300">Primary</span>
                            @endif
                        </div>

                        <dl class="mt-4 space-y-3 border-t border-bgray-100 pt-4 text-sm dark:border-darkblack-400">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-bgray-600 dark:text-bgray-300">Email</dt>
                                <dd class="break-all text-right font-medium text-bgray-900 dark:text-white">
                                    @if ($contact->email)
                                        <span class="inline-flex max-w-full items-center justify-end gap-1.5">
                                            <a href="mailto:{{ $contact->email }}" class="break-all transition hover:text-success-400">{{ $contact->email }}</a>
                                            <button type="button" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" onclick="copyProfileEmail(event, @js($contact->email))" aria-label="Copy {{ $contact->name }} email" title="Copy email">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </span>
                                    @else
                                        --
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-bgray-600 dark:text-bgray-300">Mobile</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $contact->mobile ?: '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-bgray-600 dark:text-bgray-300">Landline</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $contact->landline ?: '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-bgray-600 dark:text-bgray-300">WhatsApp</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $contact->whatsapp ?: '--' }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
