<div>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Project Tasks</h3>
        @if ($project->is_agile)
            <span class="inline-flex items-center rounded-lg bg-success-50 px-4 py-2 text-sm font-medium text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                Tasks will be managed under modules and sprints for agile projects.
            </span>
        @endif
        <button class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white hover:bg-success-400">
            + Add Task
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                    <th class="py-3 text-left text-sm font-semibold text-bgray-600">Task</th>
                    <th class="py-3 text-left text-sm font-semibold text-bgray-600">Assigned To</th>
                    <th class="py-3 text-left text-sm font-semibold text-bgray-600">Due Date</th>
                    <th class="py-3 text-left text-sm font-semibold text-bgray-600">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-bgray-200">
                    <td class="py-4 font-medium text-bgray-900 dark:text-white">Sample static task</td>
                    <td class="py-4 text-bgray-600">John</td>
                    <td class="py-4 text-bgray-600">25 Mar 2026</td>
                    <td class="py-4">
                        <span class="rounded bg-warning-50 px-3 py-1 text-xs text-warning-500">Pending</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
