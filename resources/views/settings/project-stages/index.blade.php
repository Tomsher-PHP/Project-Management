@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        @can('project_stage.create')
            <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm" data-module="Project Stage" data-url="{{ route('settings.project-stages.store') }}" data-method="POST">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>

                <span>New Project Stage</span>
            </a>
        @endcan

        <x-filters.button />

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">

                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="sort_order" label="Sort Order" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($projectStages->currentPage() - 1) * $projectStages->perPage();
                                @endphp
                                @forelse ($projectStages as $key => $projectStage)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                @if ($projectStage->is_default == 1)
                                                    <span>
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" fill="#F6A723" stroke="#F6A723" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span>
                                                        <svg class="fill-bgray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                                <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $projectStage->name }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center text-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $projectStage->sort_order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$projectStage" route="settings.project_stage.toggleStatus" entity="project_stage" permission="project_stage.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('project_stage.edit')
                                                    <a href="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.project-stages.update', $projectStage->id) }}" data-name="{{ $projectStage->name }}" data-sort_order="{{ $projectStage->sort_order }}" data-method="PUT" data-module="Project Stage">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan
                                                @can('project_stage.delete')
                                                    @if (!$projectStage->is_default)
                                                        <x-delete-form :action="route('settings.project-stages.destroy', $projectStage->id)" />
                                                    @endif
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="5" message="No project stages found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$projectStages" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Project Stage" formId="projectStageForm" action="{{ route('settings.project-stages.store') }}" button="Create Project Stage">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Project Stage Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

@endsection
