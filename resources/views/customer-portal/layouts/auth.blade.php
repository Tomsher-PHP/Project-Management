<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', 'Sign In') | Customer Portal | {{ config('app.name', 'Project Management') }}</title>

    <link rel="icon" href="{{ asset(config('assets.icons.favicon')) }}" type="image/x-icon" />

    @vite(['resources/css/customer-portal/app.css', 'resources/css/customer-portal/auth.css'])
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <a href="{{ route('customer.index') }}" class="inline-flex items-center">
                    <img src="{{ asset(config('assets.icons.logo')) }}" class="h-10 w-auto" alt="{{ config('app.name', 'Project Management') }}" />
                </a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                @include('customer-portal.partials.alerts')

                @yield('content')
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ config('app.name', 'Project Management') }}. Customer Portal.
            </p>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
