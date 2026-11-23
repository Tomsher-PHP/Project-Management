<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ data_get($metadata, 'report_name', 'Project Report') }}</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5;">

    @php
        $reportName = data_get($metadata, 'report_name', 'Project Report');
        $reportingPeriod = data_get($metadata, 'reporting_period_label', 'All Time');
        $dateFilter = data_get($metadata, 'date_filter_label', 'Status Changed Date within Period');
        $selectedStatuses = data_get($metadata, 'selected_statuses_text', 'All');
        $generatedAt = data_get($metadata, 'generated_at_display', now()->format('d M Y, h:i A'));
        $totalProjects = (int) data_get($metadata, 'total_projects', count($projects ?? []));
        $totalRows = (int) data_get($metadata, 'total_rows', count($flatRows ?? []));
        $isEmpty = (bool) data_get($metadata, 'is_empty', empty($flatRows));

        $badgeStyle = static function ($type, $value) {
            $val = strtolower((string) $value);
            if ($type === 'status' || $type === 'timeline_status') {
                if (str_contains($val, 'complete') || str_contains($val, 'done')) {
                    return 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
                }
                if (str_contains($val, 'progress') || str_contains($val, 'active')) {
                    return 'background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe;';
                }
                if (str_contains($val, 'hold') || str_contains($val, 'pause') || str_contains($val, 'wait')) {
                    return 'background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;';
                }
                if (str_contains($val, 'cancel') || str_contains($val, 'archived')) {
                    return 'background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;';
                }
                return 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
            }

            if ($type === 'priority') {
                if ($val === 'urgent' || $val === 'critical' || $val === 'high') {
                    return 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;';
                }
                if ($val === 'medium') {
                    return 'background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;';
                }
                if ($val === 'low') {
                    return 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                }
            }

            return 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
        };
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6; padding: 25px 12px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 1100px; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0;">

                    <!-- Header Section -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 28px 32px; border-bottom: 3px solid #3b82f6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" align="left">
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #93c5fd; margin-bottom: 4px;">
                                            Automated Report
                                        </div>
                                        <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.3px;">
                                            {{ $reportName }}
                                        </h1>
                                        <p style="margin: 4px 0 0; color: #cbd5e1; font-size: 13px;">
                                            {{ config('app.name') }}
                                        </p>
                                    </td>
                                    <td valign="middle" align="right">
                                        @if (config('assets.icons.logo'))
                                            <img src="{{ url(config('assets.icons.logo')) }}" alt="{{ config('app.name') }}" height="36" style="display: block; height: 36px; width: auto; border: 0;">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Metadata Summary Grid -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; border-bottom: 1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="50%" valign="top" style="padding-right: 15px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 12px; color: #64748b; font-weight: 600; width: 130px;">
                                                    Reporting Period:
                                                </td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #0f172a; font-weight: 500;">
                                                    {{ $reportingPeriod }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 12px; color: #64748b; font-weight: 600;">
                                                    Date Filter:
                                                </td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #0f172a; font-weight: 500;">
                                                    {{ $dateFilter }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 12px; color: #64748b; font-weight: 600;">
                                                    Project Statuses:
                                                </td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #0f172a; font-weight: 500;">
                                                    {{ $selectedStatuses }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top" style="padding-left: 15px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 12px; color: #64748b; font-weight: 600; width: 120px;">
                                                    Generated On:
                                                </td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #0f172a; font-weight: 500;">
                                                    {{ $generatedAt }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 3px 0; font-size: 12px; color: #64748b; font-weight: 600;">
                                                    Total Projects:
                                                </td>
                                                <td style="padding: 3px 0; font-size: 13px; color: #0f172a; font-weight: 700;">
                                                    {{ $totalProjects }} <span style="font-size: 12px; font-weight: 400; color: #64748b;">({{ $totalRows }} total rows)</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Report Table Body -->
                    <tr>
                        <td style="padding: 28px 32px;">

                            @if ($isEmpty)
                                <!-- Empty State -->
                                <div style="text-align: center; padding: 48px 20px; background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin: 8px 0;">
                                    <div style="font-size: 15px; font-weight: 700; color: #334155;">
                                        No matching projects found
                                    </div>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 6px;">
                                        There are no projects matching the configured filters and reporting period for this scheduled run.
                                    </div>
                                </div>
                            @else
                                <!-- Table Container (Supports scroll for wide tables without clipping) -->
                                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                                        <thead>
                                            <tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                                                @foreach ($columnKeys as $key)
                                                    @php
                                                        $headerLabel = $columns[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                                    @endphp
                                                    <th style="padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #334155; white-space: nowrap;">
                                                        {{ $headerLabel }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($flatRows as $index => $row)
                                                @php
                                                    $isPrimary = (bool) ($row['is_primary'] ?? true);
                                                    $hasMultiple = (bool) ($row['has_multiple_timelines'] ?? false);
                                                    $rowBg = $isPrimary ? ($index % 2 === 0 ? '#ffffff' : '#fafafa') : '#f8fafc';
                                                    $rowBorder = !$isPrimary ? 'border-bottom: 1px solid #e2e8f0; border-left: 3px solid #94a3b8;' : 'border-bottom: 1px solid #e2e8f0;';
                                                @endphp
                                                <tr style="background-color: {{ $rowBg }}; {{ $rowBorder }}">
                                                    @foreach ($columnKeys as $key)
                                                        @php
                                                            $val = $row['values'][$key] ?? '-';
                                                        @endphp

                                                        <td style="padding: 10px 14px; vertical-align: middle; color: #334155;">
                                                            @if ($key === 'project_name')
                                                                @if ($isPrimary)
                                                                    <div style="font-weight: 700; color: #0f172a; word-break: break-word; min-width: 150px;">
                                                                        {{ $val }}
                                                                    </div>
                                                                @else
                                                                    <div style="color: #64748b; font-size: 11px; padding-left: 10px; word-break: break-word; min-width: 150px;">
                                                                        <span style="color: #94a3b8; font-weight: bold;">↳</span> {{ $val }}
                                                                    </div>
                                                                @endif
                                                            @elseif ($key === 'status')
                                                                <span style="display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: 600; border-radius: 9999px; white-space: nowrap; {{ $badgeStyle('status', $val) }}">
                                                                    {{ $val }}
                                                                </span>
                                                            @elseif ($key === 'timeline_status')
                                                                <span style="display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: 600; border-radius: 9999px; white-space: nowrap; {{ $badgeStyle('timeline_status', $val) }}">
                                                                    {{ $val }}
                                                                </span>
                                                            @elseif ($key === 'priority')
                                                                <span style="display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: 600; border-radius: 9999px; white-space: nowrap; {{ $badgeStyle('priority', $val) }}">
                                                                    {{ $val }}
                                                                </span>
                                                            @elseif ($key === 'progress' || $key === 'customer_progress')
                                                                <span style="font-weight: 600; color: #0f172a; white-space: nowrap;">
                                                                    {{ $val }}
                                                                </span>
                                                            @elseif (in_array($key, ['start_date', 'end_date', 'customer_end_date', 'estimated_hours', 'customer_estimated_hours', 'actual_hours'], true))
                                                                <span style="white-space: nowrap;">
                                                                    {{ $val }}
                                                                </span>
                                                            @else
                                                                <span style="word-break: break-word;">
                                                                    {{ $val }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #64748b;">
                                This is an automated report delivered by <strong>{{ config('app.name') }}</strong>.
                            </p>
                            <p style="margin: 4px 0 0; font-size: 11px; color: #94a3b8;">
                                To modify recipients, frequency, or filters, please visit the Project Report Settings panel.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
