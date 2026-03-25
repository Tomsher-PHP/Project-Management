<form id="project-settings-form" action="{{ route('projects.update', $project->id) }}" method="POST" class="space-y-10">
    @csrf
    @method('PUT')

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
                <input type="text" id="name" name="name" value="{{ old('name', $project->name ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('name') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Customer -->
            <div class="flex flex-col gap-2">
                <label for="customer_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Customer
                </label>
                <select name="customer_id" id="customer_id" class="tom-select w-full @error('customer_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror">
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
                <select name="priority" id="priority" class="select-no-search w-full">
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

            <!-- Status -->
            <div class="flex flex-col gap-2">
                <label for="project_status" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Status <x-red-star />
                </label>
                <select name="project_status" id="project_status" class="select-no-search w-full">
                    <option value="">Select Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" {{ old('project_status', $project->status_id ?? '') == $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_status')
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
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', isset($project) ? $project->start_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @error('start_date')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Internal End Date -->
            <div class="flex flex-col gap-2">
                <label for="internal_end_date" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Internal End Date
                </label>
                <input type="date" name="internal_end_date" id="internal_end_date" value="{{ old('internal_end_date', $project->internal_end_date?->format('Y-m-d') ?? '') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @error('internal_end_date')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Client End Date -->
            <div class="flex flex-col gap-2">
                <label for="client_end_date" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Client End Date
                </label>
                <input type="date" name="client_end_date" id="client_end_date" value="{{ old('client_end_date', $project->client_end_date?->format('Y-m-d') ?? '') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @error('client_end_date')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estimated Time -->
            <div class="flex flex-col gap-2">
                <label for="estimated_time_hrs" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Estimated Time (hrs)
                </label>
                <input type="number" name="estimated_time_hrs" id="estimated_time_hrs" value="{{ old('estimated_time_hrs', $project->estimated_time_seconds ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('estimated_time_hrs') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror">
                @error('estimated_time_hrs')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>


    <!-- ================= Other Information ================= -->
    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 dark:border-darkblack-400 dark:text-white">
                Other Information
            </h3>

            <!-- Domain -->
            <div class="flex flex-col gap-2">
                <label for="domain" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Domain
                </label>
                <input type="text" name="domain" id="domain" value="{{ old('domain', $project->domain ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('domain') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror">

                @error('domain')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sales Person -->
            <div class="flex flex-col gap-2">
                <label for="sales_person_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Sales Person
                </label>
                <select name="sales_person_id" id="sales_person_id" class="tom-select w-full @error('sales_person_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" data-sort="0">
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

            <!-- Project Stage -->
            <div class="flex flex-col gap-2">
                <label for="project_stage" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Stage
                </label>
                <select name="project_stage" id="project_stage" class="select-no-search w-full">
                    <option value="">Select Project Stage</option>
                    @foreach ($projectStages as $key => $stage)
                        <option value="{{ $key }}" {{ old('project_stage', $project->project_stage ?? '') == $key ? 'selected' : '' }}>
                            {{ $stage }}
                        </option>
                    @endforeach
                </select>
                @error('project_stage')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Project Category -->
            <div class="flex flex-col gap-2">
                <label for="project_category_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Project Category
                </label>
                <select name="project_category_id" id="project_category_id" class="tom-select w-full">
                    <option value="">Select Project Category</option>
                    @foreach ($projectCategories as $category)
                        <option value="{{ $category->id }}" {{ old('project_category_id', $project->project_category_id ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_category_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Default Billable -->
            <div class="flex flex-col gap-2" x-data="{ billable: {{ old('default_billable', $project->default_billable ?? 0) ? 'true' : 'false' }} }">
                <label for="default_billable" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Default Billable
                </label>

                <!-- Hidden input -->
                <input type="hidden" name="default_billable" :value="billable ? 1 : 0">

                <!-- Toggle Button (YOUR DESIGN, FIXED) -->
                <button type="button" id="default_billable" @click="billable = !billable" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="billable ? 'active' : ''" :aria-checked="billable.toString()" role="switch">
                    <!-- Circle -->
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>

                @error('default_billable')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    <button type="button" id="update-project" data-project-id="{{ $project->id }}" class="mt-5 px-6 py-2 bg-success-300 text-white rounded-lg font-semibold hover:bg-success-400">
        Update Project
    </button>

</form>
