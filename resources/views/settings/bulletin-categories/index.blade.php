@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('bulletin_settings.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Bulletin Category" data-url="{{ route('settings.bulletin-categories.store') }}" data-method="POST" data-color="#3B82F6" label="Category" />
        @endcan

        <x-filters.button />
    </div>

    <!-- write your code here-->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!--list table-->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                @include('settings.bulletin-tabs', ['currentTab' => 'categories'])

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
                                $startNumber = ($bulletinCategories->currentPage() - 1) * $bulletinCategories->perPage();
                            @endphp
                            @forelse ($bulletinCategories as $key => $bulletinCategory)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex items-start space-x-2.5">
                                            <span class="mt-1.5 inline-flex h-3 w-3 shrink-0 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $bulletinCategory->color ?: '#E5E7EB' }}"></span>
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $bulletinCategory->name }}
                                                    </p>
                                                    @if ($bulletinCategory->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-200">
                                                            System
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <x-status-toggle :model="$bulletinCategory" route="settings.bulletin_category.toggleStatus" entity="bulletin_category" permission="bulletin_settings.edit" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('bulletin_settings.edit')
                                                <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.bulletin-categories.update', $bulletinCategory->id) }}" data-name="{{ $bulletinCategory->name }}" data-color="{{ $bulletinCategory->color }}" data-method="PUT" data-module="Bulletin Category" title="Edit Bulletin Category" />
                                            @endcan
                                            @can('bulletin_settings.delete')
                                                @if (!$bulletinCategory->is_system)
                                                    <x-delete-form :action="route('settings.bulletin-categories.destroy', $bulletinCategory->id)" />
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="4" message="No bulletin categories found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$bulletinCategories" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    <!-- write your code here-->
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Bulletin Category" formId="bulletinCategoryForm" action="{{ route('settings.bulletin-categories.store') }}" button="Save">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Category Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Enter category name" required>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Color <x-red-star /></label>
            <input type="color" name="color" value="#3B82F6" class="h-12 w-full cursor-pointer rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:border-darkblack-400" required>
        </div>

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
        $(document).on('click', '.modal-open[data-module="Bulletin Category"]', function() {
            $('#multi-step-modal').find('.submit-btn').text('Save');
        });
        $(document).on('click', '.edit-record[data-module="Bulletin Category"]', function() {
            $('#multi-step-modal').find('.submit-btn').text('Update');
        });
    </script>
@endpush
