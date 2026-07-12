<div class="2xl:flex 2xl:space-x-[48px]">
    <section class="mb-6 2xl:mb-0 2xl:flex-1">
        <div class="w-full rounded-lg bg-white px-[24px] dark:bg-darkblack-600">
            <div class="flex flex-col space-y-2">
                <div class="table-content w-full overflow-x-auto">
                    <table class="w-full">
                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                            <td class="px-6 py-5 xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                            </td>
                            <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Category Name</span>
                            </td>
                            <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Questions</span>
                            </td>
                            <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                            </td>
                            <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Default</span>
                            </td>
                            <td class="px-6 py-5 xl:w-[180px] xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Created At</span>
                            </td>
                            <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                            </td>
                        </tr>
                        @forelse ($appraisalCategories as $appraisalCategory)
                            @php
                                $questionList = $appraisalCategory->questions
                                    ->map(
                                        fn($question) => [
                                            'id' => $question->id,
                                            'question' => $question->question,
                                            'question_type' => $question->question_type,
                                            'is_active' => $question->is_active,
                                        ],
                                    )
                                    ->values();
                            @endphp
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                <td class="px-6 py-5 xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $loop->iteration }}</span>
                                </td>
                                <td class="px-6 py-5 xl:px-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                            {{ $appraisalCategory->name }}
                                        </p>
                                        @if ($appraisalCategory->is_system)
                                            <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                System
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                    <span class="block w-fit rounded-md bg-bgray-100 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                                        {{ $appraisalCategory->questions_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                    <x-status-toggle :model="$appraisalCategory" route="settings.appraisal.toggleStatus" entity="appraisal category" permission="appraisal_settings.edit" />
                                </td>
                                <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                    <button type="button" 
                                            @cannot('appraisal_settings.edit') disabled @endcannot 
                                            class="default-toggle switch-btn {{ $appraisalCategory->is_default ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed" 
                                            data-id="{{ $appraisalCategory->id }}" 
                                            data-url="{{ route('settings.appraisal.toggleDefault') }}" 
                                            data-entity="default setting" 
                                            role="switch" 
                                            aria-checked="{{ $appraisalCategory->is_default ? 'true' : 'false' }}">
                                        <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-5 xl:w-[180px] xl:px-0">
                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                        @appDateTime($appraisalCategory->created_at)
                                    </span>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2">
                                        @can('appraisal_settings.edit')
                                            <button type="button" class="edit-record inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-modal="multi-step-modal" data-url="{{ route('settings.appraisal.update', $appraisalCategory->id) }}" data-name="{{ $appraisalCategory->name }}" data-questions='@json($questionList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)' data-is-default="{{ $appraisalCategory->is_default ? '1' : '0' }}" data-method="PUT"
                                                data-module="Appraisal Category" aria-label="Edit appraisal category">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-table-no-data :col-span="8" message="No appraisal categories found." />
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
