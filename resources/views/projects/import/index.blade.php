@extends('layouts.master')

@section('page-content')
    <!-- Page Header & Module Selector Card -->
    <div class="mb-6 rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-7">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="shrink-0 mt-1">
                <x-back-button :url="route('projects.index')" />
            </div>

            <!-- Module Selector -->
            <div class="w-full lg:w-80">
                <div class="mt-2">
                    <select id="import-module-selector" name="module" onchange="if(this.value) { window.location.href = '{{ route('projects.import.index') }}?module=' + encodeURIComponent(this.value); }" class="tom-select-no-search w-full" placeholder="Select Import Module">
                        @foreach ($availableModules as $key => $module)
                            <option value="{{ $key }}" @selected($selectedModuleKey === $key)>
                                {{ $module['label'] }}
                            </option>
                        @endforeach
                    </select>
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
