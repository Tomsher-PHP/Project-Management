<div id="cheque_modal" class="fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-2xl rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600 my-8">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 id="cheque_modal_title" class="text-lg font-bold text-bgray-900 dark:text-white">
                Add New Cheque Expense
            </h3>
            <button type="button" data-cheque-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form id="cheque_form" method="POST" action="{{ route('cheques.store') }}" data-create-url="{{ route('cheques.store') }}">
            @csrf
            <input type="hidden" name="_method" id="cheque_form_method" value="POST">
            <input type="hidden" name="cheque_id" id="cheque_id_input" value="">

            <div class="max-h-[75vh] overflow-y-auto pl-6 pr-6 pt-2 pb-6 space-y-4">

                <!-- Dynamic Error Container -->
                <div id="cheque_form_errors" class="hidden rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                    <div class="font-bold mb-1">Please fix the following validation errors:</div>
                    <ul id="cheque_errors_list" class="list-disc list-inside space-y-1"></ul>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                        <div class="font-bold mb-1">Please fix the following validation errors:</div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Field 1: Cheque # & Field 2: Amount -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Cheque # <x-red-star />
                        </label>
                        <input type="text" name="cheque_number" id="cheque_number" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. CHQ-10023">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Amount ({{ $globalCompanyCurrency ?? 'AED' }}) <x-red-star />
                        </label>
                        <input type="number" step="0.01" min="0" name="amount" id="cheque_amount" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                    </div>
                </div>

                <!-- Field 3: Cheque Date & Field 4: Cheque Given -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Cheque Date <x-red-star />
                        </label>
                        <input type="text" name="cheque_date" id="cheque_date" value="{{ now()->format('Y-m-d') }}" required class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Cheque Given <x-red-star />
                        </label>
                        <input type="text" name="cheque_given" id="cheque_given" value="{{ now()->format('Y-m-d') }}" required class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                    </div>
                </div>

                <!-- Field 5: Cheque To -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Cheque To <x-red-star />
                    </label>
                    <input type="text" name="cheque_to" id="cheque_to" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Recipient name or vendor">
                </div>

                <!-- Field 6: Purpose -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Purpose <x-red-star />
                    </label>
                    <input type="text" name="purpose" id="cheque_purpose" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Describe the purpose of cheque">
                </div>

                <!-- Field 7: Cheque Status & Field 8: Debited Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Cheque Status <x-red-star />
                        </label>
                        <select name="cheque_status" id="cheque_status" required class="tom-select-no-search w-full" data-default-value="{{ $default_status }}">
                            <option value="">Select Status</option>
                            @foreach ($status_options as $val => $lbl)
                                <option value="{{ $val }}" {{ $val === $default_status ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Debited Date
                        </label>
                        <input type="text" name="debited_date" id="cheque_debited_date" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                    </div>
                </div>

            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                <button type="button" data-cheque-modal-close class="rounded-lg border border-bgray-300 px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500 transition">
                    Cancel
                </button>
                <button type="submit" id="cheque_submit_btn" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400 transition">
                    Save Cheque Expense
                </button>
            </div>
        </form>
    </div>
</div>
