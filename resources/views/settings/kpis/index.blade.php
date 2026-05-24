@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
        <div class="mb-6 flex flex-wrap items-center gap-3">

        @can('kpi.create')
            <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm" data-module="KPI" data-url="{{ route('settings.kpis.store') }}" data-method="POST">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>

                <span>New KPI</span>
            </a>
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
                                    <td class="inline-block px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Description</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($kpis->currentPage() - 1) * $kpis->perPage();
                                @endphp
                                @forelse ($kpis as $key => $kpi)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $kpi->name }}
                                                    </p>
                                                    @if ($kpi->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                            System
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <p class="text-sm text-bgray-500 dark:text-bgray-300">
                                                {{ \Illuminate\Support\Str::limit(trim(strip_tags($kpi->description ?: '')) ?: 'No description added.', 50, '...') }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$kpi" route="settings.kpi.toggleStatus" entity="kpi" permission="kpi.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('kpi.edit')
                                                    <a href="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.kpis.update', $kpi->id) }}" data-name="{{ $kpi->name }}" data-description="{{ $kpi->description }}" data-method="PUT" data-module="KPI">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan
                                                @can('kpi.delete')
                                                    @if (!$kpi->is_system)
                                                        <x-delete-form :action="route('settings.kpis.destroy', $kpi->id)" />
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="5" message="No project KPIs found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$kpis" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="KPI" formId="kpiForm" action="{{ route('settings.kpis.store') }}" button="Create KPI">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="md:col-span-2">
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
            <textarea name="description" class="hidden"></textarea>
            <div class="custom-quill">
                <div id="kpi-description-editor" class="h-60 min-h-[100px] rounded-b-lg bg-white dark:bg-darkblack-500"></div>
            </div>
        </div>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="KPI Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection

@push('scripts')
    @vite('resources/js/modules/kpi-form.js')
@endpush
