<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:30px 0;">
        <tr>
            <td align="center">

                <!-- Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background:#c8c8c8; padding:20px; text-align:center;">
                            <h1 style="color:#242424; margin:0; font-size:20px; letter-spacing:0.5px;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                        <td style="background:#c8c8c8; padding:20px; text-align:center;">
                            <img src="{{ url(config('assets.icons.logo')) }}" alt="Logo" height="40">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:30px; color:#333333;" colspan="2">

                            <h2 style="margin-top:0; font-size:18px; color:#111827;">
                                {{ $title }}
                            </h2>

                            <p>Dear {{ $notifiable->name ?? 'User' }},</p>

                            <p style="font-size:14px; line-height:1.6; color:#4b5563;">
                                {{ $messageText }}
                            </p>

                            @if (!empty($details))
                                <div style="margin:24px 0;">
                                    <h3 style="margin:0 0 12px; font-size:15px; color:#111827;">
                                        Details
                                    </h3>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border-top:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb;">
                                        @foreach ($details as $detail)
                                            <tr>
                                                <td style="width:34%; padding:10px 12px 10px 0; border-bottom:1px solid #f3f4f6; font-size:13px; font-weight:bold; color:#374151; vertical-align:top;">
                                                    {{ $detail['label'] }}
                                                </td>
                                                <td style="padding:10px 0; border-bottom:1px solid #f3f4f6; font-size:13px; line-height:1.5; color:#4b5563; vertical-align:top;">
                                                    {!! nl2br(e($detail['value'])) !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endif

                            @if ($url)
                                <div style="margin:25px 0;">
                                    <a href="{{ $url }}" style="background:#0CAF60; color:#ffffff; padding:10px 18px; text-decoration:none; border-radius:5px; font-size:14px; font-weight:bold; display:inline-block;">
                                        View Details
                                    </a>
                                </div>
                            @endif

                            <p style="font-size:14px; color:#4b5563;">
                                If you have any questions, please contact our support team.
                            </p>

                            <p style="margin-top:30px; font-size:14px; color:#111827;">
                                Sincerely,<br>
                                <strong>{{ config('app.name') }} Team</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb; padding:20px; text-align:center; font-size:12px; color:#9ca3af;" colspan="2">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                            This is an automated message, please do not reply.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
