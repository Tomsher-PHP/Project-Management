<div id="changePasswordTab" class="tab-pane">
    <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
        Change Password
    </h3>

    <form id="changePasswordForm" method="POST" action="{{ route('users.change.password') }}" novalidate>
        @csrf

        <div class="grid flex-1 grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @if (!auth()->user()->is_super_admin)
                <div class="flex flex-col gap-2">
                    <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Current Password <x-red-star />
                    </label>
                    <div class="relative" data-password-field>
                        <input type="password" name="current_password" required data-password-input class="user-password-input w-full rounded-lg border border-gray-300 bg-white p-2 pr-12 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <button type="button" class="absolute inset-y-0 right-4 inline-flex items-center text-bgray-500 transition hover:text-bgray-700 dark:text-bgray-300 dark:hover:text-white" data-password-toggle aria-label="Show password" aria-pressed="false">
                            <svg data-password-icon="show" class="h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M1 10C2.714 5.83333 6.04733 3.75 11 3.75C15.9527 3.75 19.286 5.83333 21 10C19.286 14.1667 15.9527 16.25 11 16.25C6.04733 16.25 2.714 14.1667 1 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="11" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <svg data-password-icon="hide" class="hidden h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M2 1L20 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                    <span class="error text-sm text-red-500" data-error="current_password"></span>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    New Password <x-red-star />
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            Minimum 8 characters, must include letters and numbers.
                        </span>
                    </span>
                </label>
                <div class="relative" data-password-field>
                    <input type="password" name="new_password" required data-password-input class="user-password-input w-full rounded-lg border border-gray-300 bg-white p-2 pr-12 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    <button type="button" class="absolute inset-y-0 right-4 inline-flex items-center text-bgray-500 transition hover:text-bgray-700 dark:text-bgray-300 dark:hover:text-white" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg data-password-icon="show" class="h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M1 10C2.714 5.83333 6.04733 3.75 11 3.75C15.9527 3.75 19.286 5.83333 21 10C19.286 14.1667 15.9527 16.25 11 16.25C6.04733 16.25 2.714 14.1667 1 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="11" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                        <svg data-password-icon="hide" class="hidden h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2 1L20 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <span class="error mt-1 block text-sm text-red-500" data-error="new_password"></span>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Confirm New Password <x-red-star />
                </label>
                <input type="password" name="new_password_confirmation" required class="user-password-input w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <span class="error text-sm text-red-500" data-error="new_password_confirmation"></span>
            </div>

            <input type="hidden" name="user_id" value="{{ $user->id }}">

            <div class="col-span-full">
                <button type="submit" class="rounded-lg bg-success-300 px-6 py-2.5 font-semibold text-white transition hover:bg-success-400">
                    Change Password
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
    <style>
        .user-password-input[type="password"] {
            font-size: 1.125rem;
            letter-spacing: 0.08em;
        }

        .user-password-input[type="password"]::placeholder {
            font-size: 1rem;
            letter-spacing: normal;
        }
    </style>
@endpush
