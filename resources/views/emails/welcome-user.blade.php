<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>

<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:20px;">

    <div style="max-width:600px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;">

        <!-- Header -->
        <div style="background:#111827;padding:20px;text-align:center;">
            <img src="{{ $companyLogo }}" alt="Company Logo" style="height:50px;">
        </div>

        <!-- Body -->
        <div style="padding:30px;">

            <h2 style="color:#111827;">Welcome to {{ $companyName }} 🎉</h2>

            <p style="color:#374151;font-size:14px;">
                Hello <strong>{{ $user->name }}</strong>,<br><br>
                Your account has been successfully created. Below are your login details:
            </p>

            <hr style="margin:20px 0;">

            <p style="font-size:14px;">
                <strong>Email:</strong> {{ $user->email }}<br>
                <strong>Password:</strong> {{ $password }}<br>
                <strong>Designation:</strong> {{ $user->designation->name ?? '-' }}<br>
                <strong>Reporting Manager:</strong> {{ $reportingManager ?? '-' }}
            </p>

            <hr style="margin:20px 0;">

            <!-- Login Button -->
            <div style="text-align:center;margin-top:20px;">
                <a href="{{ $loginUrl }}"
                   style="background:#2563eb;color:#fff;padding:12px 25px;text-decoration:none;border-radius:6px;display:inline-block;">
                    Click to Login
                </a>
            </div>

            <p style="font-size:12px;color:#6b7280;margin-top:30px;">
                For security reasons, please change your password after first login.
            </p>

        </div>

        <!-- Footer -->
        <div style="background:#f3f4f6;padding:15px;text-align:center;font-size:12px;color:#6b7280;">
            © {{ date('Y') }} {{ $companyName }}. All rights reserved.
        </div>

    </div>

</body>
</html>