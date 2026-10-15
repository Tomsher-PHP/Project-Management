@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('server_hosting.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Hosting Provider" data-url="{{ route('settings.hosting-providers.store') }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" label="Hosting Provider" />
        @endcan

        <x-filters.button />
    </div>

    <!-- list table -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                @include('settings.server-hosting-tabs', ['currentTab' => 'providers'])

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
                                <td class="px-6 py-5 xl:w-[200px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="website" label="Website" />
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
                                $startNumber = ($hostingProviders->currentPage() - 1) * $hostingProviders->perPage();
                            @endphp
                            @forelse ($hostingProviders as $key => $hostingProvider)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $hostingProvider->name }}
                                                </p>
                                                @if ($hostingProvider->is_system)
                                                    <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                        System
                                                    </span>
                                                @endif
                                                @if ($hostingProvider->is_default)
                                                    <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                        Default
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $hostingProvider->code ?: '--' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        @if ($hostingProvider->website)
                                            <a href="{{ $hostingProvider->website }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400 inline-flex items-center gap-1">
                                                <span>{{ Str::limit($hostingProvider->website, 30) }}</span>
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $hostingProvider->sort_order }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <x-status-toggle :model="$hostingProvider" route="settings.hosting-providers.toggleStatus" entity="hosting_provider" permission="server_hosting.edit" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('server_hosting.edit')
                                                <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.hosting-providers.update', $hostingProvider->id) }}" data-name="{{ $hostingProvider->name }}" data-code="{{ $hostingProvider->code }}" data-website="{{ $hostingProvider->website }}" data-description="{{ $hostingProvider->description }}" data-is_default="{{ (int) $hostingProvider->is_default }}" data-sort_order="{{ $hostingProvider->sort_order }}" data-method="PUT" data-module="Hosting Provider" title="Edit Hosting Provider" />
                                            @endcan
                                            @can('server_hosting.delete')
                                                @if (!$hostingProvider->is_system)
                                                    <x-delete-form :action="route('settings.hosting-providers.destroy', $hostingProvider->id)" />
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="6" message="No hosting providers found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$hostingProviders" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    <!-- Page ends -->

    <!-- Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Hosting Provider" formId="hostingProviderForm" action="{{ route('settings.hosting-providers.store') }}" button="Create Hosting Provider">

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="code-field-wrapper hidden">
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Code <x-red-star /></label>
            <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" pattern="[0-9]{6}" maxlength="6" autocomplete="off">
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">6-digit unique number.</p>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Website</label>
            <input type="url" name="website" placeholder="https://example.com" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>
        </div>

        <div>
            <label class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-700 dark:text-bgray-50">
                <span>Sort Order <x-red-star /></span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-600 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-56 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        Lower numbers appear earlier in lists and selection menus.
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-600 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        The default provider is preselected when configuring hosting.
                    </span>
                </span>
            </span>
        </label>

    </x-form-modal>

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Provider Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('multi-step-modal');
            if (!modal) return;

            const codeWrapper = modal.querySelector('.code-field-wrapper');
            const codeInput = modal.querySelector('input[name="code"]');

            const updateCodeVisibility = () => {
                const methodInput = modal.querySelector('.form-method');
                const isEdit = methodInput && methodInput.value === 'PUT';
                if (codeWrapper) {
                    if (isEdit) {
                        codeWrapper.classList.remove('hidden');
                        if (codeInput) codeInput.required = true;
                    } else {
                        codeWrapper.classList.add('hidden');
                        if (codeInput) {
                            codeInput.required = false;
                            codeInput.value = '';
                        }
                    }
                }
            };

            $(document).on('click', '.modal-open[data-target="#multi-step-modal"], .edit-record[data-modal="multi-step-modal"]', function () {
                setTimeout(updateCodeVisibility, 50);
            });
        });
    </script>
@endpush
