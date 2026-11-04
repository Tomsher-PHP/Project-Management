
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Work Report</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#263244;">

@php
    $employeeName = data_get($report, 'employee.name', 'N/A');
    $employeeEmail = data_get($report, 'employee.email', 'N/A');

    $periodStart = data_get($report, 'period.start', 'N/A');
    $periodEnd = data_get($report, 'period.end', 'N/A');

    $workload = data_get($report, 'current_workload', []);

    // Tasks with time logged during the reporting period.
    $tasks = data_get($report, 'current_workload.tasks', []);

    // Todo tasks due today or earlier.
    $pendingTasks = data_get($report, 'current_workload.pending_tasks', []);

    // Workload counts.
    $totalTasks = (int) data_get($report, 'current_workload.total', 0);
    $completedTasks = (int) data_get($report, 'current_workload.completed', 0);
    $inProgressTasks = (int) data_get($report, 'current_workload.in_progress', 0);
    $pendingCount = (int) data_get($report, 'current_workload.pending', 0);
    $overdueTasks = (int) data_get($report, 'current_workload.overdue', 0);

    $timeLogs = data_get($report, 'time_logs.entries', []);
    $comments = data_get($report, 'comments', []);
    $developerNotes = data_get($report, 'developer_notes', []);

    $totalSeconds = (int) data_get($report, 'time_logs.total_seconds', 0);

    /* Shift information */

    $shiftStart = data_get($report, 'shift.start');
    $shiftEnd = data_get($report, 'shift.end');
    // $shiftBreak = data_get($report, 'shift.break');

    /* Attendance information */

    // Display attendance only for leave or day off.
    $rawAttendanceStatus = data_get($report, 'attendance.status');
    $attendanceStatus = null;

    if ($rawAttendanceStatus) {
        $normalizedAttendance = strtolower(trim((string) $rawAttendanceStatus));

        if (
            str_contains($normalizedAttendance, 'leave')
            || str_contains($normalizedAttendance, 'day off')
            || str_contains($normalizedAttendance, 'day_off')
        ) {
            $attendanceStatus = $rawAttendanceStatus;
        }
    }

    $formatDuration = function ($seconds) {
        $seconds = max(0, (int) $seconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    };

    $formatDate = function ($date) {
        if (!$date) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return $date;
        }
    };

    $formatTime = function ($time) {
        if (!$time) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('h:i A');
        } catch (\Throwable $e) {
            return $time;
        }
    };

    $statusColor = function ($status) {
        $status = strtolower((string) $status);

        if (str_contains($status, 'complete')) return '#15803d';
        if (str_contains($status, 'progress')) return '#2563eb';
        if (str_contains($status, 'pending')) return '#b45309';
        if (str_contains($status, 'overdue')) return '#dc2626';

        return '#475569';
    };

    $attendanceColor = function ($status) {
        $status = strtolower((string) $status);

        if (str_contains($status, 'leave')) return '#7c3aed';
        if (str_contains($status, 'holiday')) return '#2563eb';
        if (str_contains($status, 'day off') || str_contains($status, 'day_off')) return '#b45309';

        return '#15803d';
    };
@endphp

<table width="100%" cellpadding="0" cellspacing="0" border="0"
    style="background-color:#f3f4f6; padding:25px 10px;">
    <tr>
        <td align="center">

            {{-- Main Container --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                style="max-width:1100px; background-color:#ffffff; border-radius:10px; overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td style="background-color:#252b3b; padding:28px 30px;">
                        <h1 style="margin:0; color:#ffffff; font-size:23px; font-weight:700;">
                            Daily Work Report
                        </h1>

                        <p style="margin:8px 0 0; color:#cbd5e1; font-size:13px;">
                            Project Management System
                        </p>
                    </td>
                </tr>

                {{-- Employee Information --}}
                <tr>
                    <td style="padding:25px 30px 20px;">

                        <p style="margin:0 0 6px; color:#64748b; font-size:12px; text-transform:uppercase; font-weight:bold;">
                            Employee Details
                        </p>

                        <h2 style="margin:0 0 8px; color:#1e293b; font-size:20px;">
                            {{ $employeeName }}
                        </h2>

                        <p style="margin:0 0 14px; color:#64748b; font-size:13px;">
                            {{ $employeeEmail }}
                        </p>

                        <table cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="background-color:#f1f5f9; padding:9px 13px; border-radius:5px;">
                                    <span style="font-size:12px; color:#64748b;">
                                        Reporting Period
                                    </span>
                                    <br>

                                    <strong style="font-size:13px; color:#334155;">
                                        {{ $formatDate($periodStart) }} – {{ $formatDate($periodEnd) }}
                                    </strong>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- Attendance Information --}}
                @if($attendanceStatus)
                    <tr>
                        <td style="padding:0 30px 20px;">

                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                                <tr>
                                    <td style="padding:13px 15px;">

                                        <p style="margin:0 0 5px; font-size:11px; color:#64748b; text-transform:uppercase; font-weight:bold;">
                                            Attendance Information
                                        </p>

                                        <strong style="font-size:13px; color:{{ $attendanceColor($attendanceStatus) }};">
                                            {{ $attendanceStatus }}
                                        </strong>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                @endif

                {{-- Shift Information --}}
                @php
                    $workedStart = collect($timeLogs)
                        ->pluck('started_at')
                        ->filter()
                        ->map(fn ($time) => \Carbon\Carbon::parse($time))
                        ->sortBy(fn ($time) => $time->timestamp)
                        ->first();

                    $workedEnd = collect($timeLogs)
                        ->pluck('ended_at')
                        ->filter()
                        ->map(fn ($time) => \Carbon\Carbon::parse($time))
                        ->sortByDesc(fn ($time) => $time->timestamp)
                        ->first();

                    $totalWorkingHours = $formatDuration($totalSeconds);
                @endphp
                ```blade
@if($shiftStart || $shiftEnd || $workedStart || $workedEnd)
    <tr>
        <td style="padding:0 30px 25px;">

            <h3 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#1e293b;">
                Work Timing Summary
            </h3>

            {{-- Assigned Shift --}}
            <div style="font-size:13px; font-weight:bold; color:#334155; margin:0 0 8px;">
                Assigned Shift
            </div>

            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="50%" style="padding:5px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                            style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                            <tr>
                                <td style="padding:13px; text-align:center;">
                                    <div style="font-size:11px; color:#64748b;">
                                        Shift Start
                                    </div>
                                    <strong style="display:block; margin-top:6px; font-size:15px; color:#334155;">
                                        {{ $shiftStart ? $formatTime($shiftStart) : '-' }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td width="50%" style="padding:5px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                            style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                            <tr>
                                <td style="padding:13px; text-align:center;">
                                    <div style="font-size:11px; color:#64748b;">
                                        Shift End
                                    </div>
                                    <strong style="display:block; margin-top:6px; font-size:15px; color:#334155;">
                                        {{ $shiftEnd ? $formatTime($shiftEnd) : '-' }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Actual Worked Timing --}}
            <div style="font-size:13px; font-weight:bold; color:#334155; margin:16px 0 8px;">
                Actual Worked Timing
            </div>

            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="50%" style="padding:5px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                            style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                            <tr>
                                <td style="padding:13px; text-align:center;">
                                    <div style="font-size:11px; color:#64748b;">
                                        First Work Start
                                    </div>
                                    <strong style="display:block; margin-top:6px; font-size:15px; color:#334155;">
                                        {{ $workedStart ? $formatTime($workedStart) : '-' }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td width="50%" style="padding:5px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                            style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                            <tr>
                                <td style="padding:13px; text-align:center;">
                                    <div style="font-size:11px; color:#64748b;">
                                        Last Work End
                                    </div>
                                    <strong style="display:block; margin-top:6px; font-size:15px; color:#334155;">
                                        {{ $workedEnd ? $formatTime($workedEnd) : '-' }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Total Working Hours --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                style="margin-top:10px;">
                <tr>
                    <td style="padding:5px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                            style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:7px;">
                            <tr>
                                <td style="padding:15px; text-align:center;">
                                    <div style="font-size:12px; color:#475569;">
                                        Total Working Hours (Time Logged)
                                    </div>
                                    <strong style="display:block; margin-top:6px; font-size:20px; color:#1d4ed8;">
                                        {{ $totalWorkingHours }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
@endif
```

                {{-- Workload Summary --}}
                @if(array_key_exists('current_workload', $report))
                    <tr>
                        <td style="padding:5px 30px 25px;">

                            <h3 style="margin:0 0 15px; font-size:16px; color:#1e293b;">
                                Workload Overview
                            </h3>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @foreach([
                                        ['label' => 'Total Tasks', 'value' => $totalTasks, 'color' => '#334155'],
                                        ['label' => 'Completed', 'value' => $completedTasks, 'color' => '#15803d'],
                                        ['label' => 'In Progress', 'value' => $inProgressTasks, 'color' => '#2563eb'],
                                        ['label' => 'Pending', 'value' => $pendingCount, 'color' => '#b45309'],
                                        ['label' => 'Overdue', 'value' => $overdueTasks, 'color' => '#dc2626'],
                                        ['label' => 'Time Logged', 'value' => $formatDuration($totalSeconds), 'color' => '#7c3aed'],
                                    ] as $item)
                                        <td width="16.66%" valign="top" style="padding:4px;">
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                                style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                                                <tr>
                                                    <td style="padding:13px 8px; text-align:center;">

                                                        <div style="font-size:22px; font-weight:bold; color:{{ $item['color'] }};">
                                                            {{ $item['value'] }}
                                                        </div>

                                                        <div style="margin-top:5px; font-size:10px; color:#64748b;">
                                                            {{ $item['label'] }}
                                                        </div>

                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>

                        </td>
                    </tr>
                @endif

                {{-- Tasks Worked On --}}
                @if(array_key_exists('current_workload', $report))
                    <tr>
                        <td style="padding:0 30px 25px;">

                            <h3 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#1e293b;">
                                Tasks Worked On
                            </h3>

                            @if(count($tasks))
                                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                    style="border-collapse:collapse;">

                                    <tr style="background-color:#f1f5f9;">
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Task</th>
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Project</th>
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Status</th>
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Due Date</th>
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Estimated / Spent</th>
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Developer Notes</th>
                                    </tr>

                                    @foreach($tasks as $task)
                                        <tr>
                                            {{-- Task --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155;">
                                                @if(!empty($task['url']))
                                                    <a href="{{ $task['url'] }}"
                                                        style="font-weight:bold; color:#2563eb; text-decoration:none;">
                                                        {{ $task['name'] ?? 'Untitled Task' }}
                                                    </a>
                                                @else
                                                    <strong>{{ $task['name'] ?? 'Untitled Task' }}</strong>
                                                @endif

                                                @if(!empty($task['code']))
                                                    <div style="margin-top:4px; font-size:10px; color:#94a3b8;">
                                                        {{ $task['code'] }}
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Project, Milestone and Sprint --}}
                                            <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">
                                                {{ $task['project'] ?? 'N/A' }}

                                                @if(!empty($task['milestone']))
                                                    <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                        Milestone: {{ $task['milestone'] }}
                                                    </div>
                                                @endif

                                                @if(!empty($task['sprints']))
                                                    <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                        Sprint:
                                                        {{ is_array($task['sprints'])
                                                            ? implode(', ', $task['sprints'])
                                                            : $task['sprints'] }}
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Status and Completion Performance --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px;">

                                                <span style="color:{{ $statusColor($task['status'] ?? '') }}; font-weight:bold;">
                                                    {{ $task['status'] ?? 'N/A' }}
                                                </span>

                                                @if(!empty($task['completion_performance']))
                                                    @php
                                                        $performance = $task['completion_performance'];

                                                        $performanceLabels = [
                                                            'early' => '✓ Completed Early',
                                                            'on_time' => '✓ On Time',
                                                            'late' => '⚠ Completed Late',
                                                        ];

                                                        $performanceColors = [
                                                            'early' => '#15803d',
                                                            'on_time' => '#2563eb',
                                                            'late' => '#dc2626',
                                                        ];
                                                    @endphp

                                                    <div style="
                                                        margin-top:6px;
                                                        font-size:10px;
                                                        font-weight:bold;
                                                        color:{{ $performanceColors[$performance] ?? '#64748b' }};
                                                    ">
                                                        {{ $performanceLabels[$performance] ?? '' }}
                                                    </div>
                                                @endif

                                            </td>

                                            {{-- Due Date and Completion Time --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px; color:#64748b; white-space:nowrap;">

                                                <div>
                                                    <strong style="color:#334155;">Due:</strong>
                                                    {{ !empty($task['due_date'])
                                                        ? \Carbon\Carbon::parse($task['due_date'])->format('Y-m-d • H:i')
                                                        : 'N/A' }}
                                                </div>

                                                @if(!empty($task['completed_at']))
                                                    <div style="margin-top:6px;">
                                                        <strong style="color:#334155;">Completed:</strong>
                                                        {{ \Carbon\Carbon::parse($task['completed_at'])->format('Y-m-d • H:i') }}
                                                    </div>
                                                @endif

                                                @if(($task['completion_performance'] ?? null) === 'early')
                                                    @php
                                                        $secondsSaved = (int) ($task['completion_difference_seconds'] ?? 0);

                                                        $daysSaved = intdiv($secondsSaved, 86400);
                                                        $hoursSaved = intdiv($secondsSaved % 86400, 3600);
                                                        $minutesSaved = intdiv($secondsSaved % 3600, 60);

                                                        $earlyDuration = [];

                                                        if ($daysSaved > 0) {
                                                            $earlyDuration[] = $daysSaved . 'd';
                                                        }

                                                        if ($hoursSaved > 0) {
                                                            $earlyDuration[] = $hoursSaved . 'h';
                                                        }

                                                        if ($minutesSaved > 0) {
                                                            $earlyDuration[] = $minutesSaved . 'm';
                                                        }
                                                    @endphp

                                                    <div style="margin-top:6px; color:#15803d; font-weight:bold;">
                                                        ✓ Completed {{ implode(' ', $earlyDuration) }} early
                                                    </div>
                                                @endif

                                            </td>

                                            {{-- Estimated / Spent --}}
                                            <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">
                                                @php
                                                    $estimatedSeconds = (int) ($task['estimated_time_seconds'] ?? 0);
                                                    $actualSeconds = (int) ($task['actual_time_seconds'] ?? 0);

                                                    $estimatedTime = $estimatedSeconds > 0
                                                        ? formatSecondsToHMS($estimatedSeconds)
                                                        : '--';

                                                    $spentTime = formatSecondsToHMS($actualSeconds);
                                                @endphp

                                                <div>
                                                    <strong>Estimated:</strong>
                                                    {{ $estimatedTime }}
                                                </div>

                                                <div style="margin-top:3px;">
                                                    <strong>Spent:</strong>
                                                    {{ $spentTime }}
                                                </div>
                                            </td>

                                            {{-- Developer Notes --}}
                                            <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">
                                                @if(!empty($task['developer_note']))
                                                    <div style="white-space:pre-line; line-height:1.6;">
                                                        {{ $task['developer_note'] }}
                                                    </div>
                                                @else
                                                    <span style="color:#94a3b8;">No developer notes</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                </table>
                            @else
                                <p style="padding:15px; background-color:#f8fafc; color:#64748b; font-size:13px; border-radius:5px;">
                                    No tasks have time logged during this reporting period.
                                </p>
                            @endif

                        </td>
                    </tr>
                @endif

                {{-- Pending Tasks --}}
                @if(array_key_exists('current_workload', $report))
                    <tr>
                        <td style="padding:0 30px 25px;">

                            <h3 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#1e293b;">
                                Pending Tasks
                            </h3>

                            @if(count($pendingTasks))
                                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                    style="border-collapse:collapse;">

                                    <tr style="background-color:#f1f5f9;">
                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">
                                            Task
                                        </th>

                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">
                                            Project
                                        </th>

                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">
                                            Due Date
                                        </th>

                                        <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">
                                            Status
                                        </th>
                                    </tr>

                                    @foreach($pendingTasks as $task)
                                        @php
                                            $dueDate = !empty($task['due_date'])
                                                ? \Carbon\Carbon::parse($task['due_date'])
                                                : null;

                                            $reportDate = null;

                                            try {
                                                $reportDate = \Carbon\Carbon::parse($periodStart)->toDateString();
                                            } catch (\Throwable $e) {
                                                $reportDate = null;
                                            }

                                            // Due today or earlier is considered overdue.
                                            $isOverdue = $dueDate
                                                && $reportDate
                                                && $dueDate->toDateString() <= $reportDate;
                                        @endphp

                                        <tr>
                                            {{-- Task --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155;">
                                                <strong>
                                                    {{ $task['name'] ?? 'Untitled Task' }}
                                                </strong>
                                            </td>

                                            {{-- Project --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155;">
                                                {{ $task['project'] ?? 'N/A' }}
                                            </td>

                                            {{-- Due Date --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px; color:#64748b; white-space:nowrap;">
                                                {{ $dueDate
                                                    ? $dueDate->format('Y-m-d • H:i')
                                                    : 'N/A' }}

                                                @if($isOverdue)
                                                    <div style="margin-top:5px; color:#dc2626; font-size:10px; font-weight:bold;">
                                                        ⚠ Overdue
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Status --}}
                                            <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px;">
                                                <span style="color:{{ $statusColor($task['status'] ?? 'Pending') }}; font-weight:bold;">
                                                    {{ $task['status'] ?? 'Pending' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach

                                </table>
                            @else
                                <p style="padding:15px; background-color:#f8fafc; color:#64748b; font-size:13px; border-radius:5px;">
                                    No pending tasks due today or earlier.
                                </p>
                            @endif

                        </td>
                    </tr>
                @endif

                {{-- Developer Notes --}}
                {{-- Notes are displayed in the Tasks Worked On table. --}}

                {{-- Footer --}}
                <tr>
                    <td style="padding:20px 30px; background-color:#f8fafc; border-top:1px solid #e2e8f0; text-align:center;">

                        <p style="margin:0; color:#64748b; font-size:11px; line-height:1.6;">
                            This is an automatically generated report from the Project Management System.
                            <br>
                            Please do not reply to this email.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
