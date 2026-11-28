@extends('customer-portal.layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Forgot Password
        </h1>
        <p class="mt-1.5 text-sm text-slate-600">
            Enter your username to receive a verification code at your registered company email address.
        </p>
    </div>

    <form method="POST" action="{{ route('customer.password.otp.send') }}" class="space-y-5" data-cp-auth-form>
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

        <button
            type="submit"
            data-cp-submit-button
            data-default-label="Send OTP"
            data-loading-label="Sending OTP..."
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
        >
            <svg data-cp-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span data-cp-submit-label>Send OTP</span>
        </button>

        <div class="pt-1 text-center">
            <a href="{{ route('customer.login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">
                Back to Login
            </a>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/js/customer-portal/auth/login.js')
@endpush
