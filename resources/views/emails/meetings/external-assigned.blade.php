<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Meeting Invitation: {{ $meeting->title }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background:#0CAF60; padding:20px 30px;">
                            <h1 style="color:#ffffff; margin:0; font-size:20px; font-weight:bold; letter-spacing:0.5px;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:30px; color:#333333;">

                            <h2 style="margin-top:0; font-size:18px; color:#111827;">
                                Meeting Invitation
                            </h2>

                            <p style="font-size:14px; line-height:1.6; color:#4b5563;">
                                Hello {{ $participantName ?: 'Guest' }},
                            </p>

                            <p style="font-size:14px; line-height:1.6; color:#4b5563;">
                                You have been invited to attend the following meeting:
                            </p>

                            <div style="margin:24px 0; background-color:#f9fafb; padding:20px; border-radius:6px; border:1px solid #e5e7eb;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="width:30%; padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            Meeting Title:
                                        </td>
                                        <td style="padding:8px 0; font-size:14px; font-weight:600; color:#111827; vertical-align:top;">
                                            {{ $meeting->title }}
                                        </td>
                                    </tr>

                                    @if ($meeting->organizer)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Organizer:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                                {{ $meeting->organizer->name }} ({{ $meeting->organizer->email }})
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            Start Time:
                                        </td>
                                        <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                            {{ $startTimeFormatted }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            End Time:
                                        </td>
                                        <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                            {{ $endTimeFormatted }} ({{ $durationFormatted }})
                                        </td>
                                    </tr>

                                    @if ($meeting->meetingLocation || $meeting->location_details)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Location:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                                {{ $meeting->meetingLocation?->name ?? '' }}
                                                @if ($meeting->location_details)
                                                    {{ $meeting->meetingLocation ? ' - ' : '' }}{{ $meeting->location_details }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if ($meeting->url)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Meeting URL:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#2563eb; vertical-align:top; word-break:break-all;">
                                                <a href="{{ $meeting->url }}" target="_blank" style="color:#2563eb; text-decoration:underline;">
                                                    {{ $meeting->url }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            @if ($meeting->url)
                                <div style="margin:25px 0; text-align:center;">
                                    <a href="{{ $meeting->url }}" target="_blank" style="background:#0CAF60; color:#ffffff; padding:12px 24px; text-decoration:none; border-radius:6px; font-size:14px; font-weight:bold; display:inline-block;">
                                        Join Meeting
                                    </a>
                                </div>
                            @endif

                            <p style="margin-top:30px; font-size:14px; color:#111827;">
                                Thank you,<br>
                                <strong>{{ config('app.name') }} Team</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb; padding:20px; text-align:center; font-size:12px; color:#9ca3af; border-top:1px solid #e5e7eb;">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                            This is an automated invitation, please do not reply directly.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
