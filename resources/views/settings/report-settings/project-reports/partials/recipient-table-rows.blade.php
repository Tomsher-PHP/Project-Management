@forelse ($recipients as $recipient)
    <tr class="border-b border-bgray-200 hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500" data-recipient-row="{{ $recipient->id }}">
        <td class="px-4 py-3 text-sm">
            @if ($recipient->user_id)
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:text-blue-500">
                    Internal
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:text-purple-500">
                    External
                </span>
            @endif
        </td>
        <td class="px-4 py-3 text-sm font-medium text-bgray-900 dark:text-white">
            {{ $recipient->name ?: ($recipient->user?->name ?: '—') }}
        </td>
        <td class="px-4 py-3 text-sm text-bgray-600 dark:text-bgray-300">
            {{ $recipient->email }}
        </td>
        <td class="px-4 py-3 text-center">
            @can($reportPermission ?? 'report_settings.manage')
                <button type="button" class="project-recipient-status-toggle switch-btn {{ $recipient->is_active ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-id="{{ $recipient->id }}" data-config-id="{{ $configuration->id ?? ($recipient->report_setting_id ?? 0) }}" data-url="{{ route('settings.project-reports.recipients.toggle.scoped', [$configuration->id ?? ($recipient->report_setting_id ?? 0), $recipient->id]) }}" role="switch" aria-checked="{{ $recipient->is_active ? 'true' : 'false' }}" title="{{ $recipient->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
            @else
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $recipient->is_active ? 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                    {{ $recipient->is_active ? 'Active' : 'Inactive' }}
                </span>
            @endcan
        </td>
        @can($reportPermission ?? 'report_settings.manage')
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <button type="button" class="btn-delete-project-recipient inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" data-id="{{ $recipient->id }}" data-config-id="{{ $configuration->id ?? ($recipient->report_setting_id ?? 0) }}" data-url="{{ route('settings.project-reports.recipients.destroy.scoped', [$configuration->id ?? ($recipient->report_setting_id ?? 0), $recipient->id]) }}" data-email="{{ $recipient->email }}" title="Delete recipient">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </td>
        @endcan
    </tr>
@empty
    <tr id="empty-recipients-row">
        <td colspan="@can($reportPermission ?? 'report_settings.manage') 5 @else 4 @endcan" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
            No recipients configured for this report.
        </td>
    </tr>
@endforelse

