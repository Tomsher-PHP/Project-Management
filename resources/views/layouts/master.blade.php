<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        const theme = '{{ $userTheme ?? 'light' }}';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.theme = theme;
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $pageTitle ?? 'Dashboard' }} | Tomsher Pmt</title>

    <script>
        window.authUserId = {{ auth()->id() }};
    </script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="{{ asset(config('assets.icons.favicon')) }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.slick')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.aos')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.output')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.style')) }}" />

    @stack('styles')
</head>

<body>
    <!-- Page Loader -->
    <x-page-loader />

    <!-- layout start -->
    <div id="layout-wrapper" class="layout-wrapper active w-full">
        <script>
            (function() {
                if (localStorage.getItem('sidebar_state') === 'collapsed') {
                    document.getElementById('layout-wrapper').classList.remove('active');
                }
            })();
        </script>
        <div class="relative flex w-full">

            @include('layouts.sidebar')

            <div style="z-index: 25" class="aside-overlay fixed left-0 top-0 block h-full w-full bg-black bg-opacity-30 sm:hidden"></div>

            @include('layouts.sidebar2')

            <div class="body-wrapper flex-1 overflow-x-clip dark:bg-darkblack-700">

                @include('layouts.navbar')
                @include('layouts.navbar2')

                <x-flash-alert />

                @hasSection('without-main')
                    @yield('page-content')
                @else
                    <main class="@yield('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]')" @yield('main-attributes')>
                        @yield('page-content')
                    </main>
                @endif

            </div>
        </div>
    </div>
    <!-- layout end -->

    <x-activity-log.details-modal />

    <!--scripts -->
    <script src="{{ asset(config('assets.js.jquery')) }}"></script>
    <script src="{{ asset(config('assets.js.aos')) }}"></script>
    <script src="{{ asset(config('assets.js.slick')) }}"></script>
    <script>
        AOS.init();
    </script>
    <script src="{{ asset(config('assets.js.main')) }}?v={{ filemtime(public_path(config('assets.js.main'))) }}"></script>
    <script src="{{ asset(config('assets.js.chart')) }}"></script>

    <script>
        let dataSetsLight = [{
                label: "My First Dataset",
                data: [1, 5, 2, 2, 6, 7, 8, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(250, 204, 21, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
            {
                label: "My First Dataset 2",
                data: [5, 2, 4, 2, 5, 8, 3, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(255, 120, 75, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
            {
                label: "My First Dataset 3",
                data: [2, 5, 3, 2, 5, 6, 9, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(74, 222, 128, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                    "rgba(237, 242, 247, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
        ];
        let dataSetsDark = [{
                label: "My First Dataset",
                data: [1, 5, 2, 2, 6, 7, 8, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(250, 204, 21, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
            {
                label: "My First Dataset 2",
                data: [5, 2, 4, 2, 5, 8, 3, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(255, 120, 75, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
            {
                label: "My First Dataset 3",
                data: [2, 5, 3, 2, 5, 6, 9, 7, 3, 4, 1, 3],
                backgroundColor: [
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(74, 222, 128, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                    "rgba(42, 49, 60, 1)",
                ],
                borderWidth: 0,
                borderRadius: 5,
            },
        ];

        document.addEventListener('DOMContentLoaded', () => {
            if (!window.location.pathname.includes('/projects')) {
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('projectTab_')) localStorage.removeItem(key);
                });
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
