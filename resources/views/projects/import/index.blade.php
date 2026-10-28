@extends('layouts.master')

@section('page-content')
    <!-- Page Navigation & Breadcrumbs -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <x-back-button label="Back" :url="route('projects.index')" />

            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-medium text-bgray-500 dark:text-bgray-400">
                <a href="{{ route('projects.index') }}" class="transition hover:text-success-400">Projects</a>
                <span aria-hidden="true">/</span>
                <span class="text-bgray-900 dark:text-white">Import Records</span>
            </nav>
        </div>
    </div>

    <!-- Page Header & Module Selector Card -->
    <div class="mb-6 rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-7">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-bold tracking-tight text-bgray-900 dark:text-white sm:text-3xl">
                    Import Records
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-bgray-600 dark:text-bgray-300">
                    Select an import module below to view guidelines and prepare your data for bulk import. Additional modules will appear here as they are enabled.
                </p>
            </div>

            <!-- Module Selector -->
            <div class="w-full lg:w-80">
                <label for="import-module-selector" class="block text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">
                    Select Import Module
                </label>
                <div class="relative mt-2">
                    <select
                        id="import-module-selector"
                        name="module"
                        onchange="if(this.value) { window.location.href = '{{ route('projects.import.index') }}?module=' + encodeURIComponent(this.value); }"
                        class="w-full appearance-none rounded-lg border border-bgray-300 bg-white px-4 py-2.5 pr-10 text-sm font-semibold text-bgray-900 transition focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >
                        @foreach ($availableModules as $key => $module)
                            <option value="{{ $key }}" @selected($selectedModuleKey === $key)>
                                {{ $module['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-bgray-500 dark:text-bgray-400">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Selected Module Content Area -->
    <div class="w-full">
        @if ($selectedModule && !empty($selectedModule['view_partial']))
            @includeIf($selectedModule['view_partial'], [
                'module' => $selectedModule,
                'validationResult' => $validationResult ?? null,
                'activeImport' => $activeImport ?? null,
            ])
        @else
            <div class="rounded-lg border border-bgray-200 bg-white p-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
                <p class="text-base font-medium text-bgray-600 dark:text-bgray-300">
                    No import module is currently selected. Please choose a module from the dropdown above.
                </p>
            </div>
        @endif
    </div>
@endsection
