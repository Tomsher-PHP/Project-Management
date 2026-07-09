@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('appraisal_settings.create')
            <x-button.create-button type="button" label="Category" disabled />
        @endcan
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex flex-col space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Appraisal Categories</h3>
                    </div>

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
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Sort Order</span>
                                </td>
                                <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                </td>
                                <td class="px-6 py-5 xl:w-[180px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Created At</span>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                </td>
                            </tr>
                            @forelse ($appraisalCategories as $appraisalCategory)
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
                                        <span class="block w-fit rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                            {{ $appraisalCategory->sort_order }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[150px] xl:px-0">
                                        @if ($appraisalCategory->is_active)
                                            <span class="inline-flex rounded-full bg-success-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.08em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 xl:w-[180px] xl:px-0">
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                            @appDateTime($appraisalCategory->created_at)
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" aria-label="Edit appraisal category">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-error-300 hover:text-error-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-error-300 dark:hover:text-error-300" aria-label="Delete appraisal category">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.5 2a1.5 1.5 0 00-1.415 1H4a1 1 0 100 2h.293l.853 10.236A3 3 0 008.136 18h3.728a3 3 0 002.99-2.764L15.707 5H16a1 1 0 100-2h-3.085A1.5 1.5 0 0011.5 2h-3zM8 7a1 1 0 011 1v6a1 1 0 11-2 0V8a1 1 0 011-1zm4 0a1 1 0 011 1v6a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="7" message="No appraisal categories found." />
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- Page ends -->
@endsection
