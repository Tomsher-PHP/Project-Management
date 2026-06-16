@php
    $currentDescriptions = $customer->profileDescriptions->pluck('description')->values();
@endphp

<div class="fixed inset-0 z-[80] hidden overflow-y-auto" data-customer-profile-grade-modal>
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-customer-profile-grade-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-darkblack-600">
            <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                <div>
                    <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Manage Profile Grade</h3>
                    <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">Assign a grade and maintain this customer's description points.</p>
                </div>
                <button type="button" data-customer-profile-grade-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:text-error-300 dark:bg-darkblack-500 dark:text-bgray-300" aria-label="Close profile grade modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <form data-customer-profile-grade-form data-action="{{ route('customers.profile-grade.update', $customer) }}" class="flex flex-col">
                <div class="max-h-[70vh] space-y-6 overflow-y-auto p-5">
                    <div>
                        <label for="customer_profile_grade_id" class="text-sm font-semibold text-bgray-700 dark:text-bgray-100">Profile Grade <x-red-star /></label>
                        <select name="customer_profile_grade_id" id="customer_profile_grade_id" class="tom-select w-full" data-sort="0">
                            <option value="">Select profile grade</option>
                            @foreach ($profileGrades as $grade)
                                <option value="{{ $grade->id }}" @selected($customer->customer_profile_grade_id === $grade->id)>
                                    {{ $grade->name }}{{ $grade->is_active ? '' : ' (Inactive)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 hidden text-sm text-error-300" data-customer-profile-grade-error="customer_profile_grade_id"></p>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <label class="text-sm font-semibold text-bgray-700 dark:text-bgray-100">Description Points</label>
                                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">These points apply only to this customer.</p>
                            </div>
                            <button type="button" data-customer-profile-grade-add-description class="inline-flex items-center gap-1.5 rounded-lg border border-success-300 px-3 py-1.5 text-xs font-semibold text-success-400 transition hover:bg-success-300 hover:text-white">
                                <span class="text-base leading-none">+</span>
                                <span>Add More</span>
                            </button>
                        </div>

                        <div class="mt-3 space-y-3" data-customer-profile-grade-descriptions></div>
                        <p class="mt-1 hidden text-sm text-error-300" data-customer-profile-grade-error="descriptions"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <button type="button" data-customer-profile-grade-close class="rounded-lg border border-bgray-300 bg-white px-5 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50">Cancel</button>
                    <button type="submit" data-customer-profile-grade-submit class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">Save Profile Grade</button>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" data-customer-profile-grade-current-descriptions>@json($currentDescriptions)</script>
</div>
