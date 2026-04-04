<div class="overflow-hidden rounded-[20px] border border-bgray-200 dark:border-darkblack-400">
    <div class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-0">
            <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                <tr>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Task</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Assignee</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Status</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Type</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Estimate Time</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Due Date</th>
                    <th class="border-b border-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:text-bgray-100">Tags</th>
                </tr>
            </thead>

            <tbody class="bg-white dark:bg-darkblack-600">
                @forelse ($tasks as $task)
                    @php
                        $statusColor = $task->status?->color ?: '#CBD5E1';
                        $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium'))
                            ?? config('project_constants.task_priorities.medium');
                        $typeConfig = config('project_constants.task_type.' . ($task->task_type ?: 'normal'))
                            ?? config('project_constants.task_type.normal');
                        $typePalette = [
                            'gray' => ['bg' => '#E5E7EB', 'text' => '#374151'],
                            'green' => ['bg' => '#DCFCE7', 'text' => '#166534'],
                            'red' => ['bg' => '#FEE2E2', 'text' => '#B91C1C'],
                            'pink' => ['bg' => '#FCE7F3', 'text' => '#BE185D'],
                            'blue' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                            'violet' => ['bg' => '#EDE9FE', 'text' => '#6D28D9'],
                        ];
                        $typeColor = $typePalette[$typeConfig['color'] ?? 'gray'] ?? $typePalette['gray'];
                        $typeLabel = $typeConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'normal'));
                    @endphp

                    <tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60">
                        <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 h-12 w-1.5 flex-shrink-0 rounded-full {{ $priorityConfig['bg_class'] ?? 'bg-primary' }}"></span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-bgray-900 dark:text-white">{{ $task->title }}</p>

                                        <span class="rounded-full bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                            {{ $task->code ?: 'T-' . str_pad($task->id, 3, '0', STR_PAD_LEFT) }}
                                        </span>

                                        @if ($task->child_tasks_count > 0)
                                            <span class="rounded-full bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                                {{ $task->child_tasks_count }} subtask{{ $task->child_tasks_count > 1 ? 's' : '' }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($task->parentTask)
                                        <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Child of {{ $task->parentTask->title }}</p>
                                    @endif

                                    @if ($task->description)
                                        <p class="mt-1 line-clamp-2 text-sm text-bgray-600 dark:text-bgray-300">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($task->description), 120) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            @if ($task->currentAssignee)
                                <div class="flex items-center gap-3">
                                    <img src="{{ $task->currentAssignee->profile_image_url }}" alt="{{ $task->currentAssignee->name }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-white dark:ring-darkblack-500">
                                    <div>
                                        <p class="font-medium text-bgray-900 dark:text-white">{{ $task->currentAssignee->name }}</p>
                                    </div>
                                </div>
                            @else
                                <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                    Unassigned
                                </span>
                            @endif
                        </td>

                        <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            @if ($task->status)
                                <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColor }}"></span>
                                    {{ $task->status->name }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                    No status
                                </span>
                            @endif
                        </td>

                        <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            <span class="inline-flex min-w-[96px] items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold"
                                style="background-color: {{ $typeColor['bg'] }}; color: {{ $typeColor['text'] }};">
                                {{ $typeLabel }}
                            </span>
                        </td>

                        <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            <div class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</div>
                            <div class="text-xs text-bgray-500 dark:text-bgray-300">Actual {{ $task->actual_time_formatted }}</div>
                        </td>

                        <td class="border-b border-r border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                            @if ($task->due_date)
                                <div class="text-sm font-medium text-bgray-900 dark:text-white">@appDate($task->due_date)</div>
                                <div class="text-xs text-bgray-500 dark:text-bgray-300">Starts @appDate($task->start_date)</div>
                            @else
                                <span class="text-sm text-bgray-500 dark:text-bgray-300">No due date</span>
                            @endif
                        </td>

                        <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($task->tags as $tag)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold text-bgray-700 ring-1 ring-inset ring-bgray-200 dark:text-bgray-100 dark:ring-darkblack-400" @if ($tag->color) style="background-color: {{ $tag->color }}1A;" @endif>
                                        {{ $tag->name }}
                                    </span>
                                @empty
                                    <span class="text-sm text-bgray-500 dark:text-bgray-300">No tags</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center">
                            <div class="mx-auto max-w-md rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 dark:border-darkblack-400 dark:bg-darkblack-500">
                                <p class="text-base font-semibold text-bgray-900 dark:text-white">No tasks in {{ $group['name'] }}</p>
                                <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                                    This group is ready, but there are no tasks to display yet.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
