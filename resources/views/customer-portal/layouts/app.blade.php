<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', 'Dashboard') | Customer Portal | {{ config('app.name', 'Project Management') }}</title>

    @vite(['resources/css/customer-portal/app.css', 'resources/js/customer-portal/app.js'])

    <link rel="icon" href="{{ asset(config('assets.icons.favicon')) }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.output')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.style')) }}" />

    @stack('styles')
</head>

<body class="bg-bgray-100 dark:bg-darkblack-700 min-h-screen">
    <!-- layout start -->
    <div id="layout-wrapper" class="layout-wrapper active w-full min-h-screen bg-bgray-100 dark:bg-darkblack-700">
        <div class="relative flex w-full min-h-screen bg-bgray-100 dark:bg-darkblack-700">

            @include('customer-portal.partials.sidebar')

            <div style="z-index: 25" class="aside-overlay fixed left-0 top-0 block h-full w-full bg-black bg-opacity-30 sm:hidden"></div>

            @include('customer-portal.partials.sidebar-mobile')

            <div class="body-wrapper flex-1 min-w-0 overflow-x-clip bg-bgray-100 dark:bg-darkblack-700 min-h-screen flex flex-col">

                @include('customer-portal.partials.header')
                @include('customer-portal.partials.header-mobile')

                <main class="w-full flex-1 px-6 pb-6 pt-[96px] sm:pt-[88px] xl:px-[48px] xl:pb-[48px]">
                    @hasSection('breadcrumbs')
                        <nav class="mb-4 text-xs text-bgray-600 dark:text-bgray-300" aria-label="Breadcrumb">
                            @yield('breadcrumbs')
                        </nav>
                    @endif

                    @include('customer-portal.partials.alerts')

                    @yield('content')
                </main>

                @include('customer-portal.partials.footer')
            </div>
        </div>
    </div>
    <!-- layout end -->

    @stack('modals')
    @stack('scripts')
</body>

</html>
