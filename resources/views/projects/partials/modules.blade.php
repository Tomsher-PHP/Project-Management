<div class="space-y-6">
    <div class="rounded-xl border border-bgray-200 bg-bgray-50 p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Project Modules</h3>
                <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                    Agile projects follow the flow <span class="font-semibold text-bgray-900 dark:text-white">Project -> Module -> Sprint -> Tasks</span>.
                </p>
            </div>

            @can('project.edit')
                <a
                    href="javascript:void(0)"
                    data-target="#project-module-modal"
                    data-module="Project Module"
                    data-url="{{ route('projects.modules.store', $project) }}"
                    data-method="POST"
                    class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400"
                    data-module-context="project-module"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Module</span>
                </a>
            @endcan
        </div>
    </div>

    <div class="rounded-xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="mb-5 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">Module Plan</h4>
                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                    Add modules from the software library or create custom modules only for this project.
                </p>
            </div>
            <div class="inline-flex items-center rounded-lg bg-bgray-50 px-4 py-2 text-sm font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                {{ $projectModules->count() }} project modules
            </div>
        </div>

        <div class="table-content w-full overflow-x-auto">
            <table class="w-full">
                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                    <td><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span></td>
                    <td class="px-6 py-5 xl:px-0"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Module</span></td>
                    <td class="px-6 py-5 xl:px-0"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Estimate</span></td>
                    <td class="px-6 py-5 xl:px-0"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Added By</span></td>
                    <td class="px-6 py-5 xl:px-0"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span></td>
                </tr>

                @forelse ($projectModules as $module)
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <td class="px-6 py-5 xl:px-0">
                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $loop->iteration }}</span>
                        </td>
                        <td class="px-6 py-5 xl:px-0">
                            <div class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-10 w-10 shrink-0 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $module->color ?: '#E5E7EB' }}"></span>
                                <div>
                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">{{ $module->name }}</p>
                                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">{{ $module->description ?: 'No description added.' }}</p>
                                    <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300">Order {{ $module->order }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 xl:px-0">
                            <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                {{ $module->estimated_time_formatted }}
                            </span>
                        </td>
                        <td class="px-6 py-5 xl:px-0">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $module->addedBy?->name ?? 'System' }}</span>
                                @if ($module->updatedBy?->name)
                                    <span class="text-xs text-bgray-500 dark:text-bgray-300">Updated by {{ $module->updatedBy->name }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-5 xl:px-0">
                            <div class="flex items-center gap-2">
                                @can('project.edit')
                                    <a
                                        href="javascript:void(0)"
                                        class="edit-record"
                                        data-modal="project-module-modal"
                                        data-url="{{ route('projects.modules.update', [$project, $module]) }}"
                                        data-name="{{ $module->name }}"
                                        data-color="{{ $module->color }}"
                                        data-description="{{ $module->description }}"
                                        data-estimated_time_minutes="{{ $module->estimated_time_minutes }}"
                                        data-order="{{ $module->order }}"
                                        data-method="PUT"
                                        data-module="Project Module"
                                        data-module-context="project-module"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 transition group-hover:text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                        </svg>
                                    </a>

                                    <x-delete-form :action="route('projects.modules.destroy', [$project, $module])" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-no-data :col-span="5" message="No project modules added yet." />
                @endforelse
            </table>
        </div>
    </div>
</div>

@can('project.edit')
    <x-form-modal modalId="project-module-modal" module="Project Module" formId="projectModuleForm" :action="route('projects.modules.store', $project)" button="Create Project Module">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Module Library</label>
            <select name="library_module_id" id="library_module_id" class="project-module-library-select w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <option value="">Create custom module</option>
                @foreach ($agileModules as $libraryModule)
                    <option
                        value="{{ $libraryModule->id }}"
                        data-name="{{ $libraryModule->name }}"
                        data-color="{{ $libraryModule->color }}"
                        data-description="{{ $libraryModule->description }}"
                    >
                        {{ $libraryModule->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                Selecting a library module copies its values into this project module form only.
            </p>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
            <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Estimated Time in Minutes</label>
            <input type="number" min="0" name="estimated_time_minutes" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Order <x-red-star /></label>
            <input type="number" min="1" name="order" value="{{ max($projectModules->count() + 1, 1) }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>
    </x-form-modal>
@endcan
