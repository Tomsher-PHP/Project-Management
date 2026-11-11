@extends('layouts.master')

@section('page-content')
    <div class="mx-auto mt-12 w-full max-w-md px-4">

        <div class="rounded-xl border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">

            {{-- Header --}}
            <div class="mb-7 text-center">

                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-success-50 text-success-400 dark:bg-success-500/10">
                    <svg class="h-6 w-6 stroke-current" viewBox="0 0 24 24"
                        fill="none" xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 10V7a4 4 0 1 1 8 0v3"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 14.5v2"
                            stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>

                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">
                    Feature Settings
                </h2>

                <p class="mt-2 text-sm leading-6 text-bgray-500 dark:text-bgray-300">
                    Enter your management password to access feature settings.
                </p>

            </div>

            {{-- Error Message --}}
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-300 dark:border-error-900/40 dark:bg-darkblack-500">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('feature-settings.authenticate') }}">
                @csrf

                <div>
                    <label for="password"
                        class="mb-2 block text-sm font-semibold text-bgray-700 dark:text-bgray-200">
                        Management Password
                    </label>

                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autofocus
                            autocomplete="current-password"
                            placeholder="Enter management password"
                            class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-900 placeholder:text-bgray-400 outline-none transition focus:border-success-300 focus:ring-2 focus:ring-success-300/20 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder:text-bgray-500"
                        >
                    </div>

                    @error('password')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-success-300 px-5 py-3 text-sm font-semibold text-white transition hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 focus:ring-offset-2 dark:focus:ring-offset-darkblack-600">

                    <i class="fa-solid fa-unlock-keyhole"></i>
                    Unlock Feature Settings

                </button>

            </form>

            {{-- Footer --}}
            <div class="mt-6 border-t border-bgray-100 pt-4 text-center dark:border-darkblack-400">
                <p class="text-xs text-bgray-500 dark:text-bgray-400">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    Restricted access for authorised management only.
                </p>
            </div>

        </div>

    </div>
@endsection
