<div class="space-y-6" data-project-module-section>
    @php
        $librarySprintCount = $agileSprints->count();
        $canEditProjectModules = auth()->user()->can('project_module.edit');
        $projectModuleReorderUrl = $canEditProjectModules ? route('projects.modules.reorder', $project) : null;
        $taskPreviewPalette = ['Setup workspace', 'Define acceptance criteria', 'Create UI draft', 'Review with team', 'QA pass'];
        $trashedCount = $trashedProjectModules->count();
    @endphp

    <div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="border-b border-bgray-200 bg-bgray-50/70 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h4 class="mt-2 text-lg font-bold text-bgray-900 dark:text-white">Module -> Sprint -> Task Planner</h4>
                    <p class="mt-1 max-w-3xl text-sm text-bgray-600 dark:text-bgray-300">
                        Compact builder view focused on module items with nested sprint and task previews.
                    </p>
                    @can('project_module.edit')
                        <p class="mt-2 inline-flex items-center rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-500 dark:bg-darkblack-500 dark:text-success-300">
                            Click Change Order -> Drag Modules -> Save Order
                        </p>
                    @endcan
                </div>

                <div class="flex flex-col gap-2 xl:items-end">
                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50">
                            <span class="text-bgray-500 dark:text-bgray-300">Modules</span>
                            <span class="font-semibold text-bgray-900 dark:text-white">{{ $projectModules->count() }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50">
                            <span class="text-bgray-500 dark:text-bgray-300">Sprint Library</span>
                            <span class="font-semibold text-bgray-900 dark:text-white">{{ $librarySprintCount }}</span>
                        </span>

                        @can('project_module.restore')
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:bg-bgray-100 disabled:text-bgray-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300 dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-500 dark:disabled:text-bgray-500"
                                data-project-module-restore-open
                                @disabled($trashedCount === 0)
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 6.172a4 4 0 015.656 0L10 7.343l1.172-1.171a4 4 0 115.656 5.656l-1.829 1.829a4 4 0 01-5.656 0L4.515 8.828a4 4 0 010-5.656zM10 5a1 1 0 00-1 1v2H7a1 1 0 000 2h3a1 1 0 001-1V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span>Restore</span>
                                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-bgray-100 px-1.5 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $trashedCount }}</span>
                            </button>
                        @endcan

                        @can('project_module.create')
                            <a href="javascript:void(0)" data-target="#project-module-modal" data-module="Project Module" data-url="{{ route('projects.modules.store', $project) }}" data-method="POST" class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-module-context="project-module">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Module</span>
                            </a>
                        @endcan
                    </div>

                    @can('project_module.edit')
                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300"
                                data-project-module-reorder-toggle
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                </svg>
                                <span data-project-module-reorder-toggle-label>Change Order</span>
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-400 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-300 hover:text-white disabled:cursor-not-allowed disabled:border-bgray-200 disabled:bg-bgray-100 disabled:text-bgray-400 dark:border-success-900/30 dark:bg-darkblack-600 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-500 dark:disabled:text-bgray-500"
                                data-project-module-reorder-save
                                disabled
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.75-3.75a1 1 0 111.414-1.414l3.043 3.043 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span>Save Order</span>
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="space-y-6 overflow-y-auto px-5 py-5 pr-3 min-h-[42rem] max-h-[42rem]" data-project-module-list @if ($projectModuleReorderUrl) data-reorder-url="{{ $projectModuleReorderUrl }}" @endif>
            @forelse ($projectModules as $module)
                @php
                    $modulePreviewSprints = collect([
                        [
                            'name' => $agileSprints->get(0)?->name ?? 'Sprint 1',
                            'color' => $agileSprints->get(0)?->color ?? '#10B981',
                            'tasks' => [$module->name . ' kickoff', $taskPreviewPalette[($loop->index + 1) % count($taskPreviewPalette)]],
                        ],
                        [
                            'name' => $agileSprints->get(1)?->name ?? 'Sprint 2',
                            'color' => $agileSprints->get(1)?->color ?? '#F59E0B',
                            'tasks' => [$taskPreviewPalette[($loop->index + 2) % count($taskPreviewPalette)]],
                        ],
                    ]);
                @endphp

                <div x-data="{ open: false, showFullDescription: false }" class="overflow-hidden rounded-2xl border border-bgray-200 bg-bgray-50/60 shadow-sm transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500/50" data-project-module-card data-module-id="{{ $module->id }}" data-module-color="{{ $module->color ?: '#D1D5DB' }}" draggable="false" style="border-color: {{ $module->color ?: '#D1D5DB' }}">
                    <div class="border-b border-bgray-200 bg-white px-4 py-4 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-5" data-project-module-card-header>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex items-start gap-4">
                                <button type="button" class="mt-0.5 inline-flex h-10 w-10 shrink-0 cursor-move items-center justify-center rounded-xl border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-project-module-drag-handle>
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                    </svg>
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="min-w-0">
                                            @php
                                                $moduleDescription = $module->description ?: 'No description added yet.';
                                                $hasLongDescription = \Illuminate\Support\Str::length($moduleDescription) > 60;
                                                $taskPreviewCount = $modulePreviewSprints->sum(fn($sprint) => count($sprint['tasks']));
                                            @endphp
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex h-3.5 w-3.5 shrink-0 rounded-sm" style="background-color: {{ $module->color ?: '#E5E7EB' }}"></span>
                                                <h5 class="text-lg font-semibold text-bgray-900 dark:text-white">{{ $module->name }}</h5>
                                                <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50" data-project-module-order-badge>Order {{ $module->order }}</span>
                                                <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $module->estimated_time_formatted }}</span>
                                                <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $modulePreviewSprints->count() }} sprints</span>
                                                <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $taskPreviewCount }} tasks</span>
                                            </div>

                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm leading-6 text-bgray-600 dark:text-bgray-300">
                                                <p class="min-w-0">
                                                    <span x-show="!showFullDescription">{{ $hasLongDescription ? \Illuminate\Support\Str::limit($moduleDescription, 60) : $moduleDescription }}</span>
                                                    @if ($hasLongDescription)
                                                        <span x-show="showFullDescription">{{ $moduleDescription }}</span>
                                                    @endif
                                                </p>

                                                @if ($hasLongDescription)
                                                    <button type="button" @click="showFullDescription = !showFullDescription" class="inline-flex items-center text-xs font-semibold text-success-400 transition duration-200 hover:text-success-300">
                                                        <span x-text="showFullDescription ? 'Show Less' : 'Show More'"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 xl:max-w-[320px] xl:justify-end">
                                @can('project_sprint.create')
                                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-1.5 text-sm font-medium text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-300 hover:text-white dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Sprint</span>
                                    </button>
                                @endcan

                                @can('project_module.edit')
                                    <a href="javascript:void(0)" class="edit-record inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" data-modal="project-module-modal" data-url="{{ route('projects.modules.update', [$project, $module]) }}" data-name="{{ $module->name }}"
                                        data-color="{{ $module->color }}" data-description="{{ $module->description }}" data-estimated_time_minutes="{{ $module->estimated_time_minutes }}" data-method="PUT" data-module="Project Module" data-module-context="project-module">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                        </svg>
                                    </a>
                                @endcan

                                @can('project_module.delete')
                                    <x-delete-form
                                        :action="route('projects.modules.destroy', [$project, $module])"
                                        ajax
                                        render-target="[data-project-module-section]"
                                        render-mode="replace_outer"
                                    />
                                @endcan

                                <button type="button" @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                                    <svg class="h-5 w-5 transition duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="open" x-transition class="px-4 py-5 sm:px-5">
                        @include('projects.partials.module.sprints', ['modulePreviewSprints' => $modulePreviewSprints])
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div class="mx-auto flex max-w-xl flex-col items-center">
                        <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-success-400 shadow-sm dark:bg-darkblack-600 dark:text-success-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" />
                            </svg>
                        </span>

                        <h5 class="mt-5 text-xl font-semibold text-bgray-900 dark:text-white">Start Your Module Builder</h5>
                        <p class="mt-2 text-sm leading-6 text-bgray-500 dark:text-bgray-300">
                            Create the first module to unlock the nested structure for sprints and tasks. Once a module exists, this page will expand into builder cards with inline actions at every level.
                        </p>

                        @can('project_module.create')
                            <a href="javascript:void(0)" data-target="#project-module-modal" data-module="Project Module" data-url="{{ route('projects.modules.store', $project) }}" data-method="POST" class="modal-open mt-5 inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-module-context="project-module">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add First Module</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @can('project_module.restore')
        <div class="modal fixed inset-0 z-50 hidden overflow-y-auto" id="project-module-restore-modal" data-project-module-restore-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-module-restore-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                            <div>
                                <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white">Restore Project Module</h3>
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">Restore a deleted module back into this project. It will be placed at the end of the module order.</p>
                            </div>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-module-restore-close>
                                ✕
                            </button>
                        </div>

                        <div class="max-h-[70vh] overflow-y-auto px-6 py-6 sm:px-7">
                            @if ($trashedProjectModules->isEmpty())
                                <div class="rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-10 text-center dark:border-darkblack-400 dark:bg-darkblack-500">
                                    <p class="text-sm font-medium text-bgray-600 dark:text-bgray-100">No deleted modules available to restore.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach ($trashedProjectModules as $trashedModule)
                                        <div class="flex flex-col gap-4 rounded-2xl border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/70 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex h-3.5 w-3.5 shrink-0 rounded-sm" style="background-color: {{ $trashedModule->color ?: '#E5E7EB' }}"></span>
                                                    <h5 class="text-base font-semibold text-bgray-900 dark:text-white">{{ $trashedModule->name }}</h5>
                                                </div>
                                                <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                                                    Deleted {{ $trashedModule->deleted_at?->diffForHumans() ?? 'recently' }}.
                                                    @if ($trashedModule->description)
                                                        {{ \Illuminate\Support\Str::limit($trashedModule->description, 100) }}
                                                    @endif
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400"
                                                data-project-module-restore-action
                                                data-restore-url="{{ route('projects.modules.restore', [$project, $trashedModule->id]) }}"
                                                data-module-name="{{ $trashedModule->name }}"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 3a1 1 0 00-1 1v2.586L6.707 5.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 10-1.414-1.414L10 6.586V4a1 1 0 00-1-1zm-5 9a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Restore</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
