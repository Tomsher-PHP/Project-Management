<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Meeting Rescheduled: {{ $newMeeting->title }}</title>
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
                                Meeting Rescheduled
                            </h2>

                            <p style="font-size:14px; line-height:1.6; color:#4b5563;">
                                Hello {{ $participantName ?: 'Guest' }},
                            </p>

                            <p style="font-size:14px; line-height:1.6; color:#4b5563;">
                                Please be informed that the following meeting has been rescheduled to a new date/time:
                            </p>

                            <div style="margin:24px 0; background-color:#f9fafb; padding:20px; border-radius:6px; border:1px solid #e5e7eb;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="width:30%; padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            Meeting Title:
                                        </td>
                                        <td style="padding:8px 0; font-size:14px; font-weight:600; color:#111827; vertical-align:top;">
                                            {{ $newMeeting->title }}
                                        </td>
                                    </tr>

                                    @if ($newMeeting->organizer)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Organizer:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                                {{ $newMeeting->organizer->name }} ({{ $newMeeting->organizer->email }})
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            Original Time:
                                        </td>
                                        <td style="padding:8px 0; font-size:13px; color:#dc2626; text-decoration:line-through; vertical-align:top;">
                                            {{ $originalStartTimeFormatted }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            New Start Time:
                                        </td>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#059669; vertical-align:top;">
                                            {{ $newStartTimeFormatted }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                            End Time:
                                        </td>
                                        <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                            {{ $newEndTimeFormatted }} ({{ $newDurationFormatted }})
                                        </td>
                                    </tr>

                                    @if ($newMeeting->meetingLocation || $newMeeting->location_details)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Location:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                                {{ $newMeeting->meetingLocation?->name ?? '' }}
                                                @if ($newMeeting->location_details)
                                                    {{ $newMeeting->meetingLocation ? ' - ' : '' }}{{ $newMeeting->location_details }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if ($rescheduleReason)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Reschedule Reason:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#4b5563; vertical-align:top;">
                                                {{ $rescheduleReason }}
                                            </td>
                                        </tr>
                                    @endif

                                    @if ($newMeeting->url)
                                        <tr>
                                            <td style="padding:8px 0; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                Meeting URL:
                                            </td>
                                            <td style="padding:8px 0; font-size:13px; color:#2563eb; vertical-align:top; word-break:break-all;">
                                                <a href="{{ $newMeeting->url }}" target="_blank" style="color:#2563eb; text-decoration:underline;">
                                                    {{ $newMeeting->url }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <p style="font-size:13px; line-height:1.5; color:#6b7280; margin-top:20px;">
                                This email was generated automatically by {{ config('app.name') }}. Please do not reply directly to this message.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb; padding:15px 30px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
