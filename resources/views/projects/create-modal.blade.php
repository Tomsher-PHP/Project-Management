<div id="projectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">

    <div class="bg-white dark:bg-darkblack-600 rounded-xl w-full max-w-lg p-6 shadow-lg">

        <h2 class="text-xl font-bold mb-4 dark:text-white">Create Project</h2>

        <form method="POST" action="{{ route('projects.store') }}">
            @csrf

            <div class="space-y-4">

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium">Project Name <x-red-star /></label>
                    <input type="text" name="name" class="form-input w-full">
                </div>

                <!-- Customer -->
                <div>
                    <label for="customer_id" class="block text-sm font-medium">Customer</label>
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
                    <label for="project_flow" class="block text-sm font-medium">Project Flow <x-red-star /></label>
                    <select name="project_flow" id="project_flow" class="tom-select w-full">
                        @php
                            $defaultProjectFlow = old('project_flow', 'agile');
                        @endphp
                        @foreach (config('project_constants.project_flows', []) as $key => $label)
                            <option value="{{ $key }}" {{ (string) $defaultProjectFlow === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('project_flow')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-medium">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" value="{{ old('start_date', now($globalTimezone)->toDateString()) }}" data-format="{{ $globalDateFormat }}" placeholder="Select a date">

                    @error('start_date')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="closeProjectModal" class="px-4 py-2 rounded-md bg-gray-200 dark:bg-darkblack-500">
                    Cancel
                </button>

                <button type="submit" class="px-4 py-2 rounded-md bg-success-300 text-white hover:bg-success-400">
                    Create
                </button>
            </div>

        </form>
    </div>
</div>
