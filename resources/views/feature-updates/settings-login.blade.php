@extends('layouts.master')

@section('page-content')
    <div class="max-w-md mx-auto mt-12">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">

            <div class="text-center mb-6">

                <div class="w-14 h-14 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-2xl text-indigo-600"></i>
                </div>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Feature Settings
                </h2>

                <p class="text-sm text-gray-500 mt-2">
                    Enter your management password to continue.
                </p>

            </div>

            <form method="POST" action="{{ route('feature-settings.authenticate') }}">
                @csrf

                <input type="password" name="password" required autofocus autocomplete="current-password">

                @error('password')
                    <p class="text-sm text-red-500 mt-2">
                        {{ $message }}
                    </p>
                @enderror

                <button type="submit">
                    Unlock Feature Settings
                </button>
            </form>

        </div>

    </div>
@endsection
