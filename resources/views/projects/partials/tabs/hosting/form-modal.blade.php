@php
    $ownershipTypes = $formOptions['ownershipTypes'] ?? \App\Models\ProjectHostingRecord::OWNERSHIP_TYPES;
    $providers = $formOptions['providers'] ?? [];
    $services = $formOptions['services'] ?? [];
    $statuses = $formOptions['statuses'] ?? [];
    $billingCycles = $formOptions['billingCycles'] ?? [];
    $environments = $formOptions['environments'] ?? [];
    $currencies = $formOptions['currencies'] ?? [];
    $defaultCurrency = $formOptions['defaultCurrency'] ?? config('constants.currency', 'AED');
@endphp

<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-project-hosting-modal>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-hosting-modal-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-4xl">
            <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                <!-- Header -->
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 class="text-xl font-semibold text-bgray-900 dark:text-white" data-project-hosting-modal-title>
                            Add Hosting Record
                        </h4>
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-project-hosting-modal-description>
                            Add a hosting or server record associated with this project.
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-hosting-modal-close aria-label="Close">
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">
                    <form id="project-hosting-form" method="POST" action="{{ route('projects.hosting.store', $project) }}" data-project-hosting-form data-store-action="{{ route('projects.hosting.store', $project) }}">
                        @csrf
                        <input type="hidden" name="_method" value="POST" data-project-hosting-method>
                        <input type="hidden" name="hosting_id" value="" data-project-hosting-id>

                        <div class="space-y-5">
                            <!-- Service Name & Ownership Type -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="project_hosting_name" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Hosting / Service Name <span class="text-error-300">*</span>
                                    </label>
                                    <input type="text" id="project_hosting_name" name="name" required maxlength="255" placeholder="e.g. Production Web Server, Cloud DB" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="name">
                                    <p data-project-hosting-error="name" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_ownership" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Ownership Type <span class="text-error-300">*</span>
                                    </label>
                                    <select id="project_hosting_ownership" name="ownership_type" required class="tom-select-no-search w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="ownership_type">
                                        @foreach ($ownershipTypes as $key => $label)
                                            <option value="{{ $key }}" @selected($key === \App\Models\ProjectHostingRecord::OWNERSHIP_COMPANY)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="ownership_type" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Provider, Service & Status -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="project_hosting_provider" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Hosting Provider <span class="text-error-300">*</span>
                                    </label>
                                    <select id="project_hosting_provider" name="hosting_provider_id" required class="tom-select w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="hosting_provider_id">
                                        <option value="">Select Provider</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="hosting_provider_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_service" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Hosting Service Type <span class="text-error-300">*</span>
                                    </label>
                                    <select id="project_hosting_service" name="hosting_service_id" required class="tom-select w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="hosting_service_id">
                                        <option value="">Select Service Type</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="hosting_service_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_status" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Service Status <span class="text-error-300">*</span>
                                    </label>
                                    <select id="project_hosting_status" name="hosting_service_status_id" required class="tom-select-no-search w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="hosting_service_status_id">
                                        <option value="">Select Status</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="hosting_service_status_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Environment & Billing Cycle -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="project_hosting_environment" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Environment
                                    </label>
                                    <select id="project_hosting_environment" name="hosting_environment_id" class="tom-select-no-search w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="hosting_environment_id">
                                        <option value="">Select Environment (Optional)</option>
                                        @foreach ($environments as $env)
                                            <option value="{{ $env->id }}">{{ $env->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="hosting_environment_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_billing_cycle" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Billing Cycle
                                    </label>
                                    <select id="project_hosting_billing_cycle" name="hosting_billing_cycle_id" class="tom-select-no-search w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="hosting_billing_cycle_id">
                                        <option value="">Select Billing Cycle (Optional)</option>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="hosting_billing_cycle_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Domain & Reference -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="project_hosting_domain" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Domain / URL
                                    </label>
                                    <input type="text" id="project_hosting_domain" name="domain" maxlength="255" placeholder="e.g. app.example.com" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="domain">
                                    <p data-project-hosting-error="domain" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_reference" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Reference / Account ID
                                    </label>
                                    <input type="text" id="project_hosting_reference" name="reference" maxlength="255" placeholder="e.g. AWS-ACC-10293, Order #4810" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="reference">
                                    <p data-project-hosting-error="reference" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Dates: Start, Expiry, Renewal -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="project_hosting_start_date" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Start Date
                                    </label>
                                    <input type="date" id="project_hosting_start_date" name="start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="start_date" autocomplete="off">
                                    <p data-project-hosting-error="start_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_expiry_date" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Expiry Date
                                    </label>
                                    <input type="date" id="project_hosting_expiry_date" name="expiry_date" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="expiry_date" autocomplete="off">
                                    <p data-project-hosting-error="expiry_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_next_renewal_date" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Next Renewal Date
                                    </label>
                                    <input type="date" id="project_hosting_next_renewal_date" name="next_renewal_date" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="next_renewal_date" autocomplete="off">
                                    <p data-project-hosting-error="next_renewal_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Billing Amount & Currency -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="project_hosting_billing_amount" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Billing Amount
                                    </label>
                                    <input type="number" step="0.01" min="0" id="project_hosting_billing_amount" name="billing_amount" placeholder="0.00" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="billing_amount">
                                    <p data-project-hosting-error="billing_amount" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label for="project_hosting_currency_code" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Currency Code
                                    </label>
                                    <select id="project_hosting_currency_code" name="currency_code" class="tom-select w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="currency_code">
                                        <option value="">Select Currency</option>
                                        @foreach ($currencies as $curr)
                                            <option value="{{ $curr['code'] }}" @selected($curr['code'] === $defaultCurrency)>
                                                {{ $curr['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p data-project-hosting-error="currency_code" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="project_hosting_description" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Description
                                </label>
                                <textarea id="project_hosting_description" name="description" rows="3" placeholder="Server specifications, configuration notes, or service scope..." class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="description"></textarea>
                                <p data-project-hosting-error="description" class="mt-1 hidden text-xs text-error-300"></p>
                            </div>

                            <!-- Internal Notes -->
                            <div>
                                <label for="project_hosting_notes" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Internal Notes
                                </label>
                                <textarea id="project_hosting_notes" name="notes" rows="2" placeholder="Private internal notes or credentials location..." class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-project-hosting-field="notes"></textarea>
                                <p data-project-hosting-error="notes" class="mt-1 hidden text-xs text-error-300"></p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2 font-semibold text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-hosting-modal-close>
                        Cancel
                    </button>
                    <button type="submit" form="project-hosting-form" data-project-hosting-save class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50">
                        Save
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
