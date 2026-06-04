@extends('layouts.master')

@section('page-content')

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="col-span-12 w-full">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Schedule Shift
                            </h3>

                            <div class="mt-8">
                                @include('schedule-shift._form', ['shift' => null, 'editable' => null])
                            </div>

                            <!-- preview modal for existing schedule -->
                            <div id="preview-modal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">

                                <div class="bg-white dark:bg-darkblack-600 rounded-lg w-[600px] max-h-[80vh] overflow-y-auto p-6">

                                    <h3 class="text-lg font-semibold mb-4">
                                        Existing Schedule Found
                                    </h3>

                                    <div id="preview-modal-content"></div>

                                    <div class="flex justify-end gap-3 mt-6">

                                        <button type="button" onclick="closePreviewModal()" class="px-4 py-2 rounded bg-gray-200">
                                            Cancel
                                        </button>

                                        <button type="button" id="continue-schedule" class="px-4 py-2 rounded bg-success-400 text-white">
                                            Continue
                                        </button>

                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
@endsection

@push('scripts')
    @vite('resources/js/modules/create-schedule.js')
@endpush
