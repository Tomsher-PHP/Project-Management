@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'modules',
            'label' => 'Modules',
            'url' => route('settings.agile-modules.index'),
            'permission' => 'agile_module.view',
        ],
        [
            'key' => 'sprints',
            'label' => 'Sprints',
            'url' => route('settings.agile-sprints.index'),
            'permission' => 'agile_sprint.view',
        ],
    ];
@endphp

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center gap-3">

        @can($createPermission)
                <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-module="{{ $entityLabel }}" data-url="{{ $storeRoute }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>

                <span>New {{ $entityLabel }}</span>
            </a>
        @endcan

        <x-filters.button />
        </div>

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                        @foreach ($tabs as $tab)
                            @can($tab['permission'])
                                @php
                                    $isActiveTab = $currentTab === $tab['key'];
                                @endphp

                                <a
                                    href="{{ $tab['url'] }}"
                                    class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200"
                                >
                                    {{ $tab['label'] }}
                                </a>
                            @endcan
                        @endforeach
                    </div>

                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td>
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="inline-block w-[280px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[170px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Color</span>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="sort_order" label="Sort Order" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($records->currentPage() - 1) * $records->perPage();
                                @endphp
                                @forelse ($records as $record)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-start space-x-2.5">
                                                @if ($record->is_system)
                                                    <span class="mt-0.5">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" fill="#F6A723" stroke="#F6A723" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="mt-0.5">
                                                        <svg class="fill-bgray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                @endif

                                                <div>
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $record->name }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                                        {{ $record->description ?: 'No description added.' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-8 w-8 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $record->color ?: '#E5E7EB' }}"></span>
                                                <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">{{ $record->color ?: '--' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center text-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $record->sort_order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$record" :route="$toggleRoute" entity="{{ \Illuminate\Support\Str::snake($entityLabel) }}" :permission="$togglePermission" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can($editPermission)
                                                    <a
                                                        href="javascript:void(0)"
                                                        class="edit-record"
                                                        data-modal="multi-step-modal"
                                                        data-url="{{ route($updateRouteName, $record->id) }}"
                                                        data-name="{{ $record->name }}"
                                                        data-color="{{ $record->color }}"
                                                        data-description="{{ $record->description }}"
                                                        data-sort_order="{{ $record->sort_order }}"
                                                        data-is_system="{{ (int) $record->is_system }}"
                                                        data-method="PUT"
                                                        data-module="{{ $entityLabel }}"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 transition group-hover:text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can($deletePermission)
                                                    @if (! $record->is_system)
                                                        <x-delete-form :action="route($destroyRouteName, $record->id)" />
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="6" :message="'No ' . strtolower($entityPluralLabel) . ' found.'" />
                                @endforelse
                            </table>
                        </div>

                        <x-pagination :paginator="$records" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
    </main>

    <x-form-modal modalId="multi-step-modal" :module="$entityLabel" formId="agileFlowForm" :action="$storeRoute" :button="'Create ' . $entityLabel">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
            <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
        </div>

        <div>
            <div class="mb-2.5 flex items-center justify-between gap-3">
                <label class="block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-modal-description-count>0</span>/100</span>
            </div>
            <textarea name="description" rows="3" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>
    </x-form-modal>

    <x-filters.drawer>
        <x-filters.input-search name="search" :label="$entityLabel . ' Name'" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
@endsection
