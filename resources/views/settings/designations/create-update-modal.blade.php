<div class="modal fixed inset-0 z-50 flex hidden items-center justify-center overflow-y-auto" id="multi-step-modal">
    <div class="modal-overlay fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-50"></div>
    <div class="modal-content mx-auto w-full max-w-xl px-4">
        <div class="step-content step-1 relative top-40">
            <div class="relative max-w-[530px] rounded-lg bg-white p-7 transition-all dark:bg-darkblack-600">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 id="modalTitle" class="font-poppins text-3xl font-medium text-bgray-900 dark:text-white">
                            Add Designation
                        </h3>
                    </div>
                    <div>
                        <button type="button" id="step-1-cancel" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-bgray-200 text-bgray-700 hover:bg-red-500 hover:text-white focus:outline-none dark:bg-darkblack-500">
                            <span class="sr-only">Close</span>
                            <svg class="fill-bgray-900 dark:fill-darkblack-300" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M9.68746 10.609C9.94199 10.8636 10.3547 10.8636 10.6092 10.609C10.8638 10.3545 10.8638 9.94174 10.6092 9.6872L6.92202 5.99993L10.6093 2.31268C10.8638 2.05813 10.8638 1.64542 10.6093 1.39087C10.3547 1.13631 9.94199 1.13631 9.68746 1.39087L6.00019 5.07809L2.31292 1.39087C2.05837 1.13632 1.64566 1.13632 1.39111 1.39087C1.13656 1.64543 1.13656 2.05814 1.39111 2.3127L5.07835 5.99993L1.39112 9.6872C1.13657 9.94174 1.13657 10.3544 1.39112 10.609C1.64567 10.8636 2.05838 10.8636 2.31293 10.609L6.00019 6.92177L9.68746 10.609Z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex flex-col">
                    <div>
                        <form id="designationForm" action="{{ route('settings.designations.store') }}" method="post" class="space-y-6">
                            @csrf

                            <input type="hidden" name="_method" id="formMethod" value="POST">
                            <!-- Name Field -->
                            <div>
                                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">
                                    Name
                                </label>
                                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                            </div>

                            <!-- Order Field -->
                            <div>
                                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">
                                    Order
                                </label>
                                <input type="number" name="order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitBtn" class="rounded-lg bg-success-300 px-6 py-3 text-base font-medium text-white hover:bg-success-400">
                                    Create designation
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
