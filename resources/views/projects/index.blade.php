@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

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

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    {{-- <td class="">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td> --}}
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project Status</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project Type</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="start_date" label="Start Date" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="internal_end_date" label="End Date" />
                                        </div>
                                    </td>
                                    {{-- <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td> --}}
                                </tr>
                                @php
                                    $startNumber = ($projects->currentPage() - 1) * $projects->perPage();
                                @endphp
                                @forelse ($projects as $key => $project)
                                    @php
                                        $priority = config('constants.project_priorities')[$project->priority] ?? null;
                                    @endphp
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        {{-- <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td> --}}
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-stretch">

                                                <!-- 🎨 Priority Vertical Line -->
                                                @if (isset($priority))
                                                    <div class="w-1 rounded-sm mr-4 {{ $priority['bg_class'] }}"></div>
                                                @endif

                                                <!-- Content -->
                                                <div class="flex-1">
                                                    <a href="{{ route('projects.edit', $project->id) }}">
                                                        <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                            {{ $project->name }}
                                                        </h4>
                                                        <p class="text-sm text-bgray-500">
                                                            Code: {{ $project->project_code ?? '--' }}
                                                        </p>
                                                    </a>

                                                    <div class="flex flex-col">
                                                        <span class="text-gray-500 dark:text-bgray-50">
                                                            Customer: {{ $project->customer->name ?? '--' }}
                                                        </span>

                                                        <span class="dark:text-bgray-50 {{ $priority['text_class'] ?? '' }}">
                                                            Priority: {{ $priority['label'] ?? '--' }}
                                                        </span>
                                                    </div>
                                                </div>

                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-bold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ $project->projectStatus->name ?? '--' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-bold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ strtoupper($project->project_type ?? '--') }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex flex-col w-full">

                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $project->start_date->format(config('constants.date_format')) ?? '--' }}
                                                </span>

                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ $project->internal_end_date->format(config('constants.date_format')) ?? '--' }}</span>
                                            </div>
                                        </td>
                                        {{-- <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('project.edit')
                                                    <x-edit-button :action="route('projects.edit', $project->id)" />
                                                @endcan
                                                @can('project.delete')
                                                    <x-delete-form :action="route('projects.destroy', $project->id)" />
                                                @endcan
                                            </div>
                                        </td> --}}
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="7" message="No projects found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$projects" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
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
        <x-filters.multi-select name="project_type" label="Project Type" :options="$typesFilter" />
        <x-filters.multi-select name="priority" label="Priority" :options="$prioritiesFilter" />
        <x-filters.multi-select name="status_id" label="Project Status" :options="$statuses" />
    </x-filters.drawer>
    <!-- Filter drawer end -->

    <!-- Create Project Modal content start -->
    <x-form-modal modalId="multi-step-modal" module="Project" formId="projectForm" action="{{ route('projects.store') }}" button="Create Project">

        <!-- Project Name -->
        <div>
            <label for="name" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Project Name <x-red-star /></label>
            <input type="text" name="name" id="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            @error('name')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Customer -->
        <div>
            <label for="customer_id" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Customer</label>
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

        <!-- Project Type -->
        <div>
            <label for="project_type" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Project Type <x-red-star /></label>
            <select name="project_type" id="project_type" class="tom-select-no-search w-full">
                <option value="agile">Agile</option>
                <option value="linear">Linear</option>
            </select>
            @error('project_type')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Project Priority -->
        <div>
            <label for="priority" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Priority <x-red-star /></label>
            <select name="priority" id="priority" class="tom-select-no-search w-full">
                @foreach ($priorities as $key => $priority)
                    <option value="{{ $key }}">{{ $priority['label'] }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Project Status -->
        <div>
            <label for="project_status" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Project Status <x-red-star /></label>
            <select name="project_status" id="project_status" class="tom-select-no-search w-full">
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
            @error('project_status')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Start Date -->
        <div>
            <label for="start_date" class="mb-2.5 block text-left text-sm text-bgray-600 dark:text-bgray-50">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                        bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" value="{{ old('start_date', \Carbon\Carbon::today()->format('Y-m-d')) }}" data-format="{{ config('constants.date_format') }}" placeholder="Select a date">

            @error('start_date')
                <p class="mt-2 text-sm text-error-300">
                    {{ $message }}
                </p>
            @enderror
        </div>

    </x-form-modal>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/ajax-form-modal.js') }}"></script>
@endpush
