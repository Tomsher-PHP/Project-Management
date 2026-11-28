@extends('customer-portal.layouts.auth')

@section('title', 'Verify OTP')

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Verify OTP
        </h1>
        <p class="mt-1.5 text-sm text-slate-600">
            Enter the six-digit verification code sent to your registered company email address.
        </p>
    </div>

    <form method="POST" action="{{ route('customer.password.otp.verify') }}" class="space-y-5" data-cp-otp-form>
        @csrf

        <div>
            <label for="otp" class="mb-1.5 block text-sm font-medium text-slate-700">
                Verification Code
            </label>
            <input
                id="otp"
                name="otp"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{{ '{' . $otpLength . '}' }}"
                maxlength="{{ $otpLength }}"
                required
                autofocus
                placeholder="{{ str_repeat('0', $otpLength) }}"
                data-cp-otp-input
                data-otp-length="{{ $otpLength }}"
                class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-center font-mono text-2xl font-semibold tracking-[0.5em] text-slate-900 placeholder:tracking-[0.4em] placeholder:text-slate-300 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
            />
        </div>

        <button
            type="submit"
            data-cp-submit-button
            data-default-label="Verify OTP"
            data-loading-label="Verifying..."
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
        >
            <svg data-cp-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span data-cp-submit-label>Verify OTP</span>
        </button>
    </form>

    <div class="mt-5 border-t border-slate-100 pt-5">
        <form method="POST" action="{{ route('customer.password.otp.resend') }}" class="flex flex-col items-center gap-2" data-cp-resend-form>
            @csrf
            <button
                type="submit"
                data-cp-resend-button
                data-cooldown-seconds="{{ $resendCooldownSeconds }}"
                data-default-label="Resend OTP"
                data-loading-label="Sending..."
                class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline"
            >
                Resend OTP
            </button>
            <p data-cp-resend-timer class="text-xs text-slate-500" aria-live="polite"></p>
        </form>

        <div class="mt-4 flex items-center justify-between text-sm">
            <a href="{{ route('customer.password.request') }}" class="font-medium text-slate-600 hover:text-slate-900 hover:underline">
                Back to Forgot Password
            </a>
            <a href="{{ route('customer.login') }}" class="font-medium text-slate-600 hover:text-slate-900 hover:underline">
                Back to Login
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/customer-portal/auth/verify-otp.js')
@endpush
