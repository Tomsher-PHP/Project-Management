<div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">

    <a href="{{ route('settings.leave-types.index') }}"
       class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition duration-200
       {{ ($currentTab ?? 'leave-types') === 'leave-types'
            ? 'border-success-300 bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300'
            : 'border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50' }}">
        Leave Types
    </a>

    <a href="{{ route('settings.leave-notifications.edit') }}"
       class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition duration-200
       {{ ($currentTab ?? '') === 'notifications'
            ? 'border-success-300 bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300'
            : 'border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50' }}">
        Notifications
    </a>

</div>