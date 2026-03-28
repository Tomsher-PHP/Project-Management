<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    <title>Sign In | {{ config('app.name', 'Project Management') }}</title>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="{{ asset(config('assets.icons.favicon')) }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.slick')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.aos')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.output')) }}" />
    <link rel="stylesheet" href="{{ asset(config('assets.css.style')) }}" />
</head>

<body>
    <x-flash-alert />

    @yield('content')

    <!-- Modal -->
    @include('auth.modal-reset-password')

    <!--scripts -->

    <script src="{{ asset(config('assets.js.jquery')) }}"></script>
    <script src="{{ asset(config('assets.js.aos')) }}"></script>
    <script src="{{ asset(config('assets.js.slick')) }}"></script>
    <script>
        AOS.init();
    </script>
    <script src="{{ asset(config('assets.js.chart')) }}"></script>
    <script src="{{ asset(config('assets.js.main')) }}"></script>

    <script>
        localStorage.theme = 'light';
        document.documentElement.classList.remove('dark');
    </script>

</body>

</html>
