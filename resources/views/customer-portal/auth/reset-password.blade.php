@extends('customer-portal.layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Reset Password
        </h1>
        <p class="mt-1.5 text-sm text-slate-600">
            Create a new password for your Customer Portal account.
        </p>
    </div>

    <form method="POST" action="{{ route('customer.password.update') }}" class="space-y-5" data-cp-reset-form>
        @csrf

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                New Password
            </label>
            <div class="relative" data-password-field>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autofocus
                    autocomplete="new-password"
                    placeholder="Enter your new password"
                    data-password-input
                    data-cp-new-password
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
            <p class="mt-1.5 text-xs text-slate-500">
                Must be at least 8 characters and include at least one letter and one number.
            </p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">
                Confirm Password
            </label>
            <div class="relative" data-password-field>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your new password"
                    data-password-input
                    data-cp-confirm-password
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
            <p data-cp-match-feedback class="mt-1.5 hidden text-xs"></p>
        </div>

        <button
            type="submit"
            data-cp-submit-button
            data-default-label="Reset Password"
            data-loading-label="Resetting Password..."
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
        >
            <svg data-cp-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span data-cp-submit-label>Reset Password</span>
        </button>

        <div class="pt-1 text-center">
            <a href="{{ route('customer.login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">
                Back to Login
            </a>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/customer-portal/auth/reset-password.js')
@endpush
