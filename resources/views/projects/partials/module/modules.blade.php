@php
    $projectModuleBuilderConfig = [
        'storeUrl' => route('projects.modules.store', $project),
        'updateUrlTemplate' => route('projects.modules.update', ['project' => $project, 'projectModule' => '__MODULE__']),
        'destroyUrlTemplate' => route('projects.modules.destroy', ['project' => $project, 'projectModule' => '__MODULE__']),
        'reorderUrl' => route('projects.modules.reorder', $project),
        'libraryStoreUrl' => route('settings.agile-modules.store'),
        'nextLibrarySortOrder' => ((int) $agileModules->max('sort_order')) + 1,
        'owners' => $assignableUsers->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
        ])->values(),
    ];
    $projectSprintBuilderConfig = [
        'storeUrlTemplate' => route('projects.modules.sprints.store', ['project' => $project, 'projectModule' => '__MODULE__']),
        'updateUrlTemplate' => route('projects.sprints.update', ['project' => $project, 'projectSprint' => '__SPRINT__']),
        'destroyUrlTemplate' => route('projects.sprints.destroy', ['project' => $project, 'projectSprint' => '__SPRINT__']),
        'reorderUrlTemplate' => route('projects.modules.sprints.reorder', ['project' => $project, 'projectModule' => '__MODULE__']),
        'libraryStoreUrl' => route('settings.agile-sprints.store'),
        'nextLibrarySortOrder' => ((int) $agileSprints->max('sort_order')) + 1,
        'canDelete' => auth()->user()->can('project_sprint.delete'),
    ];
@endphp

@include('projects.partials.module.section')

@canany(['project_module.create', 'project_module.edit'])
    <div class="modal fixed inset-0 z-50 hidden overflow-y-auto modal-form" id="project-module-modal" data-project-module-builder-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-module-builder-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-7xl">
                <div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <div>
                            <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">
                                Build Project Modules
                            </h3>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="rounded-full border border-bgray-200 bg-bgray-50 px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                                Selected: <span data-project-module-builder-count>{{ $projectModules->count() }}</span>
                            </div>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-module-builder-close>
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="grid h-[82vh] max-h-[82vh] gap-0 overflow-hidden xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]">
                        <div class="flex min-h-0 flex-col border-b border-bgray-200 p-6 dark:border-darkblack-400 xl:border-b-0 xl:border-r">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Work Area</h4>
                                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                        Drop needed modules here, then adjust only the working details your team needs right now.
                                    </p>
                                </div>

                                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm font-medium text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300" data-project-module-builder-reset-search>
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a7 7 0 1112.95 3.95l1.55 1.55a1 1 0 01-1.414 1.414l-1.55-1.55A7 7 0 013 10zm7-5a5 5 0 100 10 5 5 0 000-10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Show All Library</span>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl border border-dashed border-success-200 bg-success-50/30 p-4 pr-3 dark:border-success-900/30 dark:bg-darkblack-500/20">
                                    <div class="space-y-4" data-project-module-builder-workspace>
                                    @forelse ($projectModules as $module)
                                        <article class="select-text rounded-none border bg-white p-4 shadow-sm dark:bg-darkblack-600" style="border-color: {{ $module->color ?: '#E5E7EB' }};" data-project-module-builder-card data-module-id="{{ $module->id }}" data-module-name="{{ $module->name }}" data-expanded="false" draggable="false">
                                            <input type="hidden" name="color" value="{{ $module->color ?: '#22C55E' }}">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="flex items-start gap-3">
                                                    <button type="button" class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-project-module-builder-drag-handle>
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                        </svg>
                                                    </button>

                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex h-3.5 w-3.5 rounded-sm" style="background-color: {{ $module->color ?: '#22C55E' }}" data-project-module-builder-color-dot></span>
                                                            <h5 class="text-base font-semibold text-bgray-900 dark:text-white" data-project-module-builder-title>{{ $module->name }}</h5>
                                                            <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200" data-project-module-builder-order>{{ $module->sort_order }}</span>
                                                        </div>
                                                        <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300" data-project-module-builder-status>
                                                            Saved
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-500 transition duration-200 hover:border-red-300 hover:bg-red-100 dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400" data-project-module-builder-delete aria-label="Delete module" title="Delete module">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-module-builder-toggle aria-label="Expand module" title="Expand module">
                                                        <svg class="h-4 w-4 rotate-180 transition duration-200" viewBox="0 0 20 20" fill="currentColor" data-project-module-builder-toggle-icon>
                                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4 hidden border-t border-bgray-100 pt-4 dark:border-darkblack-400" data-project-module-builder-body>
                                                <div class="grid gap-4 xl:grid-cols-2">
                                                <div>
                                                    <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Name <x-red-star /></label>
                                                    <input type="text" name="name" value="{{ $module->name }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Owner</label>
                                                    <select name="owner_id" class="tom-select w-full" data-sort="0">
                                                        <option value="">Select owner</option>
                                                        @foreach ($assignableUsers as $assignableUser)
                                                            <option value="{{ $assignableUser->id }}" @selected((int) $module->owner_id === (int) $assignableUser->id)>{{ $assignableUser->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <x-forms.estimated-time-input
                                                        label="Estimated Time"
                                                        name="estimated_time_minutes"
                                                        :total-minutes="$module->estimated_time_minutes ?? 0"
                                                        :show-label="false"
                                                    />
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Date Range</label>
                                                    <input type="text" value="{{ $module->start_date?->format('Y-m-d') }}{{ $module->start_date && $module->end_date ? ' to ' : '' }}{{ $module->end_date?->format('Y-m-d') }}" class="datepicker project-module-date-range w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" data-min-date="{{ now(config('constants.timezone'))->toDateString() }}" data-project-module-builder-date-range>
                                                    <input type="hidden" name="start_date" value="{{ $module->start_date?->format('Y-m-d') }}">
                                                    <input type="hidden" name="end_date" value="{{ $module->end_date?->format('Y-m-d') }}">
                                                </div>

                                                <div class="xl:col-span-2">
                                                    <div class="mb-2 flex items-center justify-between gap-3">
                                                        <label class="block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Description</label>
                                                        <span class="text-[11px] font-medium text-bgray-400 dark:text-bgray-300"><span data-project-module-builder-description-count>{{ strlen($module->description ?? '') }}</span>/100</span>
                                                    </div>
                                                    <textarea name="description" rows="2" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ $module->description }}</textarea>
                                                </div>
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600" data-project-module-builder-empty>
                                            <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </span>
                                            <h5 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No Modules Selected Yet</h5>
                                            <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                                                Drag one or more items from the module library to start building this project workspace.
                                            </p>
                                        </div>
                                    @endforelse
                                        @if ($projectModules->isNotEmpty())
                                            <div class="flex items-center gap-3 rounded-2xl border border-dashed border-success-200/80 bg-white/75 px-4 py-3 text-success-500 dark:border-success-900/40 dark:bg-darkblack-600/60 dark:text-success-300" data-project-module-builder-helper>
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-success-50 text-success-500 dark:bg-darkblack-500 dark:text-success-300">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <p class="text-sm font-semibold">Drag here for more modules</p>
                                                    <p class="text-xs text-bgray-500 dark:text-bgray-300">Drop another library item anywhere in this workspace to add it to the project.</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="h-24 rounded-2xl border border-dashed border-bgray-200/70 bg-bgray-50/40 dark:border-darkblack-400/60 dark:bg-darkblack-500/20" data-project-module-builder-dropzone></div>
                                    </div>
                            </div>
                        </div>

                        <aside class="flex min-h-0 flex-col overflow-hidden bg-bgray-50/60 p-6 dark:bg-darkblack-500/40">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Module Library</h4>
                                @can('agile_module.create')
                                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-success-200 bg-white text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-500 dark:border-success-900/30 dark:bg-darkblack-600 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-darkblack-500" data-project-module-library-create-open aria-label="Add module library item" title="Add module library item">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                @endcan
                            </div>

                            <label class="relative mb-4 block">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-bgray-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 013.93 9.35l3.61 3.61a1 1 0 01-1.414 1.414l-3.61-3.61A5.5 5.5 0 118.5 3zm0 2a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <input type="text" class="w-full rounded-xl border border-bgray-200 bg-white py-3 pl-11 pr-4 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Search module library..." data-project-module-builder-library-search>
                            </label>

                            <div class="min-h-0 flex-1 overflow-y-scroll pr-1 [scrollbar-gutter:stable]" data-project-module-builder-library-scroll>
                                <div class="space-y-3" data-project-module-builder-library>
                                    @foreach ($agileModules as $libraryModule)
                                        <article class="cursor-grab rounded-none border border-bgray-200 bg-white p-4 shadow-sm transition duration-200 hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:border-success-300" draggable="true" data-project-module-library-item data-library-module-id="{{ $libraryModule->id }}" data-name="{{ $libraryModule->name }}" data-color="{{ $libraryModule->color ?: '#22C55E' }}" data-description="{{ $libraryModule->description }}" data-sort-order="{{ $libraryModule->sort_order }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex h-3.5 w-3.5 rounded-sm" style="background-color: {{ $libraryModule->color ?: '#22C55E' }}"></span>
                                                        <h5 class="truncate text-sm font-semibold text-bgray-900 dark:text-white">
                                                            {{ $libraryModule->name }}
                                                        </h5>
                                                    </div>
                                                    <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                                                        {{ $libraryModule->description ?: 'No library description added yet.' }}
                                                    </p>
                                                </div>

                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8M8 17h8M5 7h.01M5 12h.01M5 17h.01" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        <script type="application/json" id="project-module-builder-config">
            {!! json_encode($projectModuleBuilderConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </div>

    @can('agile_module.create')
        <div class="modal fixed inset-0 z-[60] hidden overflow-y-auto" id="project-module-library-create-modal" data-project-module-library-create-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-module-library-create-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                            <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white">Add Module</h3>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-module-library-create-close>
                                ✕
                            </button>
                        </div>

                        <form class="flex max-h-[80vh] flex-col" data-project-module-library-create-form>
                            <div class="overflow-y-auto px-6 py-6 sm:px-7">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
                                        <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-module-library-create-error="name"></p>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
                                        <input type="color" name="color" value="#22C55E" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-module-library-create-error="color"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="mb-2.5 flex items-center justify-between gap-3">
                                            <label class="block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                                            <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-project-module-library-description-count>0</span>/100</span>
                                        </div>
                                        <textarea name="description" rows="3" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-module-library-create-error="description"></p>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
                                        <input type="number" name="sort_order" min="1" step="1" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-module-library-create-error="sort_order"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-module-library-create-close>
                                    Cancel
                                </button>

                                <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400" data-project-module-library-create-submit>
                                    Create Module
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endcanany

@canany(['project_sprint.create', 'project_sprint.edit'])
    <div class="modal fixed inset-0 z-50 hidden overflow-y-auto" id="project-sprint-modal" data-project-sprint-builder-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-sprint-builder-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-7xl">
                <div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <div>
                            <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">Build Project Sprints</h3>
                            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                Add library sprints into a module work area, then adjust the live sprint details your team needs.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="rounded-full border border-bgray-200 bg-bgray-50 px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                                Selected: <span data-project-sprint-builder-count>0</span>
                            </div>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-sprint-builder-close>
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="grid h-[82vh] max-h-[82vh] gap-0 overflow-hidden xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]">
                        <div class="flex min-h-0 flex-col border-b border-bgray-200 p-6 dark:border-darkblack-400 xl:border-b-0 xl:border-r">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Work Area</h4>
                                        <span class="inline-flex rounded-full border border-success-200 bg-success-50 px-3 py-1 text-xs font-semibold text-success-500 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300">
                                            Module: <span class="ml-1" data-project-sprint-builder-module-name>Select a module</span>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                        Drag needed sprints here, then fine-tune each sprint directly inside this module workspace.
                                    </p>
                                </div>

                                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm font-medium text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300" data-project-sprint-builder-reset-search>
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a7 7 0 1112.95 3.95l1.55 1.55a1 1 0 01-1.414 1.414l-1.55-1.55A7 7 0 013 10zm7-5a5 5 0 100 10 5 5 0 000-10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Show All Library</span>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl border border-dashed border-success-200 bg-success-50/30 p-4 pr-3 dark:border-success-900/30 dark:bg-darkblack-500/20">
                                <div class="space-y-4" data-project-sprint-builder-workspace></div>
                            </div>
                        </div>

                        <aside class="flex min-h-0 flex-col overflow-hidden bg-bgray-50/60 p-6 dark:bg-darkblack-500/40">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Sprint Library</h4>
                                @can('agile_sprint.create')
                                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-success-200 bg-white text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-500 dark:border-success-900/30 dark:bg-darkblack-600 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-darkblack-500" data-project-sprint-library-create-open aria-label="Add sprint library item" title="Add sprint library item">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                @endcan
                            </div>

                            <label class="relative mb-4 block">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-bgray-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 013.93 9.35l3.61 3.61a1 1 0 01-1.414 1.414l-3.61-3.61A5.5 5.5 0 118.5 3zm0 2a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <input type="text" class="w-full rounded-xl border border-bgray-200 bg-white py-3 pl-11 pr-4 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Search sprint library..." data-project-sprint-builder-library-search>
                            </label>

                            <div class="min-h-0 flex-1 overflow-y-scroll pr-1 [scrollbar-gutter:stable]" data-project-sprint-builder-library-scroll>
                                <div class="space-y-3" data-project-sprint-builder-library>
                                    @foreach ($agileSprints as $librarySprint)
                                        <article class="cursor-grab rounded-none border border-bgray-200 bg-white p-4 shadow-sm transition duration-200 hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:border-success-300" draggable="true" data-project-sprint-library-item data-library-sprint-id="{{ $librarySprint->id }}" data-name="{{ $librarySprint->name }}" data-color="{{ $librarySprint->color ?: '#22C55E' }}" data-description="{{ $librarySprint->description }}" data-sort-order="{{ $librarySprint->sort_order }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex h-3.5 w-3.5 rounded-sm" style="background-color: {{ $librarySprint->color ?: '#22C55E' }}"></span>
                                                        <h5 class="truncate text-sm font-semibold text-bgray-900 dark:text-white">
                                                            {{ $librarySprint->name }}
                                                        </h5>
                                                    </div>
                                                    <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                                                        {{ $librarySprint->description ?: 'No library description added yet.' }}
                                                    </p>
                                                </div>

                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8M8 17h8M5 7h.01M5 12h.01M5 17h.01" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        <script type="application/json" id="project-sprint-builder-config">
            {!! json_encode($projectSprintBuilderConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </div>

    @can('agile_sprint.create')
        <div class="modal fixed inset-0 z-[60] hidden overflow-y-auto" id="project-sprint-library-create-modal" data-project-sprint-library-create-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-sprint-library-create-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                            <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white">Add Sprint</h3>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-sprint-library-create-close>
                                ✕
                            </button>
                        </div>

                        <form class="flex max-h-[80vh] flex-col" data-project-sprint-library-create-form>
                            <div class="overflow-y-auto px-6 py-6 sm:px-7">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
                                        <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-sprint-library-create-error="name"></p>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
                                        <input type="color" name="color" value="#22C55E" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-sprint-library-create-error="color"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="mb-2.5 flex items-center justify-between gap-3">
                                            <label class="block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                                            <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-project-sprint-library-description-count>0</span>/100</span>
                                        </div>
                                        <textarea name="description" rows="3" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-sprint-library-create-error="description"></p>
                                    </div>

                                    <div>
                                        <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
                                        <input type="number" name="sort_order" min="1" step="1" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                        <p class="mt-1 hidden text-sm text-red-500" data-project-sprint-library-create-error="sort_order"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-sprint-library-create-close>
                                    Cancel
                                </button>

                                <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400" data-project-sprint-library-create-submit>
                                    Create Sprint
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endcanany
