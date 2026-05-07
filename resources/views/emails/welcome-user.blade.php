<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ $company->company_name }}</title>
</head>

<body style="margin:0; padding:20px; background:#f5f5f5; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; overflow:hidden;">

        <!-- HEADER -->
        <tr>
            <td style="background:#949596; padding:15px;">
                <table width="100%">
                    <tr>
                        <td style="color:#ffffff; font-size:18px; font-weight:bold;">
                            {{ config('app.name') }}
                        </td>
                        <td style="text-align:right;">
                            <img src="{{ $config->logo_url ?? url(config('assets.icons.logo')) }}" alt="Logo" height="40">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- BODY -->
        <tr>
            <td style="padding:30px;">

                <h2 style="margin-top:0; color:#111827;">
                    Welcome to {{ $company->company_name }} 🎉
                </h2>

                <p style="color:#374151; font-size:14px;">
                    Hello <strong>{{ $user->name }}</strong>,<br><br>
                    Your account has been successfully created. Below are your login details:
                </p>

                <hr style="margin:20px 0; border:none; border-top:1px solid #e5e7eb;">

                <!-- DETAILS -->
                <table width="100%" cellpadding="5" cellspacing="0" style="font-size:14px; color:#111827;">
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td><strong>Password:</strong></td>
                        <td>{{ $password }}</td>
                    </tr>

                    @if(optional($user->details->designation)->name)
                    <tr>
                        <td><strong>Designation:</strong></td>
                        <td>{{ $user->details->designation->name }}</td>
                    </tr>
                    @endif

                    @if(optional($user->details->reporter)->name)
                    <tr>
                        <td><strong>Reporting Manager:</strong></td>
                        <td>{{ $user->details->reporter->name }}</td>
                    </tr>
                    @endif
                </table>

                <hr style="margin:20px 0; border:none; border-top:1px solid #e5e7eb;">

                <!-- BUTTON -->
                <table width="100%" style="text-align:center; margin-top:20px;">
                    <tr>
                        <td>
                            <a href="{{ route('login') }}"
                               style="background:#2563eb; color:#ffffff; padding:12px 25px; text-decoration:none; border-radius:6px; display:inline-block;">
                                Click to Login
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="font-size:12px; color:#6b7280; margin-top:30px;">
                    For security reasons, please change your password after first login.
                </p>

            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td style="background:#f3f4f6; padding:15px; text-align:center; font-size:12px; color:#6b7280;">
                &copy; {{ date('Y') }} {{ $company->company_name }}. All rights reserved.
            </td>
        </tr>

    </table>

</body>
</html>