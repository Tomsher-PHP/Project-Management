@php
    $canEdit = auth()->user()->can('project.edit') && (isset($project) ? !$project->trashed() : true);
@endphp

@if ($canEdit)
    <x-form-modal modalId="project-edit-modal" module="Project" title="Edit Project" maxWidth="max-w-5xl" formId="projectEditForm" action="" button="Update Project">
        @csrf
        @method('PUT')

        <!-- Project Name -->
            <div class="col-span-1 md:col-span-2">
                <label for="edit_project_name" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Project Name <x-red-star /></label>
                <input type="text" name="name" id="edit_project_name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" required>
            </div>

            <!-- Customer -->
            <div>
                <label for="edit_project_customer_id" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Customer <x-red-star /></label>
                <select name="customer_id" id="edit_project_customer_id" class="tom-select w-full" required>
                    <option value="">Select Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label for="edit_project_priority" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Priority <x-red-star /></label>
                <select name="priority" id="edit_project_priority" class="tom-select-no-search w-full" required>
                    <option value="">Select Priority</option>
                    @foreach ($priorities as $key => $priority)
                        <option value="{{ $key }}">{{ $priority['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Parent Project -->
            <div class="col-span-1 md:col-span-2">
                <label for="edit_project_parent_project_id" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Parent Project / Rework For</label>
                <select name="parent_project_id" id="edit_project_parent_project_id" class="tom-select w-full">
                    <option value="">No parent project</option>
                    @isset($parentProjectOptions)
                        @foreach ($parentProjectOptions as $parentProjectOption)
                            <option value="{{ $parentProjectOption->id }}">
                                {{ $parentProjectOption->name }}{{ $parentProjectOption->project_code ? ' (' . $parentProjectOption->project_code . ')' : '' }}
                            </option>
                        @endforeach
                    @endisset
                </select>
                <p class="text-xs text-bgray-500 mt-1">Select a completed project only when this project is rework or follow-up work for an earlier delivered project.</p>
            </div>

            <!-- Start Date -->
            <div>
                <label for="edit_project_start_date" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Start Date</label>
                <input type="date" name="start_date" id="edit_project_start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- End Date -->
            <div>
                <label for="edit_project_end_date" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">End Date</label>
                <input type="date" name="end_date" id="edit_project_end_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Estimated Time -->
            <div class="col-span-1 md:col-span-2">
                <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" id="edit_project_estimated_time_minutes" :total-minutes="0" />
            </div>

            @can('project.customer_end_date')
                <!-- Customer End Date -->
                <div>
                    <label for="edit_project_customer_end_date" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Customer End Date</label>
                    <input type="date" name="customer_end_date" id="edit_project_customer_end_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                </div>

                <!-- Customer Estimate Time -->
                <div>
                    <x-forms.estimated-time-input label="Customer Estimate Time" name="customer_estimate_minutes" id="edit_project_customer_estimate_minutes" :total-minutes="0" />
                </div>
            @endcan

            <!-- Sales Person -->
            <div>
                <label for="edit_project_sales_person_id" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Sales Person</label>
                <select name="sales_person_id" id="edit_project_sales_person_id" class="tom-select w-full">
                    <option value="">Select</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" data-subtype="{{ $user->email }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Domain -->
            <div>
                <label for="edit_project_domain" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Domain</label>
                <input type="text" name="domain" id="edit_project_domain" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Project Category -->
            <div>
                <label for="edit_project_category_ids" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Project Category</label>
                <select name="project_category_ids[]" id="edit_project_category_ids" multiple class="tom-select-multiple w-full">
                    <option value="">Select Project Category</option>
                    @isset($projectCategories)
                        @foreach ($projectCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    @endisset
                </select>
            </div>

            <!-- Project Technology -->
            <div>
                <label for="edit_project_technology_ids" class="mb-2 block text-left text-sm font-medium text-bgray-600 dark:text-bgray-50">Project Technology</label>
                <select name="project_technology_ids[]" id="edit_project_technology_ids" multiple class="tom-select-multiple w-full">
                    <option value="">Select Project Technology</option>
                    @isset($projectTechnologies)
                        @foreach ($projectTechnologies as $technology)
                            <option value="{{ $technology->id }}">{{ $technology->name }}</option>
                        @endforeach
                    @endisset
                </select>
            </div>

            <!-- Default Billable -->
            <div class="col-span-1 md:col-span-2 mt-2" x-data="{ billable: false }" id="edit_project_default_billable_wrapper">
                <label for="edit_project_default_billable" class="mr-3 text-sm font-medium text-bgray-600 dark:text-bgray-50">Default Billable</label>
                <input type="hidden" name="default_billable" id="edit_project_default_billable_input" :value="billable ? 1 : 0">
                <button type="button" id="edit_project_default_billable" @click="billable = !billable" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="billable ? 'active' : ''" :aria-checked="billable.toString()" role="switch">
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
            </div>
    </x-form-modal>
@endif
