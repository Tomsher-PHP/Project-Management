@php
    //get project edit permission
    $canEdit = auth()->user()->can('project.edit') && !$project->trashed();
    $isDeletedProjectView = $project->trashed();
@endphp
<form id="project-settings-form" action="{{ route('projects.update', $project->id) }}" method="POST" x-data="projectForm()" data-can-edit="{{ $canEdit ? 'true' : 'false' }}">
    @csrf
    @method('PUT')

    @if (!$canEdit)
        <div class="mb-4 text-sm text-gray-500">
            You have view-only access to this project.
        </div>
        <fieldset disabled class="opacity-60 cursor-not-allowed">
    @endif

    <!-- ================= PROJECT INFORMATION ================= -->
    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 dark:border-darkblack-400 dark:text-white">
                Project Information
            </h3>

            <!-- Project Name -->
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Name <x-red-star />
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $project->name ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('name') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" x-on:input="markDirty()">
                @error('name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Customer -->
            <div class="flex flex-col gap-2">
                <label for="customer_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Customer <x-red-star />
                </label>
                <select name="customer_id" id="customer_id" class="tom-select w-full @error('customer_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" x-on:change="markDirty()">
                    <option value="">Select Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $project->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority -->
            <div class="flex flex-col gap-2">
                <label for="priority" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Priority
                </label>
                <select name="priority" id="priority" class="tom-select-no-search w-full" x-on:change="markDirty()">
                    <option value="">Select Priority</option>
                    @foreach ($priorities as $key => $priority)
                        <option value="{{ $key }}" {{ old('priority', $project->priority ?? '') == $key ? 'selected' : '' }}>
                            {{ $priority['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('priority')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="parent_project_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Parent Project / Rework For
                </label>
                <select name="parent_project_id" id="parent_project_id" class="tom-select w-full @error('parent_project_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" x-on:change="markDirty()">
                    <option value="">No parent project</option>
                    @foreach ($parentProjectOptions as $parentProjectOption)
                        <option value="{{ $parentProjectOption->id }}" {{ (string) old('parent_project_id', $project->parent_project_id ?? null) === (string) $parentProjectOption->id ? 'selected' : '' }}>
                            {{ $parentProjectOption->name }}{{ $parentProjectOption->project_code ? ' (' . $parentProjectOption->project_code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-sm text-bgray-700 dark:text-bgray-300">
                    Select a completed project only when this project is rework or follow-up work for an earlier delivered project.
                </p>
                @error('parent_project_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    <!-- ================= Timeline INFORMATION ================= -->
    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 dark:border-darkblack-400 dark:text-white">
                Timeline Information
            </h3>

            <!-- Start Date -->
            <div class="flex flex-col gap-2">
                <label for="start_date" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Start Date
                </label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', isset($project) ? $project->start_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" x-on:input="markDirty()">
                @error('start_date')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Internal End Date -->
            <div class="flex flex-col gap-2">
                <label for="end_date" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    End Date
                </label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d') ?? '') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" x-on:input="markDirty()">
                @error('end_date')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Customer End Date -->
            @can('project.customer_end_date')
                <div class="flex flex-col gap-2">
                    <label for="customer_end_date" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Customer End Date
                    </label>
                    <input type="date" name="customer_end_date" id="customer_end_date" value="{{ old('customer_end_date', $project->customer_end_date?->format('Y-m-d') ?? '') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" x-on:input="markDirty()">
                    @error('customer_end_date')
                        <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                    @enderror
                </div>
            @endcan

            <!-- Estimated Time -->
            <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="old('estimated_time_minutes', $project->estimated_time_seconds !== null ? intdiv($project->estimated_time_seconds, 60) : 0)" input-action="markDirty()" />

            <x-forms.estimated-time-input label="Default Task Estimate" name="default_task_estimate_minutes" :total-minutes="old('default_task_estimate_minutes', $project->default_task_estimate_seconds !== null ? intdiv($project->default_task_estimate_seconds, 60) : 0)" input-action="markDirty()" help-text="Used as the default estimated time when creating new tasks in this project." />

        </div>
    </div>

    <!-- ================= Other Information ================= -->
    <div class="flex flex-col md:flex-row gap-8 pb-4 dark:border-darkblack-400 dark:text-white items-start md:items-center">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 dark:border-darkblack-400 dark:text-white">
                Other Information
            </h3>

            <!-- Sales Person -->
            <div class="flex flex-col gap-2">
                <label for="sales_person_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Sales Person
                </label>
                <select name="sales_person_id" id="sales_person_id" class="tom-select w-full @error('sales_person_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" data-sort="0" x-on:change="markDirty()">
                    <option value="">Select</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ old('sales_person_id', $project->sales_person_id ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @error('sales_person_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Project Category -->
            <div class="flex flex-col gap-2">
                <label for="project_category_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Category
                </label>

                <div class="flex items-center gap-2">
                    <select name="project_category_id" id="project_category_id" class="tom-select w-full" x-on:change="markDirty()">
                        <option value="">Select Project Category</option>
                        @foreach ($projectCategories as $category)
                            <option value="{{ $category->id }}" {{ old('project_category_id', $project->project_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    @if ($canEdit)
                        @can('project_category.create')
                            <button type="button" data-target="#project-category-modal" data-select-target="project_category_id" data-module="Project Category" data-url="{{ route('settings.project-categories.store') }}" data-method="POST" data-sort_order="{{ $nextProjectCategorySortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Project Category" aria-label="Add Project Category">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endcan
                    @endif
                </div>

                @error('project_category_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Project Technology -->
            <div class="flex flex-col gap-2">
                <label for="project_technology_ids" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Technology
                </label>

                <div class="flex items-center gap-2">
                    <select name="project_technology_ids[]" id="project_technology_ids" multiple class="tom-select-multiple w-full" x-on:change="markDirty()">
                        <option value="">Select Project Technology</option>
                        @foreach ($projectTechnologies as $technology)
                            <option value="{{ $technology->id }}" {{ in_array($technology->id, old('project_technology_ids', $project->technologies->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
                                {{ $technology->name }}
                            </option>
                        @endforeach
                    </select>

                    @if ($canEdit)
                        @can('technology.create')
                            <button type="button" data-target="#project-technology-modal" data-select-target="project_technology_ids[]" data-module="Technology" data-url="{{ route('settings.technologies.store') }}" data-method="POST" data-sort_order="{{ $nextProjectTechnologySortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Technology" aria-label="Add Technology">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endcan
                    @endif
                </div>

                @error('project_technology_ids')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Domain -->
            <div class="flex flex-col gap-2">
                <label for="domain" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Domain
                </label>
                <input type="text" name="domain" id="domain" value="{{ old('domain', $project->domain ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('domain') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" x-on:input="markDirty()">

                @error('domain')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Default Billable -->
            <div class="flex flex-col gap-2" x-data="{
                billable: {{ old('default_billable', $project->default_billable ?? 0) ? 'true' : 'false' }},
                markDirty() { $dispatch('form-dirty') }
            }">
                <label for="default_billable" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Default Billable
                </label>

                <!-- Hidden input -->
                <input type="hidden" name="default_billable" :value="billable ? 1 : 0">

                <!-- Toggle Button (YOUR DESIGN, FIXED) -->
                <button type="button" id="default_billable" @click="billable = !billable; markDirty()" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="billable ? 'active' : ''" :aria-checked="billable.toString()" role="switch">
                    <!-- Circle -->
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>

                @error('default_billable')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    @if (!$canEdit)
        </fieldset>
    @endif

    @if ($canEdit)
        <div class="pt-6 border-t flex justify-end dark:border-darkblack-400">
            <button type="button" id="update-project" data-project-id="{{ $project->id }}" class="px-6 py-2 bg-success-300 text-white rounded-lg font-semibold hover:bg-success-400" :disabled="!dirty">
                Update Project
            </button>
        </div>
    @endif
</form>

@if ($canEdit)
    @can('project_category.create')
        <x-form-modal modalId="project-category-modal" module="Project Category" formId="projectCategoryInlineForm" action="{{ route('settings.project-categories.store') }}" button="Create Project Category">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        </x-form-modal>
    @endcan

    @can('technology.create')
        <x-form-modal modalId="project-technology-modal" module="Technology" formId="projectTechnologyInlineForm" action="{{ route('settings.technologies.store') }}" button="Create Technology">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        </x-form-modal>
    @endcan
@endif
