@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'types',
            'label' => 'Meeting Types',
            'url' => route('settings.meeting-types.index'),
            'permission' => 'meeting_settings.view',
        ],
        [
            'key' => 'locations',
            'label' => 'Meeting Locations',
            'url' => route('settings.meeting-locations.index'),
            'permission' => 'meeting_settings.view',
        ],
        [
            'key' => 'tags',
            'label' => 'Meeting Tags',
            'url' => route('settings.meeting-tags.index'),
            'permission' => 'meeting_settings.view',
        ],
    ];
@endphp

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />
        @can($createPermission)
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="{{ $entityLabel }}" data-url="{{ $storeRoute }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" :label="$entityLabel" />
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
                            <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                {{ $tab['label'] }}
                            </a>
                        @endcan
                    @endforeach
                </div>

                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full relative">
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <td class="pr-6 py-5 whitespace-nowrap">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="name" label="Name" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="sort_order" label="Sort Order" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                </td>
                                <td class="pl-6 py-5 whitespace-nowrap text-right">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </td>
                            </tr>
                            @php
                                $startNumber = ($records->currentPage() - 1) * $records->perPage();
                            @endphp
                            @forelse ($records as $record)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="pr-6 py-5 whitespace-nowrap">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-start space-x-2.5">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $record->name }}
                                                    </p>
                                                    @if ($record->is_system)
                                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                            System
                                                        </span>
                                                    @endif
                                                    @if ($record->is_default)
                                                        <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>
                                                @if ($record->description)
                                                    <p class="mt-1 text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                        {{ \Illuminate\Support\Str::limit($record->description, 60, '...') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex w-full items-center text-center">
                                            <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $record->sort_order }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex w-full items-center">
                                            <x-status-toggle :model="$record" :route="$toggleRoute" entity="{{ \Illuminate\Support\Str::snake($entityLabel) }}" :permission="$togglePermission" />
                                        </div>
                                    </td>
                                    <td class="pl-6 py-5 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-3">
                                            @can($editPermission)
                                                @php
                                                    $editData = [
                                                        'data-name' => $record->name,
                                                        'data-description' => $record->description,
                                                        'data-sort_order' => $record->sort_order,
                                                        'data-is_default' => (int) $record->is_default,
                                                        'data-is_system' => (int) $record->is_system,
                                                    ];
                                                @endphp
                                                <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route($updateRouteName, $record->id) }}" :attributes="new \Illuminate\View\ComponentAttributeBag($editData)" data-method="PUT" data-module="{{ $entityLabel }}" title="Edit {{ $entityLabel }}" />
                                            @endcan

                                            @can($deletePermission)
                                                @if (!$record->is_system)
                                                    <x-delete-form :action="route($destroyRouteName, $record->id)" />
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="5" :message="'No ' . strtolower($entityPluralLabel) . ' found.'" />
                            @endforelse
                        </table>
                    </div>

                    <x-pagination :paginator="$records" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>

    <x-form-modal modalId="multi-step-modal" :module="$entityLabel" formId="meetingSettingsForm" :action="$storeRoute" :button="'Create ' . $entityLabel">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
        </div>

        <div>
            <div class="mb-2.5 flex items-center justify-between gap-3">
                <label class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Description</label>
                <span class="text-xs font-medium text-bgray-600 dark:text-bgray-300"><span data-modal-description-count>0</span>/250</span>
            </div>
            <textarea name="description" rows="3" maxlength="250" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
        </div>

        <div>
            <label class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-700 dark:text-bgray-50">
                <span>Sort Order <x-red-star /></span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-600 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        Lower numbers appear earlier in lists and selection menus.
                    </span>
                </span>
            </label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
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
                        The default option is preselected when creating a new meeting.
                    </span>
                </span>
            </span>
        </label>
    </x-form-modal>

    <x-filters.drawer>
        <x-filters.input-search name="search" :label="$entityLabel . ' Name'" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
@endsection
