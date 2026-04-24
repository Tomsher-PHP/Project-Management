<style>
    #{{ $modalId }} .form-modal-fields {
        display: grid;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        #{{ $modalId }} .form-modal-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #{{ $modalId }} .form-modal-fields>*:has(textarea) {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="modal fixed inset-0 z-50 hidden overflow-y-auto modal-form" id="{{ $modalId }}" @isset($modalZIndex) style="z-index: {{ $modalZIndex }} !important;" @endisset>
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-3xl">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <h3 class="modal-title text-2xl font-semibold text-bgray-900 dark:text-white">
                        Add {{ $module }}
                    </h3>

                    <button type="button" class="modal-close inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300">
                        ✕
                    </button>
                </div>

                <form id="{{ $formId }}" class="ajax-form flex max-h-[80vh] flex-col" action="{{ $action }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="POST" class="form-method">

                    <div class="overflow-y-auto px-6 py-6 sm:px-7">
                        <div class="form-modal-fields">
                            {{ $slot }}
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                            Cancel
                        </button>

                        <button type="submit" id="submitBtn" class="submit-btn rounded-lg bg-success-300 px-4 py-2 text-sm text-white transition duration-200 hover:bg-success-400">
                            {{ $button }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
