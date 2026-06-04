<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f6fb; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 32px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="padding:28px 32px; background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:24px; font-weight:700; line-height:1.2; color:#ffffff;">
                                        {{ $appName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px; font-size:14px; line-height:1.7; color:rgba(255,255,255,0.88);">
                                        Secure account access for your team and workspace.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 32px 24px;">
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.7; color:#111827;">
                                Hi {{ $user->name ?? 'there' }},
                            </p>

                            <p style="margin:0 0 16px; font-size:16px; line-height:1.7; color:#4b5563;">
                                We received a request to reset your password.
                            </p>

                            <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4b5563;">
                                Your verification code is:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center" style="padding:24px; border:1px solid #dcfce7; border-radius:16px; background-color:#f0fdf4;">
                                        <div style="font-size:32px; line-height:1; font-weight:700; letter-spacing:10px; color:#166534;">
                                            {{ $otp }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="padding:18px 20px; border-radius:14px; background-color:#f9fafb; border:1px solid #e5e7eb;">
                                        <p style="margin:0; font-size:14px; line-height:1.7; color:#374151;">
                                            This code will expire in <strong>5 minutes</strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#4b5563;">
                                If you did not request a password reset, you can safely ignore this email. Your account remains secure.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#111827;">
                                Thanks,<br>
                                {{ $appName }} Team
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 28px; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; line-height:1.7; color:#6b7280; text-align:center;">
                                This is an automated security email from {{ $appName }}. Please do not share this code with anyone.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
