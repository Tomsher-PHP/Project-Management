@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <x-back-button label="Back" />

            @can('industry.create')
                <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Industry" data-url="{{ route('settings.industries.store') }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" label="Industry" />
            @endcan

            <x-filters.button />
        </div>

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
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Parent Industry</span>
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
                                    $startNumber = ($industries->currentPage() - 1) * $industries->perPage();
                                @endphp
                                @forelse ($industries as $key => $industry)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $industry->name }}
                                                    </p>
                                                    @if ($industry->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                            System
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:text-bgray-50">{{ $industry->parent->name ?? '--' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $industry->sort_order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$industry" route="settings.industry.toggleStatus" entity="industry" permission="industry.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('industry.edit')
                                                    <a href="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.industries.update', $industry->id) }}" data-name="{{ $industry->name }}" data-parent_id="{{ $industry->parent_id }}" data-sort_order="{{ $industry->sort_order }}" data-method="PUT" data-module="Industry">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can('industry.delete')
                                                    @if (!$industry->is_system)
                                                        <x-delete-form :action="route('settings.industries.destroy', $industry->id)" />
                                                    @endif
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="6" message="No industries found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$industries" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Industry" formId="industryForm" action="{{ route('settings.industries.store') }}" button="Create Industry">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Parent Industry</label>
            <select name="parent_id" id="parent_id" class="tom-select w-full" data-sort="0">
                <option value="">Select Parent Industry</option>
                @foreach ($parentIndustries as $parentIndustry)
                    <option value="{{ $parentIndustry->id }}">{{ $parentIndustry->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Industry Name" />
        <x-filters.multi-select name="parent_id" label="Parent Industry" :options="$parentIndustries" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection
