<div id="changePasswordTab" class="tab-pane">
    <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
        Change Password
    </h3>

    <form id="changePasswordForm" method="POST" action="{{ route('users.change.password') }}" novalidate>
        @csrf

        <div class="grid flex-1 grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @if (! auth()->user()->is_super_admin)
                <div class="flex flex-col gap-2">
                    <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Current Password <x-red-star />
                    </label>
                    <input type="password" name="current_password" required class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    <span class="error text-sm text-red-500" data-error="current_password"></span>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    New Password <x-red-star />
                </label>
                <input type="password" name="new_password" required class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <span class="error mt-1 block text-sm text-red-500" data-error="new_password"></span>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Confirm New Password <x-red-star />
                </label>
                <input type="password" name="new_password_confirmation" required class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
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
