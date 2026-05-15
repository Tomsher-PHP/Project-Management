@php
    $handoffAccessibleProjects = \App\Models\Project::accessibleBy(auth()->user())
        ->where('is_active', true)
        ->whereHas('projectStatus', function ($q) {
            $q->where('is_completed', false);
        })
        ->get();
    $handoffPurposes = \App\Models\HandoffPurpose::active()->get();
@endphp

<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-handoff-create-modal id="handoff_create_modal">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-handoff-create-close></div>

    <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
        <div class="relative z-10 w-full max-w-lg transition-all duration-200" data-handoff-create-modal-panel>
            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Create Handoff Request
                        </h3>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-handoff-create-close>
                        ✕
                    </button>
                </div>

                <form class="space-y-4 overflow-y-auto px-5 py-5" data-handoff-create-form data-store-url="{{ route('handoff_requests.store') }}">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="handoff_project_id" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Project <x-red-star /></label>
                            <select id="handoff_project_id" name="project_id" class="tom-select w-full" data-handoff-project-select>
                                <option value="">Select project</option>
                                @foreach ($handoffAccessibleProjects as $projectOption)
                                    <option value="{{ $projectOption->id }}" data-flow="{{ $projectOption->project_flow }}">
                                        {{ $projectOption->name }} ({{ $projectOption->project_code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="project_id"></p>
                        </div>

                        <div>
                            <label for="handoff_project_milestone_id" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Milestone</label>
                            <select id="handoff_project_milestone_id" name="project_milestone_id" class="tom-select w-full" data-handoff-milestone-select>
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="project_milestone_id"></p>
                        </div>

                        <div>
                            <label for="handoff_project_sprint_id" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Sprint</label>
                            <select id="handoff_project_sprint_id" name="project_sprint_id" class="tom-select w-full" data-handoff-sprint-select>
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="project_sprint_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="handoff_source_task_id" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Source Task</label>
                            <select id="handoff_source_task_id" name="source_task_id" class="tom-select w-full" data-handoff-task-select>
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="source_task_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="handoff_purpose" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Purpose <x-red-star /></label>
                            <select id="handoff_purpose" name="purpose" class="tom-select-add w-full" data-handoff-purpose-select data-placeholder="Select or type a purpose..." data-sort="0" data-max-items="1">
                                <option value="">Select or type a purpose...</option>
                                @foreach ($handoffPurposes as $purpose)
                                    <option value="{{ $purpose->name }}">{{ $purpose->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="purpose"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="handoff_description" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Description <x-red-star /></label>
                            <textarea id="handoff_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Provide handoff details"></textarea>
                            <p class="mt-1 hidden text-xs text-red-500" data-handoff-create-error="description"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-darkblack-300" data-handoff-create-close>
                            Cancel
                        </button>

                        <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-handoff-create-submit>
                            Create Handoff
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
