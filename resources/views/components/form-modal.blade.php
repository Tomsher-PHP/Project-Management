<div class="modal fixed inset-0 z-50 flex hidden items-center justify-center overflow-y-auto modal-form" id="{{ $modalId }}">
    <div class="modal-overlay fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-50"></div>

    <div class="modal-content mx-auto w-full max-w-xl px-4">
        <div class="relative top-40">
            <div class="rounded-lg bg-white p-7 dark:bg-darkblack-600">

                <div class="flex items-center justify-between">
                    <h3 class="text-3xl font-medium text-bgray-900 dark:text-white modal-title">
                        Add {{ $module }}
                    </h3>

                    <button type="button" class="modal-close h-8 w-8 rounded-md bg-bgray-200">
                        ✕
                    </button>
                </div>

                <form id="{{ $formId }}" class="ajax-form space-y-6 mt-6" action="{{ $action }}" method="POST">

                    @csrf
                    <input type="hidden" name="_method" value="POST" class="form-method">

                    {{ $slot }}

                    <div class="flex justify-end">
                        <button type="submit" id="submitBtn" class="submit-btn rounded-lg bg-success-300 px-6 py-3 text-white hover:bg-success-400">
                            {{ $button }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
