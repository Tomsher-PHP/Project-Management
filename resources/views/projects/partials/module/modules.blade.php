@include('projects.partials.module.section')

@canany(['project_module.create', 'project_module.edit'])
    <x-form-modal modalId="project-module-modal" module="Project Module" formId="projectModuleForm" :action="route('projects.modules.store', $project)" button="Create Project Module">
        <div>
            <label class="mb-2.5 block text-left text-sm font-medium text-bgray-600 dark:text-white">Module Library</label>
            <select name="library_module_id" id="library_module_id" class="project-module-library-select tom-select w-full" data-placeholder="Select a library module">
                <option value="">Select a library module</option>
                @foreach ($agileModules as $libraryModule)
                    <option value="{{ $libraryModule->id }}" data-name="{{ $libraryModule->name }}" data-color="{{ $libraryModule->color }}" data-description="{{ $libraryModule->description }}">
                        {{ $libraryModule->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                Selecting a library module copies its values into this project module form only.
            </p>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm font-medium text-bgray-600 dark:text-white">Name <x-red-star /></label>
            <input type="text" name="name" placeholder="Enter module name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm font-medium text-bgray-600 dark:text-white">Color</label>
            <div class="rounded-2xl border border-bgray-200 bg-bgray-50/80 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/70">
                <div class="flex items-center gap-4 rounded-xl border border-bgray-200 bg-white p-3 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <input type="color" name="color" title="Choose module color" class="h-14 w-20 cursor-pointer rounded-xl border border-bgray-200 bg-transparent p-1 shadow-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400">
                    <div>
                        <p class="text-sm font-medium text-bgray-700 dark:text-white">Module Accent Color</p>
                        <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Used for the module card border and title marker.</p>
                    </div>
                </div>
            </div>
        </div>

        <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="300" hours-placeholder="Hours" minutes-placeholder="Minutes" panel />

        <div>
            <label class="mb-2.5 block text-left text-sm font-medium text-bgray-600 dark:text-white">Description</label>
            <textarea name="description" rows="3" maxlength="100" placeholder="Add a short module description" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
            <p class="mt-1 text-right text-xs text-bgray-500 dark:text-bgray-300">
                <span data-project-module-description-count>0</span>/100 characters
            </p>
        </div>
    </x-form-modal>
@endcanany
