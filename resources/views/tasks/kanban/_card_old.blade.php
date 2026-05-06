@php
    $authUser = auth()->user();
    $priorities = $priorities ?? config('project_constants.task_priorities', []);

    $canOpenTask = $task->project && $authUser && (
        $authUser->is_super_admin ||
        $authUser->can('task.view_all_tasks') ||
        $authUser->can('task.view') ||
        (int) ($task->current_assignee_id ?? 0) === (int) $authUser->id
    );

    $isCompleted = $status->is_completed ?? false;

    $priority = $task->priority ?? 'medium';
    $priorityKey = strtolower((string) $priority);
    $priorityConfig = $priorities[$priority] ?? [];
    $priorityLabel = $priorityConfig['label'] ?? ucfirst($priority);
    $priorityTone = match ($priorityKey) {
        'high', 'urgent' => 'bg-red-50 text-red-500',
        'medium' => 'bg-orange-50 text-orange-500',
        'low' => 'bg-emerald-50 text-emerald-500',
        default => 'bg-[#eef5ff] text-[#0866ff]',
    };

    $estimatedTime = $task->estimatedTimeFormatted ?? '-';
    $actualTime = $task->actualTimeFormatted ?? '00:00:00';

    $stringLimit = fn(?string $value, int $length = 25, string $end = '...'): string =>
        \Illuminate\Support\Str::limit($value ?? '', $length, $end);

    $taskName = $task->name ?? ($task->code ?? 'Untitled task');
    $taskCode = $task->code ?: 'TSK-' . str_pad((string) $task->id, 5, '0', STR_PAD_LEFT);
    $taskDescription = trim(strip_tags((string) ($task->description ?? '')));
    $dueDateLabel = $task->due_date_time ? $task->due_date_time->format('M d, H:i') : null;

    $childTasksCount = (int) ($task->child_tasks_count ?? 0);
    $completedChildTasksCount = (int) ($task->completed_child_tasks_count ?? 0);
    $estimatedSeconds = max((int) ($task->estimated_time_seconds ?? 0), 0);
    $actualSeconds = max((int) ($task->actual_time_seconds ?? 0), 0);

    if ($isCompleted) {
        $progressPercentage = 100;
        $progressLabel = 'Completed';
        $progressMeta = 'Done';
    } elseif ($childTasksCount > 0) {
        $progressPercentage = (int) round(($completedChildTasksCount / max($childTasksCount, 1)) * 100);
        $progressLabel = 'Subtasks';
        $progressMeta = $completedChildTasksCount . '/' . $childTasksCount;
    } elseif ($estimatedSeconds > 0) {
        $progressPercentage = (int) min(100, round(($actualSeconds / $estimatedSeconds) * 100));
        $progressLabel = 'Time progress';
        $progressMeta = $progressPercentage . '%';
    } else {
        $progressPercentage = 0;
        $progressLabel = 'Progress';
        $progressMeta = '0%';
    }

    $progressPercentage = max(0, min(100, $progressPercentage));
    $showProgress = ! $isCompleted && ($childTasksCount > 0 || $estimatedSeconds > 0 || $progressPercentage > 0);
    $progressColor = $status->color ?? '#0866ff';
    $actionLabel = $isCompleted ? 'Done' : ($actualSeconds > 0 ? 'Continue' : 'Start');
@endphp

<div class="card group overflow-hidden rounded-[11px] border border-[#edf1f7] bg-white shadow-[0_8px_20px_rgba(18,25,95,0.045)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#dfe6f1] hover:shadow-[0_12px_26px_rgba(18,25,95,0.08)]"
    data-task-id="{{ $task->id }}">

    {{-- MODAL OPEN AREA ONLY --}}
    <div class="space-y-2.5 p-3.5 {{ $canOpenTask ? 'cursor-pointer' : 'cursor-default' }}"
        @if ($canOpenTask)
            data-project-task-detail-open
            data-project-task-detail-url="{{ route('projects.tasks.modal', [$task->project, $task]) }}"
            data-project-task-group-key=""
            title="Open task"
        @endif>

        <div class="flex items-center justify-between gap-3">
            <span class="inline-flex min-w-0 items-center rounded-md bg-[#eef5ff] px-2 py-0.5 text-[11px] font-extrabold leading-5 text-[#0866ff]">
                <span class="truncate">{{ $taskCode }}</span>
            </span>

            <span class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-[11px] font-extrabold uppercase leading-5 {{ $priorityTone }}">
                {{ $priorityLabel }}
            </span>
        </div>

        @if ($dueDateLabel)
            <div class="flex items-center gap-2 text-[12px] font-bold text-[#6677a7]">
                <svg class="h-3.5 w-3.5 text-[#6677a7]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" />
                </svg>
                <span>{{ $dueDateLabel }}</span>
            </div>
        @endif

        <div class="space-y-1">
            <h5 class="leading-snug">
                <x-task-name-status
                    :name="$taskName"
                    :request-type="$task->request_type"
                    :request-status="$task->request_status"
                    :limit="31"
                    text-class="text-[13px] font-extrabold leading-snug transition {{ $isCompleted ? 'line-through text-gray-400' : 'text-[#111653] group-hover:text-[#0866ff]' }}"
                    name-class="block"
                    class="max-w-full"
                />
            </h5>

            <p class="line-clamp-2 min-h-[2rem] text-[12px] font-bold leading-relaxed text-[#6677a7]">
                {{ filled($taskDescription) ? $stringLimit($taskDescription, 74, '...') : 'No description added.' }}
            </p>
        </div>

        @if ($showProgress)
            <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[12px] font-bold text-[#6677a7]">{{ $progressLabel === 'Time progress' ? 'Progress' : $progressLabel }}</span>
                    <span class="text-[12px] font-extrabold text-[#111653]">{{ $progressMeta }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-[#e8e8e8]" aria-label="{{ $progressLabel }} {{ $progressPercentage }}%">
                    <div class="h-full rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%; background-color: {{ $progressColor }};"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- START TASK AREA - NO MODAL --}}
    <div class="flex items-center justify-between gap-3 px-3.5 pb-3.5 pt-1">
        <div class="flex min-w-0 items-center gap-2" title="{{ $task->currentAssignee ? 'Assignee: ' . $task->currentAssignee->name : 'Not Assigned' }}">
            @if ($task->currentAssignee)
                @if (!empty($task->currentAssignee->profileImageUrl))
                    <img src="{{ $task->currentAssignee->profileImageUrl }}"
                        alt="{{ $task->currentAssignee->name }}"
                        class="h-8 w-8 rounded-full object-cover shadow-[0_4px_12px_rgba(18,25,95,0.12)]" />
                @else
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#eef5ff] text-[11px] font-extrabold uppercase text-[#0866ff]">
                        {{ strtoupper(substr($task->currentAssignee->name, 0, 1)) }}
                    </div>
                @endif

                <span class="truncate text-[12px] font-bold text-[#6677a7]">
                    {{ $stringLimit($task->currentAssignee->name, 18, '...') }}
                </span>
            @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#eef5ff] text-[10px] font-extrabold uppercase text-[#0866ff]">
                    NA
                </div>
                <span class="text-[12px] font-bold text-[#6677a7]">Unassigned</span>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($isCompleted)
                <span class="inline-flex items-center gap-2 rounded-lg px-1.5 py-1 text-[12px] font-extrabold text-emerald-600">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                    </svg>
                    <span>{{ $actionLabel }}</span>
                </span>
            @else
                <button type="button"
                    class="start-task-btn inline-flex items-center gap-2 rounded-lg px-1.5 py-1 text-[12px] font-extrabold text-[#111653] transition hover:bg-[#eef5ff]"
                    data-task-id="{{ $task->id }}"
                    data-task-name="{{ e($taskName) }}">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.6 5.2A1 1 0 0 0 7 6v12a1 1 0 0 0 1.6.8l8-6a1 1 0 0 0 0-1.6l-8-6Z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $actionLabel }}</span>
                </button>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    if (window.__taskTimerFinalInit) return;
    window.__taskTimerFinalInit = true;

    let activeTask = null;
    let seconds = 0;
    let running = false;
    let timerInterval = null;

    const old1 = document.getElementById('taskTimerBox');
    const old2 = document.getElementById('runningTaskTimerBox');
    if (old1) old1.remove();
    if (old2) old2.remove();

    const pauseIcon = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <rect x="6" y="5" width="4" height="14" rx="1"></rect>
            <rect x="14" y="5" width="4" height="14" rx="1"></rect>
        </svg>
    `;

    const playIcon = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 5v14l11-7z"></path>
        </svg>
    `;

    function createTimerBox() {
        if (document.getElementById('runningTaskTimerBox')) return;

        document.body.insertAdjacentHTML('beforeend', `
            <div id="runningTaskTimerBox"
                style="
                    position: fixed;
                    top: 18px;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 99999;
                    display: none;
                    align-items: center;
                    gap: 18px;
                    background: #ffffff;
                    border: 1px solid #edf1f7;
                    border-radius: 14px;
                    padding: 12px 14px 12px 18px;
                    min-width: 520px;
                    box-shadow: 0 8px 24px rgba(18, 25, 95, 0.08);
                ">

                <span style="
                    width: 14px;
                    height: 14px;
                    flex-shrink: 0;
                    border-radius: 999px;
                    background: #0866ff;
                "></span>

                <div style="min-width: 0; flex: 1;">
                    <p style="
                        margin: 0;
                        font-size: 12px;
                        line-height: 1;
                        font-weight: 800;
                        color: #0866ff;
                    ">
                        WORKING ACTIVITY
                    </p>

                    <p id="runningTaskName"
                        style="
                            max-width: 220px;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                            margin: 8px 0 0;
                            font-size: 15px;
                            line-height: 1;
                            font-weight: 800;
                            color: #111653;
                        ">
                    </p>
                </div>

                <p id="runningTaskTime"
                    style="
                        margin: 0;
                        padding: 0 18px;
                        font-size: 16px;
                        line-height: 1;
                        font-weight: 800;
                        color: #111653;
                    ">
                    00:00:00
                </p>

                <button type="button" id="runningTaskToggle"
                    style="
                        width: 40px;
                        height: 40px;
                        border-radius: 10px;
                        border: 0;
                        background: #f1f3f7;
                        color: #111653;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                    ">
                    ${pauseIcon}
                </button>

                <span style="
                    width: 40px;
                    height: 40px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 10px;
                    background: #fff1f1;
                ">
                    <span style="
                        width: 16px;
                        height: 16px;
                        border-radius: 3px;
                        background: #ff1414;
                    "></span>
                </span>
            </div>
        `);
    }

    function formatTime(totalSeconds) {
        const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function renderTimer() {
        createTimerBox();

        const box = document.getElementById('runningTaskTimerBox');
        const name = document.getElementById('runningTaskName');
        const time = document.getElementById('runningTaskTime');
        const toggle = document.getElementById('runningTaskToggle');

        if (!activeTask) return;

        box.style.display = 'flex';

        name.textContent = activeTask.name;
        time.textContent = formatTime(seconds);

        toggle.innerHTML = running ? pauseIcon : playIcon;
        toggle.style.background = running ? '#f1f3f7' : '#ecfdf5';
        toggle.style.color = running ? '#111653' : '#16a34a';
    }

    function startInterval() {
        clearInterval(timerInterval);

        timerInterval = setInterval(function () {
            if (!running || !activeTask) return;

            seconds++;
            renderTimer();
        }, 1000);
    }

    document.addEventListener('click', function (event) {
        const startBtn = event.target.closest('.start-task-btn');

        if (startBtn) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            activeTask = {
                id: startBtn.dataset.taskId,
                name: startBtn.dataset.taskName || 'No task'
            };

            seconds = 0;
            running = true;

            renderTimer();
            startInterval();

            return false;
        }

        const toggleBtn = event.target.closest('#runningTaskToggle');

        if (toggleBtn) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            if (!activeTask) return false;

            running = !running;
            renderTimer();

            return false;
        }
    }, true);
})();
</script>
