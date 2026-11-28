@extends('customer-portal.layouts.auth')

@section('title', 'Sign In')

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Customer Portal
        </h1>
        <p class="mt-1.5 text-sm text-slate-600">
            Sign in with your portal credentials to continue
        </p>
    </div>

    <form method="POST" action="{{ route('customer.login.store') }}" class="space-y-5" data-cp-login-form>
        @csrf

        <div>
            <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">
                Username
            </label>
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                maxlength="255"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your username"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
            />
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                Password
            </label>
            <div class="relative" data-password-field>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    data-password-input
                    class="cp-password-input block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3.5 pr-11 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
                />
                <button
                    type="button"
                    data-password-toggle
                    aria-label="Show password"
                    aria-pressed="false"
                    class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus:text-slate-900"
                >
                    <svg data-password-icon="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-password-icon="hide" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587a2 2 0 0 0 2.829 2.828" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.363 5.365A9.466 9.466 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a16.72 16.72 0 0 1-2.503 3.488M6.643 6.651C4.474 8.07 2.95 10.17 2.25 12c0 0 3.75 6.75 9.75 6.75 1.81 0 3.443-.614 4.82-1.5" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('customer.password.request') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 hover:underline focus:outline-none focus:underline">
                Forgot Password?
            </a>
        </div>

        <button
            type="submit"
            data-cp-submit-button
            data-default-label="Sign In"
            data-loading-label="Signing in..."
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
        >
            <svg data-cp-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span data-cp-submit-label>Sign In</span>
        </button>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/customer-portal/auth/login.js')
@endpush
