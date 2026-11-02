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
    $tasks = data_get($report, 'current_workload.tasks', []);
    $timeLogs = data_get($report, 'time_logs.entries', []);
    $comments = data_get($report, 'comments', []);
    $developerNotes = data_get($report, 'developer_notes', []);

    $totalSeconds = (int) data_get($report, 'time_logs.total_seconds', 0);

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

    $statusColor = function ($status) {
        $status = strtolower((string) $status);

        if (str_contains($status, 'complete')) return '#15803d';
        if (str_contains($status, 'progress')) return '#2563eb';
        if (str_contains($status, 'pending')) return '#b45309';
        if (str_contains($status, 'overdue')) return '#dc2626';

        return '#475569';
    };
@endphp

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6; padding:25px 10px;">
    <tr>
        <td align="center">

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:850px; background-color:#ffffff; border-radius:10px; overflow:hidden;">

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
                                    <span style="font-size:12px; color:#64748b;">Reporting Period</span><br>
                                    <strong style="font-size:13px; color:#334155;">
                                        {{ $formatDate($periodStart) }} – {{ $formatDate($periodEnd) }}
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

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
                                        ['label' => 'Total Tasks', 'value' => data_get($workload, 'total', 0), 'color' => '#334155'],
                                        ['label' => 'Completed', 'value' => data_get($workload, 'completed', 0), 'color' => '#15803d'],
                                        ['label' => 'In Progress', 'value' => data_get($workload, 'in_progress', 0), 'color' => '#2563eb'],
                                    ] as $item)
                                        <td width="33.33%" style="padding:5px;">
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                                                <tr>
                                                    <td style="padding:15px 12px;">
                                                        <div style="font-size:25px; font-weight:bold; color:{{ $item['color'] }};">
                                                            {{ $item['value'] }}
                                                        </div>
                                                        <div style="margin-top:5px; font-size:11px; color:#64748b;">
                                                            {{ $item['label'] }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach([
                                        ['label' => 'Pending', 'value' => data_get($workload, 'pending', 0), 'color' => '#b45309'],
                                        ['label' => 'Overdue', 'value' => data_get($workload, 'overdue', 0), 'color' => '#dc2626'],
                                        ['label' => 'Time Logged', 'value' => $formatDuration($totalSeconds), 'color' => '#7c3aed'],
                                    ] as $item)
                                        <td width="33.33%" style="padding:5px;">
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;">
                                                <tr>
                                                    <td style="padding:15px 12px;">
                                                        <div style="font-size:23px; font-weight:bold; color:{{ $item['color'] }};">
                                                            {{ $item['value'] }}
                                                        </div>
                                                        <div style="margin-top:5px; font-size:11px; color:#64748b;">
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

                {{-- Current Tasks --}}
                @if(array_key_exists('current_workload', $report))
                    <tr>
                        <td style="padding:0 30px 25px;">
                            <h3 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#1e293b;">
                                Current Tasks
                            </h3>

                            @if(count($tasks))
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                    <tr style="background-color:#f1f5f9;">
                                        <th align="left" style="padding:11px 10px; font-size:11px; color:#475569;">Task</th>
                                        <th align="left" style="padding:11px 10px; font-size:11px; color:#475569;">Project</th>
                                        <th align="left" style="padding:11px 10px; font-size:11px; color:#475569;">Status</th>
                                        <th align="left" style="padding:11px 10px; font-size:11px; color:#475569;">Due Date</th>
                                    </tr>

                                    @foreach($tasks as $task)
                                        <tr>
                                            <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#334155; font-weight:bold;">
                                                {{ $task['name'] ?? 'Untitled Task' }} ({{ $task['code'] ?? '' }})
                                            </td>
                                            <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#64748b;">
                                                {{ $task['project'] ?? 'N/A' }}
                                            </td>
                                            <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; font-size:11px;">
                                                <span style="color:{{ $statusColor($task['status'] ?? '') }}; font-weight:bold;">
                                                    {{ $task['status'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#64748b;">
                                                {{ $formatDate($task['due_date'] ?? null) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p style="padding:15px; background-color:#f8fafc; color:#64748b; font-size:13px; border-radius:5px;">
                                    No current tasks assigned.
                                </p>
                            @endif
                        </td>
                    </tr>
                @endif






                {{-- Developer Notes --}}
                @if(array_key_exists('developer_notes', $report))
                    <tr>
                        <td style="padding:0 30px 30px;">
                            <h3 style="margin:0 0 14px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#1e293b;">
                                Developer Notes
                            </h3>

                            @forelse($developerNotes as $note)
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px; background-color:#f8fafc;">
                                    <tr>
                                        <td style="padding:13px 15px; border-left:3px solid #7c3aed;">
                                            <strong style="font-size:12px; color:#334155;">
                                                {{ $note['task_name'] ?? 'Task' }}
                                            </strong>
                                            <p style="margin:7px 0 0; font-size:12px; line-height:1.6; color:#475569; white-space:pre-line;">
                                                {{ $note['note'] ?? '' }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @empty
                                <p style="padding:15px; background-color:#f8fafc; color:#64748b; font-size:13px;">
                                    No developer notes created or updated during this period.
                                </p>
                            @endforelse
                        </td>
                    </tr>
                @endif

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
