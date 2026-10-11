@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('project_settings.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Service Type" data-url="{{ route('settings.service-types.store') }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" label="Service Type" />
        @endcan

        <x-filters.button />
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!--list table-->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                @include('settings.project-tabs', ['currentTab' => 'service_types'])

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
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Group</span>
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
                                $startNumber = ($serviceTypes->currentPage() - 1) * $serviceTypes->perPage();
                            @endphp
                            @forelse ($serviceTypes as $key => $serviceType)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <div class="flex flex-col gap-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $serviceType->name }}
                                                    </p>
                                                    @if ($serviceType->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                            System
                                                        </span>
                                                    @endif
                                                    @if ($serviceType->is_default)
                                                        <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-sm text-bgray-600 dark:text-bgray-300">{{ $serviceType->code }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            {{ $serviceType->serviceGroup->name ?? 'N/A' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $serviceType->sort_order }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            @if ($serviceType->is_system)
                                                <span class="text-sm font-medium text-bgray-500">System</span>
                                            @else
                                                <x-status-toggle :model="$serviceType" route="settings.service_type.toggleStatus" entity="service_type" permission="project_settings.edit" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('project_settings.edit')
                                                @if (!$serviceType->is_system)
                                                    <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.service-types.update', $serviceType->id) }}" data-name="{{ $serviceType->name }}" data-description="{{ $serviceType->description }}" data-service_group_id="{{ $serviceType->service_group_id }}" data-sort_order="{{ $serviceType->sort_order }}" data-method="PUT" data-module="Service Type" title="Edit Service Type" />
                                                @endif
                                            @endcan
                                            @can('project_settings.delete')
                                                @if (!$serviceType->is_system)
                                                    <x-delete-form :action="route('settings.service-types.destroy', $serviceType->id)" />
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="6" message="No service types found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$serviceTypes" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Service Type" formId="serviceTypeForm" action="{{ route('settings.service-types.store') }}" button="Create Service Type">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Service Group <x-red-star /></label>
            <select name="service_group_id" class="w-full tom-select">
                <option value="">Select Group</option>
                @foreach ($serviceGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Description</label>
            <textarea name="description" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" rows="3"></textarea>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Service Type Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
        <x-filters.select name="service_group_id" label="Service Group" :options="$serviceGroups->pluck('name', 'id')->toArray()" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

@endsection
