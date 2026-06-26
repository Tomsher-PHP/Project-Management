@php
    $primaryContact = $customer->contacts->firstWhere('is_primary', true);
    $emirates = config('constants.emirates', []);
    $emirateName = filled($customer->emirate) ? $emirates[$customer->emirate] ?? ucfirst(str_replace('_', ' ', $customer->emirate)) : null;
    $detailItems = [['label' => 'Company Name', 'value' => $customer->name], ['label' => 'Contact Person', 'value' => $primaryContact?->name], ['label' => 'Email', 'value' => $customer->email], ['label' => 'Phone', 'value' => $primaryContact?->landline], ['label' => 'Mobile', 'value' => $primaryContact?->mobile], ['label' => 'Industry', 'value' => $customer->industry?->name], ['label' => 'Country', 'value' => $customer->country?->name], ['label' => 'Emirate', 'value' => $emirateName], ['label' => 'Sales Person', 'value' => $customer->salesPerson?->name]];
@endphp

<div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,2fr)_minmax(220px,auto)] lg:items-start">
        <div class="min-w-0">
            <div class="flex h-full items-start gap-3">
                <div class="shrink-0 mt-1">
                    <x-back-button />
                </div>
                <div class="mt-1 h-10 w-1 shrink-0 rounded {{ $customer->is_active ? 'bg-success-300' : 'bg-bgray-300' }}"></div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="break-words text-xl font-bold text-bgray-900 dark:text-white">{{ $customer->name }}</h2>
                        <div id="customer-profile-grade-badge" class="inline-flex">
                            <x-profile-grade-badge :grade="$customer->profileGrade" size="md" class="mt-0.5" />
                        </div>
                    </div>
                    <p class="text-sm text-bgray-700 dark:text-bgray-300">Customer Code: {{ $customer->customer_code }}</p>
                </div>
            </div>
        </div>

        <dl class="rounded-xl border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40 grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($detailItems as $item)
                <div class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-700 dark:text-bgray-300">{{ $item['label'] }}</dt>
                    <dd class="mt-1 break-words text-md font-medium text-bgray-900 dark:text-white">
                        @if ($item['label'] === 'Email' && filled($item['value']))
                            <span class="inline-flex max-w-full items-center gap-1.5">
                                <a href="mailto:{{ $item['value'] }}" class="break-all transition hover:text-success-400">{{ $item['value'] }}</a>
                                <button type="button" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" onclick="copyProfileEmail(event, @js($item['value']))" aria-label="Copy customer email" title="Copy email">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </span>
                        @else
                            {{ filled($item['value']) ? $item['value'] : '--' }}
                        @endif
                    </dd>
                </div>
            @endforeach

            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-700 dark:text-bgray-300">Website</dt>
                <dd class="mt-1 break-words text-md font-medium text-bgray-900 dark:text-white">
                    @if ($customer->website)
                        <a href="{{ $customer->website }}" target="_blank" rel="noopener noreferrer" class="text-success-400 transition hover:text-success-300">
                            {{ limitStringChar($customer->website, 40) }}
                        </a>
                    @else
                        --
                    @endif
                </dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-700 dark:text-bgray-300">Google Map</dt>
                <dd class="mt-1 break-words text-md font-medium text-bgray-900 dark:text-white">
                    @if ($customer->google_map_link)
                        <a href="{{ $customer->google_map_link }}" target="_blank" rel="noopener noreferrer" class="text-success-400 transition hover:text-success-300">
                            {{ limitStringChar($customer->google_map_link, 40) }}
                        </a>
                    @else
                        --
                    @endif
                </dd>
            </div>
        </dl>

        <div class="flex min-w-0 flex-col gap-4 lg:items-end">
            @can('customer.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14.166 2.5C14.385 2.28103 14.645 2.10732 14.9311 1.98879C15.2173 1.87026 15.524 1.8092 15.8338 1.8092C16.1435 1.8092 16.4503 1.87026 16.7364 1.98879C17.0225 2.10732 17.2823 2.28103 17.5013 2.5C17.7202 2.71897 17.8939 2.97874 18.0125 3.26487C18.131 3.551 18.1921 3.85768 18.1921 4.16746C18.1921 4.47723 18.131 4.78391 18.0125 5.07004C17.8939 5.35617 17.7202 5.61594 17.5013 5.83491L6.25033 17.0858L1.66602 18.3341L2.91435 13.7498L14.166 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Edit</span>
                </a>
            @endcan

            <dl class="min-w-0 text-left lg:text-right">
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Created By</dt>
                    <dd class="mt-1 break-words text-md font-medium text-bgray-900 dark:text-white">{{ $customer->addedBy?->name ?? '--' }} at @appDateTime($customer->created_at)</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
