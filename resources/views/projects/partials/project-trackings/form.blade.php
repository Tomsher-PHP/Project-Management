<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-project-tracking-modal>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-tracking-modal-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-5xl">
            <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                {{-- Header --}}
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 class="text-xl font-semibold text-bgray-900 dark:text-white" data-project-tracking-modal-title>
                            Add Project Tracking
                        </h4>

                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-project-tracking-modal-description>
                            Add a tracking update for this project.
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-tracking-modal-close aria-label="Close">
                        ✕
                    </button>
                </div>

                {{-- Body --}}
                <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">

                    <form id="project-tracking-form" method="POST" action="{{ route('projects.trackings.store', $project) }}" enctype="multipart/form-data" data-project-tracking-form data-store-action="{{ route('projects.trackings.store', $project) }}">
                        @csrf

                        {{-- Dynamically changed to PUT during Edit --}}
                        <input type="hidden" name="_method" value="POST" data-project-tracking-method>

                        {{-- Tracking ID --}}
                        <input type="hidden" name="tracking_id" value="" data-project-tracking-id>

                        <div class="space-y-6">

                            {{-- Date --}}
                            <div>
                                <label for="project_tracking_date" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Date
                                    <span class="text-error-300">*</span>
                                </label>

                                <input type="date" id="project_tracking_date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required data-project-tracking-date class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" autocomplete="off">

                                <p data-project-tracking-date-error class="mt-1 hidden text-xs text-error-300"></p>
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="project_tracking_title" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Title
                                    <span class="text-error-300">*</span>
                                </label>

                                <input type="text" id="project_tracking_title" name="title" value="{{ old('title') }}" maxlength="255" required data-project-tracking-title placeholder="Enter tracking title" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <p data-project-tracking-title-error class="mt-1 hidden text-xs text-error-300"></p>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="project_tracking_description" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Description
                                </label>

                                <textarea id="project_tracking_description" name="description" rows="6" data-project-tracking-description placeholder="Enter description" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ old('description') }}</textarea>

                                <p data-project-tracking-description-error class="mt-1 hidden text-xs text-error-300"></p>
                            </div>

                            {{-- Attachments --}}
                            <div>
                                <label for="project_tracking_attachments" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Attachments
                                </label>

                                {{-- Existing Attachments --}}
                                <div data-project-tracking-existing-files class="mb-3 hidden space-y-2"></div>

                                {{-- New Attachments --}}
                                <input type="file" id="project_tracking_attachments" name="attachments[]" multiple data-project-tracking-attachments accept="*/*" class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-2 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">
                                    You can attach a maximum of 5 files.
                                    Max file size: 15MB per file.
                                </p>

                                {{-- Newly selected attachments --}}
                                <div data-project-tracking-selected-files class="mt-3 flex flex-wrap gap-2"></div>

                                {{-- Attachment error --}}
                                <p data-project-tracking-attachment-error class="mt-2 hidden text-sm text-error-300"></p>

                                <p data-project-tracking-files-error class="mt-2 hidden text-sm text-error-300"></p>
                            </div>

                        </div>
                    </form>
                </div>

                {{-- Footer --}}
                <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2 font-semibold text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-tracking-modal-close>
                        Cancel
                    </button>

                    <button type="submit" form="project-tracking-form" data-project-tracking-save class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white transition duration-200 hover:bg-success-400">
                        Save
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
