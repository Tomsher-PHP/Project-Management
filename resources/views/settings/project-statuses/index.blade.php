@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
        <div class="mb-6 flex flex-wrap items-center gap-3">

            @can('project_status.create')
                <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm" data-module="Project Status" data-url="{{ route('settings.project-statuses.store') }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" data-color="#22C55E">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>

                    <span>New Project Status</span>
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
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="type" label="Type" />
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
                                    $startNumber = ($projectStatuses->currentPage() - 1) * $projectStatuses->perPage();
                                @endphp
                                @forelse ($projectStatuses as $key => $projectStatus)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <span class="inline-flex h-3 w-3 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $projectStatus->color ?: '#E5E7EB' }}"></span>
                                                <div class="space-y-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                            {{ $projectStatus->name }}
                                                        </p>
                                                        @if ($projectStatus->is_system)
                                                            <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                                System
                                                            </span>
                                                        @endif
                                                        @if ($projectStatus->is_default)
                                                            <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                                Default
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-sm text-bgray-700 dark:text-bgray-300">
                                                        {{ $projectStatus->code ?: '--' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-semibold uppercase text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ str_replace('_', ' ', $projectStatus->type) }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $projectStatus->sort_order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$projectStatus" route="settings.project_status.toggleStatus" entity="project_status" permission="project_status.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('project_status.edit')
                                                    <a href="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.project-statuses.update', $projectStatus->id) }}" data-name="{{ $projectStatus->name }}" data-code="{{ $projectStatus->code }}" data-color="{{ $projectStatus->color }}" data-type="{{ $projectStatus->type }}" data-is_completed="{{ (int) $projectStatus->is_completed }}" data-is_default="{{ (int) $projectStatus->is_default }}" data-sort_order="{{ $projectStatus->sort_order }}" data-method="PUT" data-module="Project Status">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan
                                                @can('project_status.delete')
                                                    @if (!$projectStatus->is_system)
                                                        <x-delete-form :action="route('settings.project-statuses.destroy', $projectStatus->id)" />
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="7" message="No project statuses found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$projectStatuses" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Project Status" formId="projectStatusForm" action="{{ route('settings.project-statuses.store') }}" button="Create Project Status">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" data-project-status-name class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Code <x-red-star /></label>
            <input type="text" name="code" data-project-status-code class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" pattern="[a-z0-9_]+" autocomplete="off">
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">Lowercase only. Spaces are converted to underscores.</p>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Color</label>
            <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-700 dark:text-bgray-50">
                <span>Type <x-red-star /></span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        Choose how this status behaves in the project lifecycle: open, in progress, or closed.
                    </span>
                </span>
            </label>
            <select name="type" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @foreach ($types as $type => $label)
                    <option value="{{ $type }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-700 dark:text-bgray-50">
                <span>Sort Order <x-red-star /></span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-56 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        Lower numbers appear earlier in project status lists and selection menus.
                    </span>
                </span>
            </label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <label for="is_default" class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="is_default" id="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                <span>Is Default</span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        The default project status is preselected when a new project is created.
                    </span>
                </span>
            </span>
        </label>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Project Status Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

@endsection

@push('scripts')
    @vite('resources/js/modules/project-status-form.js')
@endpush
