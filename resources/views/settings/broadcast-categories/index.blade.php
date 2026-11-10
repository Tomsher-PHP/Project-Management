@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('broadcast_settings.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Broadcast Category" data-url="{{ route('settings.broadcast-categories.store') }}" data-method="POST" data-color="#3B82F6" data-is_default="0" label="Category" />
        @endcan

        <x-filters.button />
    </div>

    <!-- write your code here-->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!--list table-->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                @include('settings.broadcast-tabs', ['currentTab' => 'categories'])

                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <td class="w-[60px]">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                </td>
                                <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="name" label="Name" />
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
                                $startNumber = ($broadcastCategories->currentPage() - 1) * $broadcastCategories->perPage();
                            @endphp
                            @forelse ($broadcastCategories as $key => $broadcastCategory)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex items-start space-x-2.5">
                                            <span class="mt-1.5 inline-flex h-3 w-3 shrink-0 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $broadcastCategory->color ?: '#E5E7EB' }}"></span>
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $broadcastCategory->name }}
                                                    </p>
                                                    @if ($broadcastCategory->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-200">
                                                            System
                                                        </span>
                                                    @endif
                                                    @if ($broadcastCategory->is_default)
                                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-300">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <x-status-toggle :model="$broadcastCategory" route="settings.broadcast_category.toggleStatus" entity="broadcast_category" permission="broadcast_settings.edit" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('broadcast_settings.edit')
                                                <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.broadcast-categories.update', $broadcastCategory->id) }}" data-name="{{ $broadcastCategory->name }}" data-color="{{ $broadcastCategory->color }}" data-is_default="{{ (int) $broadcastCategory->is_default }}" data-method="PUT" data-module="Broadcast Category" title="Edit Broadcast Category" />
                                            @endcan
                                            @can('broadcast_settings.delete')
                                                @if (!$broadcastCategory->is_system)
                                                    <x-delete-form :action="route('settings.broadcast-categories.destroy', $broadcastCategory->id)" />
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="4" message="No broadcast categories found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$broadcastCategories" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    <!-- write your code here-->
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Broadcast Category" formId="broadcastCategoryForm" action="{{ route('settings.broadcast-categories.store') }}" button="Save">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Category Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Enter category name" required>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Color <x-red-star /></label>
            <input type="color" name="color" value="#3B82F6" class="h-12 w-full cursor-pointer rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:border-darkblack-400" required>
        </div>

        <label for="is_default" class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="is_default" id="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                <span>Is Default</span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-600 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        The default option is preselected when creating a new broadcast.
                    </span>
                </span>
            </span>
        </label>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Category Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection

@push('scripts')
    <script>
        $(document).on('click', '.modal-open[data-module="Broadcast Category"]', function() {
            $('#multi-step-modal').find('.submit-btn').text('Save');
        });
        $(document).on('click', '.edit-record[data-module="Broadcast Category"]', function() {
            $('#multi-step-modal').find('.submit-btn').text('Update');
        });
    </script>
@endpush

