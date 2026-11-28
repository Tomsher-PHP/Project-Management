<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', 'Dashboard') | Customer Portal | {{ config('app.name', 'Project Management') }}</title>

    <link rel="icon" href="{{ asset(config('assets.icons.favicon')) }}" type="image/x-icon" />

    @vite(['resources/css/customer-portal/app.css', 'resources/js/customer-portal/app.js'])
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        @include('customer-portal.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('customer-portal.partials.header')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @hasSection('breadcrumbs')
                    <nav class="mb-4 text-xs text-slate-500" aria-label="Breadcrumb">
                        @yield('breadcrumbs')
                    </nav>
                @endif

                @include('customer-portal.partials.alerts')

                @yield('content')
            </main>

            @include('customer-portal.partials.footer')
        </div>
    </div>

    @stack('scripts')
</body>

</html>
