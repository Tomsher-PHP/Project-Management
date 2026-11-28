<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal – Password Reset Verification Code</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f6fb; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 32px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="padding:28px 32px; background:linear-gradient(135deg, #059669 0%, #047857 100%);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:24px; font-weight:700; line-height:1.2; color:#ffffff;">
                                        {{ $appName }} &mdash; Customer Portal
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px; font-size:14px; line-height:1.7; color:rgba(255,255,255,0.9);">
                                        Password Reset Verification
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 32px 24px;">
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.7; color:#111827;">
                                Hello {{ $companyName }},
                            </p>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#4b5563;">
                                We received a request to reset the password for your Customer Portal account. Use the verification code below to continue:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center" style="padding:24px; border:1px solid #d1fae5; border-radius:16px; background-color:#ecfdf5;">
                                        <div style="font-size:32px; line-height:1; font-weight:700; letter-spacing:10px; color:#065f46;">
                                            {{ $otp }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px 20px; border-radius:12px; background-color:#f9fafb; border:1px solid #e5e7eb;">
                                        <p style="margin:0 0 8px; font-size:14px; line-height:1.6; color:#374151;">
                                            This verification code will expire in <strong>{{ $expiryMinutes }} {{ $expiryMinutes === 1 ? 'minute' : 'minutes' }}</strong>.
                                        </p>
                                        <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                                            For your security, never share this code with anyone. Our team will never ask you for this verification code.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px; font-size:14px; line-height:1.7; color:#4b5563;">
                                If you did not initiate this password reset request, please ignore this email. Your password will remain unchanged.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#111827;">
                                Regards,<br>
                                {{ $appName }} Team
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 28px; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; line-height:1.7; color:#6b7280; text-align:center;">
                                This is an automated security notification from {{ $appName }} Customer Portal.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
