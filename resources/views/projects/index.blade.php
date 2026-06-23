@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        @can('project.create')
            <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200" data-module="Project" data-url="{{ route('projects.store') }}" data-method="POST">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>

                <span>New Project</span>
            </a>
        @endcan

        <x-filters.button />

        <x-project-flow-indicator class="sm:ml-auto" />

        @can('project.restore')
            <a href="{{ route('projects.restore.index') }}" class="inline-flex items-center gap-2 rounded-md border border-success-300 px-4 py-1.5 text-sm font-semibold text-success-400 transition duration-200 hover:bg-success-300 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 8l5 4-5 4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12h-8" />
                </svg>
                <span>Restore Projects</span>
            </a>
        @endcan
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
                                <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="name" label="Name" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="customer.name" label="Customer" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[220px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status / Stage</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="start_date" label="Start Date" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="end_date" label="End Date" />
                                    </div>
                                </td>
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </div>
                                </td>
                            </tr>
                            @php
                                $startNumber = ($projects->currentPage() - 1) * $projects->perPage();
                            @endphp
                            @forelse ($projects as $key => $project)
                                @php
                                    $priority = config('project_constants.project_priorities')[$project->priority] ?? null;
                                    $isAgileFlow = $project->project_flow === 'agile';
                                    $flowLabel = ucfirst($project->project_flow ?? 'linear');
                                    $projectStatusColor = $project->projectStatus->color ?? '#CBD5E1';
                                    $projectStageColor = $project->projectStage->color ?? '#CBD5E1';
                                @endphp
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex items-stretch">
                                            <!-- Priority Vertical Line -->
                                            @if (isset($priority))
                                                <div class="w-1 rounded-sm mr-4 {{ $priority['bg_class'] }}" title="{{ 'Priority: ' . ($priority['label'] ?? '--') }}"></div>
                                            @endif

                                            <!-- Content -->
                                            <div class="relative flex-1 pr-8">
                                                <x-project-flow-icon :flow="$project->project_flow" class="absolute right-0 top-0" :title="'Project Flow: ' . $flowLabel" />

                                                <a href="{{ route('projects.edit', $project->id) }}">
                                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                        {{ $project->name }}
                                                    </h4>
                                                    <p class="text-sm text-bgray-700">
                                                        Code: {{ $project->project_code ?? '--' }}
                                                    </p>
                                                </a>
                                                <div class="mt-3 w-full">
                                                    <div class="mb-1 flex items-center justify-between gap-3">
                                                        <span class="text-xs font-medium text-bgray-700 dark:text-bgray-300">
                                                            Timeline
                                                        </span>
                                                        <span class="text-xs font-semibold {{ $project->project_timeline['text_class'] }}">
                                                            {{ $project->project_timeline['percentage'] }}%
                                                        </span>
                                                    </div>

                                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-500">
                                                        <div class="h-full rounded-full {{ $project->project_timeline['bar_class'] }}" style="width: {{ $project->project_timeline['percentage'] }}%;"></div>
                                                    </div>

                                                    <div class="mt-1 flex items-center justify-between gap-3 text-[11px] text-bgray-700 dark:text-bgray-300">
                                                        <span>{{ $project->project_timeline['start_label'] }}</span>
                                                        <span class="text-right">{{ $project->project_timeline['end_label'] }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-md px-1.5 py-1.5 text-sm font-bold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                @if ($project->customer)
                                                    <x-profile-grade-badge :grade="$project->customer->profileGrade" size="sm" />
                                                @endif
                                                @if ($project->customer && !$project->customer->trashed())
                                                    <a href="{{ route('customers.show', $project->customer) }}" class="break-words transition hover:text-success-400">
                                                        {{ $project->customer->name }}
                                                    </a>
                                                @else
                                                    <span class="break-words">{{ $project->customer->name ?? '--' }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[220px] xl:px-0">
                                        <div class="flex w-full flex-col gap-2">
                                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $projectStatusColor }}"></span>
                                                {{ $project->projectStatus->name ?? 'No Status' }}
                                            </span>
                                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $projectStageColor }}"></span>
                                                {{ $project->projectStage->name ?? 'No Stage' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex flex-col w-full">

                                            <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                {{ $project->start_date?->format($globalDateFormat) ?? '--' }}
                                            </span>

                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ $project->end_date?->format($globalDateFormat) ?? '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            <a href="{{ route('projects.edit', $project->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" title="Open project">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7m0 0v7m0-7L10 14" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5v14h14v-5" />
                                                </svg>
                                            </a>

                                            @can('project.delete')
                                                <x-delete-form :action="route('projects.destroy', $project->id)" formClass="project-delete-form" />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="6" message="No projects found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$projects" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    <!-- write your code here-->
    <!-- Page ends -->

    <!-- Filter drawer -->
    @php
        $typesFilter = collect($types)->map(
            fn($label, $key) => (object) [
                'id' => $key,
                'name' => $label,
            ],
        );
        $prioritiesFilter = collect($priorities)->map(
            fn($value, $key) => (object) [
                'id' => $key,
                'name' => $value['label'],
            ],
        );
    @endphp
    <x-filters.drawer>
        <x-filters.input-search name="name" label="Name" />
        <x-filters.multi-select name="customer_id" label="Customer" :options="$customers" />
        <x-filters.multi-select name="project_flow" label="Project Flow" :options="$typesFilter" />
        <x-filters.multi-select name="priority" label="Priority" :options="$prioritiesFilter" />
        <x-filters.multi-select name="status_id" label="Project Status" :options="$statuses" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

    <!-- Create Project Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Project" formId="projectForm" action="{{ route('projects.store') }}" button="Create Project">

        <!-- Project Name -->
        <div>
            <label for="name" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Project Name <x-red-star /></label>
            <input type="text" name="name" id="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Enter project name">
            @error('name')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Customer -->
        <div>
            <label for="customer_id" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Customer <x-red-star /></label>
            <select name="customer_id" id="customer_id" class="tom-select w-full">
                <option value="">Select Customer</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Project Flow -->
        <div>
            <label for="project_flow" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Project Flow <x-red-star /></label>
            <select name="project_flow" id="project_flow" class="tom-select-no-search w-full">
                @php
                    $defaultProjectFlow = old('project_flow', 'agile');
                @endphp
                @foreach ($types as $key => $type)
                    <option value="{{ $key }}" {{ (string) $defaultProjectFlow === (string) $key ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
            @error('project_flow')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Project Priority -->
        <div>
            <label for="priority" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Priority <x-red-star /></label>
            <select name="priority" id="priority" class="tom-select-no-search w-full">
                @php
                    $defaultProjectPriority = old('priority', 'medium');
                @endphp
                @foreach ($priorities as $key => $priority)
                    <option value="{{ $key }}" {{ (string) $defaultProjectPriority === (string) $key ? 'selected' : '' }}>{{ $priority['label'] }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

    </x-form-modal>
    @vite('resources/js/modules/projects/project-delete.js')
@endsection
