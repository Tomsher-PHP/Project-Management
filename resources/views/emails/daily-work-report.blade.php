<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Work Report</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#263244;">

    @php
        $reportOwnerName = data_get($report, 'report_owner.name', 'Team');
        $reportPeriodStart = data_get($report, 'period.start', 'N/A');
        $reportPeriodEnd = data_get($report, 'period.end', 'N/A');
        $teamGroups = collect(data_get($report, 'team_groups', []));
        $employeeCount = (int) data_get($report, 'employee_count', 0);

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

        $formatDateTime = function ($date) {
            if (!$date) {
                return 'N/A';
            }

            try {
                return \Carbon\Carbon::parse($date)->format('d M Y • h:i A');
            } catch (\Throwable $e) {
                return $date;
            }
        };

        $statusColor = function ($status) {
            $status = strtolower((string) $status);

            if (str_contains($status, 'complete')) {
                return '#15803d';
            }

            if (str_contains($status, 'progress')) {
                return '#2563eb';
            }

            if (str_contains($status, 'pending')) {
                return '#b45309';
            }

            if (str_contains($status, 'overdue')) {
                return '#dc2626';
            }

            return '#475569';
        };

        $attendanceColor = function ($status) {
            $status = strtolower((string) $status);

            if (str_contains($status, 'leave')) {
                return '#7c3aed';
            }

            if (str_contains($status, 'holiday')) {
                return '#2563eb';
            }

            if (str_contains($status, 'day off') || str_contains($status, 'day_off')) {
                return '#b45309';
            }

            return '#15803d';
        };
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6; padding:25px 10px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:1100px; background-color:#ffffff; border-radius:10px; overflow:hidden;">
                    {{-- Main Header --}}
                    <tr>
                        <td style="background-color:#c8c8c8; padding:28px 30px;">

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    {{-- Left Section --}}
                                    <td width="50%" valign="middle" align="left" style="padding-right:15px;">

                                        <h1 style="color:#242424; margin:0; font-size:20px; letter-spacing:0.5px;">
                                            Daily Work Report
                                        </h1>

                                        <p style="margin:8px 0 10px; color:#242424; font-size:13px; line-height:18px;">
                                            {{ config('app.name') }}
                                        </p>

                                        <img
                                            src="{{ url(config('assets.icons.logo')) }}"
                                            alt="{{ config('app.name') }}"
                                            height="40"
                                            style="display:block; height:40px; width:auto; border:0;"
                                        >

                                    </td>

                                    {{-- Right Section --}}
                                    <td width="50%" valign="middle" align="right" style="padding-left:15px;">

                                        <p style="margin:0; color:#242424; font-size:13px; line-height:20px;">
                                            Report For:
                                            <strong>{{ $reportOwnerName }}</strong>
                                        </p>

                                        <p style="margin:5px 0 0; color:#242424; font-size:12px; line-height:18px;">
                                            {{ $employeeCount }} Employees
                                        </p>

                                        <p style="margin:5px 0 0; color:#242424; font-size:12px; line-height:18px;">
                                            Reporting Period:<br>
                                            {{ $formatDate($reportPeriodStart) }}
                                            –
                                            {{ $formatDate($reportPeriodEnd) }}
                                        </p>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    @forelse ($teamGroups as $teamGroup)
                        @php
                            $teamName = data_get($teamGroup, 'team_name', 'No Team Assigned');
                            $teamLeader = data_get($teamGroup, 'leader');
                            $leaderReport = data_get($teamLeader, 'report');
                            $teamMembers = collect(data_get($teamGroup, 'members', []));

                            /*
                            |--------------------------------------------------------------------------
                            | Prepare Team-wise Employee Reports
                            |--------------------------------------------------------------------------
                            |
                            | Display the team leader first, followed by all team members.
                            | The team leader is not duplicated in the separate information row
                            | when their individual report is available.
                            |
                            */

                            $teamEmployeeReports = collect();

                            if (!empty($leaderReport)) {
                                $teamEmployeeReports->push([
                                    'report' => $leaderReport,
                                    'team_position' => 'Team Lead',
                                ]);
                            }

                            foreach ($teamMembers as $memberReport) {
                                $teamEmployeeReports->push([
                                    'report' => $memberReport,
                                    'team_position' => 'Team Member',
                                ]);
                            }

                            $teamEmployeeCount = (int) data_get($teamGroup, 'employee_count', $teamEmployeeReports->count());

                            $teamLeaderName = data_get($teamLeader, 'name', 'N/A');
                            $teamLeaderEmail = data_get($teamLeader, 'email', '');
                            $teamLeaderRole = data_get($teamLeader, 'role', 'No Role Assigned');

                            if (is_array($teamLeaderRole)) {
                                $teamLeaderRole = implode(', ', $teamLeaderRole);
                            }

                            $teamLeaderRole = $teamLeaderRole ?: 'No Role Assigned';
                        @endphp

                        {{-- Team Heading --}}
                        <tr>
                            <td style="padding:20px 30px 12px; background-color:#0058a2; border-bottom:1px solid #e2e8f0;">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td align="left">
                                            <h2 style="margin:0; font-size:16px; color:#fff;">
                                                {{ $teamName }}
                                            </h2>
                                        </td>
                                        <td align="right">
                                            <span style="font-size:11px; color:#64748b;">
                                                {{ $teamEmployeeCount }}
                                                {{ $teamEmployeeCount === 1 ? 'Employee' : 'Employees' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{-- Team Leader Information --}}
                        @if (!empty($teamLeader) && empty($leaderReport))
                            {{-- Show leader details only when their individual report is not included --}}
                            <tr>
                                <td style="padding:14px 30px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0;">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td>
                                                <div style="font-size:14px; font-weight:bold; color:#1e293b;">
                                                    {{ $teamLeaderName }}
                                                    <span style="display:inline-block; margin-left:7px; padding:3px 8px; background-color:#dbeafe; color:#1d4ed8; border-radius:4px; font-size:10px;">
                                                        TEAM LEAD
                                                    </span>
                                                </div>
                                                <div style="margin-top:5px; font-size:11px; color:#64748b;">
                                                    {{ $teamLeaderEmail }}
                                                </div>
                                                <div style="margin-top:5px; font-size:11px; color:#475569;">
                                                    <strong>Role:</strong> {{ $teamLeaderRole }}
                                                </div>
                                                <div style="margin-top:8px; font-size:11px; color:#64748b;">
                                                    The team leader's individual work report is not included in this reporting scope.
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        @foreach ($teamEmployeeReports as $teamEmployee)
                            @php
                                $employeeReport = $teamEmployee['report'];
                                $teamPosition = $teamEmployee['team_position'];
                                $employee = data_get($employeeReport, 'employee', []);
                                $employeeName = data_get($employee, 'name', 'N/A');
                                $employeeEmail = data_get($employee, 'email', 'N/A');

                                // Role string is populated by generateConsolidated().
                                $employeeRoles = data_get($employee, 'roles', data_get($employee, 'role', 'No Role Assigned'));

                                if (is_array($employeeRoles)) {
                                    $employeeRoles = implode(', ', $employeeRoles);
                                }

                                $employeeRoles = $employeeRoles ?: 'No Role Assigned';
                                $periodStart = data_get($employeeReport, 'period.start', $reportPeriodStart);
                                $periodEnd = data_get($employeeReport, 'period.end', $reportPeriodEnd);
                                $tasks = data_get($employeeReport, 'current_workload.tasks', []);
                                $pendingTasks = data_get($employeeReport, 'current_workload.pending_tasks', []);

                                $totalTasks = (int) data_get($employeeReport, 'current_workload.total', 0);
                                $completedTasks = (int) data_get($employeeReport, 'current_workload.completed', 0);
                                $inProgressTasks = (int) data_get($employeeReport, 'current_workload.in_progress', 0);
                                $pendingCount = (int) data_get($employeeReport, 'current_workload.pending', 0);
                                $upcomingCount = (int) data_get($employeeReport, 'current_workload.upcoming', 0);
                                $overdueTasks = (int) data_get($employeeReport, 'current_workload.overdue', 0);
                                $timeLogs = collect(data_get($employeeReport, 'time_logs.entries', []));
                                $totalSeconds = (int) data_get($employeeReport, 'time_logs.total_seconds', 0);

                                // Shift information.
                                $shiftStart = data_get($employeeReport, 'shift.start');
                                $shiftEnd = data_get($employeeReport, 'shift.end');

                                // Actual worked timing.
                                $workedStart = $timeLogs
                                    ->pluck('started_at')
                                    ->filter()
                                    ->map(function ($time) {
                                        try {
                                            return \Carbon\Carbon::parse($time);
                                        } catch (\Throwable $e) {
                                            return null;
                                        }
                                    })
                                    ->filter()
                                    ->sortBy(fn($time) => $time->timestamp)
                                    ->first();

                                $workedEnd = $timeLogs
                                    ->pluck('ended_at')
                                    ->filter()
                                    ->map(function ($time) {
                                        try {
                                            return \Carbon\Carbon::parse($time);
                                        } catch (\Throwable $e) {
                                            return null;
                                        }
                                    })
                                    ->filter()
                                    ->sortByDesc(fn($time) => $time->timestamp)
                                    ->first();

                                $totalWorkingHours = $formatDuration($totalSeconds);

                                // Attendance: display only leave or day off.
                                $rawAttendanceStatus = data_get($employeeReport, 'attendance.status');
                                $attendanceStatus = null;

                                if ($rawAttendanceStatus) {
                                    $normalizedAttendance = strtolower(trim((string) $rawAttendanceStatus));

                                    if (str_contains($normalizedAttendance, 'leave') || str_contains($normalizedAttendance, 'day off') || str_contains($normalizedAttendance, 'day_off')) {
                                        $attendanceStatus = $rawAttendanceStatus;
                                    }
                                }

                                $taskCountUrls = data_get($employeeReport, 'task_count_urls', []);
                                $taskCountItems = [
                                    [
                                        'key' => 'total',
                                        'label' => 'Total Tasks',
                                        'value' => $totalTasks,
                                        'color' => '#334155',
                                    ],
                                    [
                                        'key' => 'completed',
                                        'label' => 'Completed',
                                        'value' => $completedTasks,
                                        'color' => '#15803d',
                                    ],
                                    [
                                        'key' => 'in_progress',
                                        'label' => 'In Progress',
                                        'value' => $inProgressTasks,
                                        'color' => '#2563eb',
                                    ],
                                    [
                                        'key' => 'pending',
                                        'label' => 'Pending',
                                        'value' => $pendingCount,
                                        'color' => '#b45309',
                                    ],
                                    [
                                        'key' => 'upcoming',
                                        'label' => 'Upcoming',
                                        'value' => $upcomingCount,
                                        'color' => '#b45309',
                                    ],
                                    [
                                        'key' => 'overdue',
                                        'label' => 'Overdue',
                                        'value' => $overdueTasks,
                                        'color' => '#dc2626',
                                    ],
                                    [
                                        'key' => 'time_logged',
                                        'label' => 'Time Logged',
                                        'value' => $totalWorkingHours,
                                        'color' => '#7c3aed',
                                    ],
                                ];
                            @endphp

                            {{-- Employee Header --}}
                            <tr>
                                <td style="padding:14px 30px; background-color:#ffffff; border-bottom:1px solid #e2e8f0;">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="48%" valign="middle">
                                                <div style="font-size:15px; font-weight:700; color:#1e293b;">
                                                    {{ $employeeName }}
                                                </div>
                                                <div style="margin-top:3px; font-size:11px; color:#64748b;">
                                                    {{ $employeeEmail }}
                                                </div>
                                                <div style="margin-top:5px; font-size:11px; color:#475569;">
                                                    <strong>Role:</strong>
                                                    {{ $employeeRoles }}
                                                </div>
                                                <div style="margin-top:5px;">
                                                    <span style="display:inline-block; padding:3px 7px; background-color:{{ $teamPosition === 'Team Lead' ? '#dbeafe' : '#f1f5f9' }}; color:{{ $teamPosition === 'Team Lead' ? '#1d4ed8' : '#475569' }}; border-radius:4px; font-size:10px; font-weight:bold;">
                                                        {{ strtoupper($teamPosition) }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td width="52%" valign="middle" align="right">
                                                <div style="margin-top:5px; font-size:11px; color:#64748b;">
                                                    <strong>Shift:</strong>
                                                    {{ $shiftStart ? $formatTime($shiftStart) : '-' }}
                                                    –
                                                    {{ $shiftEnd ? $formatTime($shiftEnd) : '-' }}
                                                </div>

                                                <div style="margin-top:4px; font-size:11px; color:#64748b;">
                                                    <strong>Worked:</strong>
                                                    {{ $workedStart ? $formatTime($workedStart) : '-' }}
                                                    –
                                                    {{ $workedEnd ? $formatTime($workedEnd) : '-' }}
                                                </div>

                                                <div style="margin-top:4px; font-size:11px; color:#64748b;">
                                                    <strong>Time Logged:</strong>
                                                    <span style="font-weight:bold; color:#1d4ed8;">
                                                        {{ $totalWorkingHours }}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            {{-- Attendance Information --}}
                            @if ($attendanceStatus)
                                <tr>
                                    <td style="padding:10px 30px 5px;">
                                        <div style="padding:9px 12px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:5px; font-size:11px;">
                                            <strong style="color:#64748b;">
                                                Attendance:
                                            </strong>
                                            <span style="font-weight:bold; color:{{ $attendanceColor($attendanceStatus) }};">
                                                {{ $attendanceStatus }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            {{-- Workload Summary --}}
                            @if (array_key_exists('current_workload', $employeeReport))
                                <tr>
                                    <td style="padding:18px 30px 20px;">
                                        <h5 style="margin:0 0 12px;color:#1e293b;">
                                            Workload Overview
                                        </h5>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                @foreach ($taskCountItems as $item)
                                                    @php
                                                        $countUrl = data_get($taskCountUrls, $item['key']);
                                                        $isClickable = !empty($countUrl) && $item['key'] !== 'time_logged';
                                                    @endphp
                                                    <td width="14.285%" valign="top" style="padding:3px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
                                                            <tr>
                                                                <td style="padding:5px; text-align:center;">

                                                                    @if ($isClickable)
                                                                        <a href="{{ $countUrl }}" target="_blank" style="display:block; text-decoration:none; color:inherit;">
                                                                    @endif

                                                                    <div style="font-size:21px; font-weight:bold; color:{{ $item['color'] }};">
                                                                        {{ $item['value'] }}
                                                                    </div>

                                                                    <div style="margin-top:4px; font-size:10px; color:#64748b;">
                                                                        {{ $item['label'] }}
                                                                    </div>

                                                                    @if ($isClickable)
                                                                        </a>
                                                                    @endif
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
                            @if (array_key_exists('current_workload', $employeeReport))
                                <tr>
                                    <td style="padding:0 30px 25px;">
                                        <h5 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0;color:#1e293b;">
                                            Tasks Worked On
                                        </h5>
                                        @if (count($tasks))
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                                <tr style="background-color:#f1f5f9;">
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Task</th>
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Project</th>
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Status</th>
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Due Date</th>
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Estimated / Spent</th>
                                                    <th align="left" style="padding:11px 9px; font-size:11px; color:#475569;">Developer Notes</th>
                                                </tr>

                                                @foreach ($tasks as $task)
                                                    <tr>
                                                        {{-- Task --}}
                                                        <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155;">
                                                            @if (!empty($task['url']))
                                                                <a href="{{ $task['url'] }}" target="_blank" style="font-weight:bold; color:#2563eb; text-decoration:none;">
                                                                    {{ $task['name'] ?? 'Untitled Task' }}
                                                                </a>
                                                            @else
                                                                <strong>
                                                                    {{ $task['name'] ?? 'Untitled Task' }}
                                                                </strong>
                                                            @endif

                                                            @if (!empty($task['code']))
                                                                <div style="margin-top:4px; font-size:10px; color:#94a3b8;">
                                                                    {{ $task['code'] }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Project, Milestone and Sprint --}}
                                                        <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">

                                                            @if (!empty($task['project_url']))
                                                                <a href="{{ $task['project_url'] }}"
                                                                target="_blank"
                                                                style="font-weight:600; color:#2563eb; text-decoration:none;">
                                                                    {{ $task['project'] ?? 'N/A' }}
                                                                </a>
                                                            @else
                                                                {{ $task['project'] ?? 'N/A' }}
                                                            @endif

                                                            @if (!empty($task['milestone']))
                                                                <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                                    Milestone: {{ $task['milestone'] }}
                                                                </div>
                                                            @endif

                                                            @if (!empty($task['sprints']))
                                                                <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                                    Sprint:
                                                                    {{ is_array($task['sprints']) ? implode(', ', $task['sprints']) : $task['sprints'] }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Status and Completion Performance --}}
                                                        <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px;">
                                                            <span style="color:{{ $statusColor($task['status'] ?? '') }}; font-weight:bold;">
                                                                {{ $task['status'] ?? 'N/A' }}
                                                            </span>
                                                            @if (!empty($task['completion_performance']))
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

                                                                <div style="margin-top:6px; font-size:10px; font-weight:bold; color:{{ $performanceColors[$performance] ?? '#64748b' }};">
                                                                    {{ $performanceLabels[$performance] ?? '' }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Due Date and Completion Time --}}
                                                        <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px; color:#64748b; white-space:nowrap;">
                                                            <div>
                                                                <strong style="color:#334155;">Due:</strong>
                                                                {{ !empty($task['due_date']) ? $formatDateTime($task['due_date']) : 'N/A' }}
                                                            </div>

                                                            @if (!empty($task['completed_at']))
                                                                <div style="margin-top:6px;">
                                                                    <strong style="color:#334155;">Completed:</strong>
                                                                    {{ $formatDateTime($task['completed_at']) }}
                                                                </div>
                                                            @endif

                                                            @if (($task['completion_performance'] ?? null) === 'early')
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

                                                                @if (!empty($earlyDuration))
                                                                    <div style="margin-top:6px; color:#15803d; font-weight:bold;">
                                                                        ✓ Completed {{ implode(' ', $earlyDuration) }} early
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </td>

                                                        {{-- Estimated / Spent --}}
                                                        <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">
                                                            @php
                                                                $estimatedSeconds = (int) ($task['estimated_time_seconds'] ?? 0);
                                                                $actualSeconds = (int) ($task['actual_time_seconds'] ?? 0);

                                                                $estimatedTime = $estimatedSeconds > 0 ? formatSecondsToHMS($estimatedSeconds) : '--';

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
                                                            @if (!empty($task['developer_note']))
                                                                <div style="white-space:pre-line; line-height:1.6;">
                                                                    {{ $task['developer_note'] }}
                                                                </div>
                                                            @else
                                                                <span style="color:#94a3b8;">
                                                                    No developer notes
                                                                </span>
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
                            @if (array_key_exists('current_workload', $employeeReport))
                                <tr>
                                    <td style="padding:0 30px 25px;">
                                        <h5 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0;color:#1e293b;">
                                            Pending Tasks
                                        </h5>
                                        @if (count($pendingTasks))
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
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

                                                @foreach ($pendingTasks as $task)
                                                    @php
                                                        $dueDate = null;

                                                        if (!empty($task['due_date'])) {
                                                            try {
                                                                $dueDate = \Carbon\Carbon::parse($task['due_date']);
                                                            } catch (\Throwable $e) {
                                                                $dueDate = null;
                                                            }
                                                        }

                                                        $reportDate = null;

                                                        try {
                                                            $reportDate = \Carbon\Carbon::parse($periodStart)->toDateString();
                                                        } catch (\Throwable $e) {
                                                            $reportDate = null;
                                                        }

                                                        $isOverdue = $dueDate && $reportDate && $dueDate->toDateString() <= $reportDate;
                                                    @endphp

                                                    <tr>
                                                        {{-- Task --}}
                                                        <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155;">
                                                            @if (!empty($task['url']))
                                                                <a href="{{ $task['url'] }}" target="_blank" style="font-weight:bold; color:#2563eb; text-decoration:none;">
                                                                    {{ $task['name'] ?? 'Untitled Task' }}
                                                                </a>
                                                            @else
                                                                <strong>{{ $task['name'] ?? 'Untitled Task' }}</strong>
                                                            @endif

                                                            @if (!empty($task['code']))
                                                                <div style="margin-top:4px; font-size:10px; color:#94a3b8;">
                                                                    {{ $task['code'] }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Project, Milestone and Sprint --}}
                                                        <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#334155;">

                                                            @if (!empty($task['project_url']))
                                                                <a href="{{ $task['project_url'] }}"
                                                                target="_blank"
                                                                style="font-weight:600; color:#2563eb; text-decoration:none;">
                                                                    {{ $task['project'] ?? 'N/A' }}
                                                                </a>
                                                            @else
                                                                {{ $task['project'] ?? 'N/A' }}
                                                            @endif

                                                            @if (!empty($task['milestone']))
                                                                <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                                    Milestone: {{ $task['milestone'] }}
                                                                </div>
                                                            @endif

                                                            @if (!empty($task['sprints']))
                                                                <div style="font-size:11px; color:#6b7280; margin-top:3px;">
                                                                    Sprint:
                                                                    {{ is_array($task['sprints']) ? implode(', ', $task['sprints']) : $task['sprints'] }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Due Date --}}
                                                        <td style="padding:12px 9px; border-bottom:1px solid #e2e8f0; font-size:11px; color:#64748b; white-space:nowrap;">
                                                            {{ $dueDate ? $dueDate->format('Y-m-d • H:i') : 'N/A' }}
                                                            @if ($isOverdue)
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

                            {{-- Employee Separator --}}
                            <tr>
                                <td style="padding:0 30px;">
                                    <div style="border-bottom:2px solid #e2e8f0; margin:5px 0 15px;"></div>
                                </td>
                            </tr>
                        @endforeach

                    @empty
                        <tr>
                            <td style="padding:30px; text-align:center;">
                                <p style="margin:0; color:#64748b; font-size:14px;">
                                    No employee reports are available for this reporting period.
                                </p>
                            </td>
                        </tr>
                    @endforelse

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
