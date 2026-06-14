@php
    $primaryContact = $customer->contacts->firstWhere('is_primary', true);
    $detailItems = [['label' => 'Company Name', 'value' => $customer->name], ['label' => 'Contact Person', 'value' => $primaryContact?->name], ['label' => 'Email', 'value' => $customer->email], ['label' => 'Phone', 'value' => $primaryContact?->landline], ['label' => 'Mobile', 'value' => $primaryContact?->mobile], ['label' => 'Industry', 'value' => $customer->industry?->name], ['label' => 'Country', 'value' => $customer->country?->name], ['label' => 'Sales Person', 'value' => $customer->salesPerson?->name]];
@endphp

<div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-12 w-1.5 rounded {{ $customer->is_active ? 'bg-success-300' : 'bg-bgray-300' }}"></div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="break-words text-xl font-bold text-bgray-900 dark:text-white">{{ $customer->name }}</h2>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $customer->is_active ? 'bg-success-50 text-success-400 dark:bg-success-300/10 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-sm text-bgray-700 dark:text-bgray-300">Customer Code: {{ $customer->customer_code }}</p>
                </div>
            </div>

            <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($detailItems as $item)
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">{{ $item['label'] }}</dt>
                        <dd class="mt-1 break-words text-sm font-medium text-bgray-900 dark:text-white">{{ filled($item['value']) ? $item['value'] : '--' }}</dd>
                    </div>
                @endforeach

                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Website</dt>
                    <dd class="mt-1 break-words text-sm font-medium text-bgray-900 dark:text-white">
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
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Created At</dt>
                    <dd class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">@appDateTime($customer->created_at)</dd>
                </div>
            </dl>
        </div>

        @can('customer.edit')
            <a href="{{ route('customers.edit', $customer) }}" class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M14.166 2.5C14.385 2.28103 14.645 2.10732 14.9311 1.98879C15.2173 1.87026 15.524 1.8092 15.8338 1.8092C16.1435 1.8092 16.4503 1.87026 16.7364 1.98879C17.0225 2.10732 17.2823 2.28103 17.5013 2.5C17.7202 2.71897 17.8939 2.97874 18.0125 3.26487C18.131 3.551 18.1921 3.85768 18.1921 4.16746C18.1921 4.47723 18.131 4.78391 18.0125 5.07004C17.8939 5.35617 17.7202 5.61594 17.5013 5.83491L6.25033 17.0858L1.66602 18.3341L2.91435 13.7498L14.166 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>Edit</span>
            </a>
        @endcan
    </div>
</div>
