@php
    $cardClasses = 'rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600';
    $cardTitleClasses = 'text-base font-semibold text-bgray-900 dark:text-white';
    $cardTextClasses = 'text-sm text-bgray-600 dark:text-bgray-300';
    $emptyTextClasses = 'text-sm italic text-bgray-600 dark:text-bgray-300';
    $timeStatusClasses = ! $timeComparison['has_estimate']
        ? 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200'
        : ($timeComparison['is_over_estimate']
            ? 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-300'
            : 'bg-success-50 text-success-400 dark:bg-success-900/20 dark:text-success-200');
    $timeBarClasses = $timeComparison['is_over_estimate'] ? 'bg-red-500' : 'bg-success-400';
    $formatDuration = function (?int $seconds): string {
        $normalizedSeconds = max(0, (int) ($seconds ?? 0));
        $hours = intdiv($normalizedSeconds, 3600);
        $minutes = intdiv($normalizedSeconds % 3600, 60);

        return sprintf('%02dh %02dm', $hours, $minutes);
    };
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="{{ $cardClasses }}">
                <h3 class="{{ $cardTitleClasses }}">Description</h3>

                @if (filled($description))
                    <div class="mt-4 rounded-xl bg-bgray-50 px-4 py-4 text-sm leading-7 text-bgray-700 whitespace-pre-line dark:bg-darkblack-500 dark:text-bgray-300">{{ $description }}</div>
                @else
                    <p class="mt-4 {{ $emptyTextClasses }}">No task description added yet.</p>
                @endif
            </section>

            <section class="{{ $cardClasses }}">
                <h3 class="{{ $cardTitleClasses }}">Context</h3>

                <dl class="mt-4 divide-y divide-bgray-100 dark:divide-darkblack-400">
                    @foreach ($contextItems as $item)
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <dt class="text-sm font-medium text-bgray-600 dark:text-bgray-300">{{ $item['label'] }}</dt>
                            <dd class="max-w-[70%] text-right text-sm font-semibold text-bgray-900 dark:text-white">
                                @if (! empty($item['url']) && filled($item['value']) && $item['value'] !== '--')
                                    <a href="{{ $item['url'] }}" class="transition hover:text-success-400 dark:hover:text-success-300">{{ $item['value'] }}</a>
                                @else
                                    {{ $item['value'] }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="{{ $cardClasses }}">
                <h3 class="{{ $cardTitleClasses }}">Tags</h3>

                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($tags as $tag)
                        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $tag->color ?: '#94A3B8' }}"></span>
                            {{ $tag->name }}
                        </span>
                    @empty
                        <p class="{{ $emptyTextClasses }}">No tags assigned yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="{{ $cardClasses }}">
                <h3 class="{{ $cardTitleClasses }}">Task Details</h3>

                <div class="mt-4 space-y-3">
                    @foreach ($taskDetails as $item)
                        <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                            <p class="text-xs font-medium uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">{{ $item['label'] }}</p>

                            @if (! empty($item['color']))
                                <span class="mt-2 inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-600 dark:border-darkblack-400 dark:text-bgray-100">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item['color'] }}"></span>
                                    {{ $item['value'] }}
                                </span>
                            @elseif (! empty($item['badge_class']))
                                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white {{ $item['badge_class'] }}">
                                    {{ $item['value'] }}
                                </span>
                            @elseif (! empty($item['url']) && filled($item['value']) && $item['value'] !== '--')
                                <a href="{{ $item['url'] }}" class="mt-2 inline-flex text-sm font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300">
                                    {{ $item['value'] }}
                                </a>
                            @else
                                <p class="mt-2 text-sm font-semibold text-bgray-900 dark:text-white">{{ $item['value'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="{{ $cardClasses }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="{{ $cardTitleClasses }}">Estimated / Actual Time</h3>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $timeStatusClasses }}">
                        {{ $timeComparison['status_label'] }}
                    </span>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-xl bg-bgray-50 px-4 py-4 dark:bg-darkblack-500">
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">Estimated</p>
                        <p class="mt-2 text-base font-semibold text-bgray-900 dark:text-white">{{ $timeComparison['estimated_formatted'] }}</p>
                    </div>

                    <div class="rounded-xl bg-bgray-50 px-4 py-4 dark:bg-darkblack-500">
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">Actual</p>
                        <p class="mt-2 text-base font-semibold text-bgray-900 dark:text-white">{{ $timeComparison['actual_formatted'] }}</p>
                    </div>

                    <div class="rounded-xl bg-bgray-50 px-4 py-4 dark:bg-darkblack-500">
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">{{ $timeComparison['remaining_or_over_label'] }}</p>
                        <p class="mt-2 text-base font-semibold text-bgray-900 dark:text-white">{{ $timeComparison['remaining_or_over_formatted'] }}</p>
                    </div>

                    <div class="rounded-xl bg-bgray-50 px-4 py-4 dark:bg-darkblack-500">
                        <p class="text-xs font-medium uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">Progress</p>
                        <p class="mt-2 text-base font-semibold text-bgray-900 dark:text-white">{{ $timeComparison['progress_percent'] }}%</p>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                    <div class="mb-3 flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">
                        <span>Progress Bar</span>
                        <span>{{ $timeComparison['bar_percent'] }}%</span>
                    </div>

                    <div class="h-3 overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                        <div class="h-full rounded-full transition-all duration-500 {{ $timeBarClasses }}" style="width: {{ $timeComparison['bar_percent'] }}%;"></div>
                    </div>

                    <p class="mt-3 {{ $cardTextClasses }}">
                        @if (! $timeComparison['has_estimate'])
                            <span class="text-error-200">Add an estimate to start comparing planned and actual effort.</span>
                        @elseif ($timeComparison['is_over_estimate'])
                            <span class="text-error-200">Actual time is over the estimate by {{ $timeComparison['remaining_or_over_formatted'] }}.</span>
                        @else
                            <span class="text-bgray-800 dark:text-bgray-300">{{ $timeComparison['estimated_formatted'] }} / {{ $timeComparison['remaining_or_over_formatted'] }} remaining within the current estimate.</span>
                        @endif
                    </p>
                </div>
            </section>

            <section class="{{ $cardClasses }}">
                <h3 class="{{ $cardTitleClasses }}">Billable</h3>

                <div class="mt-4 flex items-center gap-3">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $is_billable ? 'bg-success-50 text-success-400 dark:bg-success-900/20 dark:text-success-200' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200' }}">
                        {{ $is_billable ? 'Billable' : 'Non-billable' }}
                    </span>
                </div>
            </section>
        </div>
    </div>

    <section class="{{ $cardClasses }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="{{ $cardTitleClasses }}">Subtasks</h3>
                <p class="mt-1 {{ $cardTextClasses }}">Direct child tasks linked to this task.</p>
            </div>

            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-100">
                {{ $subtasks->count() }} {{ \Illuminate\Support\Str::plural('subtask', $subtasks->count()) }}
            </span>
        </div>

        @if ($subtasks->isEmpty())
            <p class="mt-4 {{ $emptyTextClasses }}">No subtasks added yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-bgray-200 dark:divide-darkblack-400">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">
                            <th class="pb-3 pr-4">Task</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3 pr-4">Priority</th>
                            <th class="pb-3 pr-4">Assignee</th>
                            <th class="pb-3 pr-4">Estimated</th>
                            <th class="pb-3">Actual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                        @foreach ($subtasks as $subtask)
                            @php
                                $statusColor = $subtask->status?->color ?: '#CBD5E1';
                                $priorityConfig = config('project_constants.task_priorities.' . ($subtask->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
                            @endphp

                            <tr class="align-top">
                                <td class="py-4 pr-4">
                                    <a href="{{ route('tasks.edit', $subtask) }}" class="block transition hover:text-success-400 dark:hover:text-success-300">
                                        <x-task-name-status
                                            :name="$subtask->name"
                                            :request-type="$subtask->request_type"
                                            :request-status="$subtask->request_status"
                                            :limit="48"
                                            limit-end=".."
                                            show-priority-indicator
                                            priority-indicator="line"
                                            :priority-class="$priorityConfig['bg_class'] ?? 'bg-primary'"
                                            text-class="text-sm font-semibold text-bgray-900 dark:text-white"
                                        />
                                        <p class="mt-1 text-xs text-[#7C97C1] dark:text-bgray-300">{{ $subtask->code ?: 'TSK-' . str_pad($subtask->id, 5, '0', STR_PAD_LEFT) }}</p>
                                    </a>
                                </td>
                                <td class="py-4 pr-4">
                                    @if ($subtask->status)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColor }}"></span>
                                            {{ $subtask->status->name }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                            No status
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 pr-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white {{ $priorityConfig['bg_class'] ?? 'bg-primary' }}">
                                        {{ $priorityConfig['label'] ?? ucfirst($subtask->priority ?: 'medium') }}
                                    </span>
                                </td>
                                <td class="py-4 pr-4 text-sm font-medium text-bgray-700 dark:text-bgray-100">
                                    {{ $subtask->currentAssignee?->name ?? 'Unassigned' }}
                                </td>
                                <td class="py-4 pr-4 text-sm font-medium text-bgray-700 dark:text-bgray-100">
                                    {{ $formatDuration($subtask->estimated_time_seconds) }}
                                </td>
                                <td class="py-4 text-sm font-medium text-bgray-700 dark:text-bgray-100">
                                    {{ $formatDuration($subtask->actual_time_seconds) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
