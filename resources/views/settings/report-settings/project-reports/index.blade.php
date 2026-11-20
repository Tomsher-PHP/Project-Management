@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />
    </div>

    <div class="space-y-6">
        @php
            $availableTabs = [
                'general' => 'Report Configurations',
                'recipients' => 'Recipients',
            ];

            $reportTabs = $currentReport['tabs'] ?? array_keys($availableTabs);
            $tabs = array_intersect_key($availableTabs, array_flip($reportTabs));
            $reportPermission = $currentReport['permission'] ?? 'report_settings.manage';
        @endphp

        <div class="grid grid-cols-12 gap-6">
            @include('settings.report-settings.aside')

            <!-- RIGHT SETTINGS PANEL -->
            <div class="col-span-9 lg:col-span-9 min-w-0">
                <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                    <!-- TABS -->
                    <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 p-4 dark:border-darkblack-400">
                        @foreach ($tabs as $key => $label)
                            <button type="button" data-report-tab="{{ $key }}" aria-controls="report-panel-{{ $key }}" aria-selected="false" class="report-tab {{ $key === 'general' ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <!-- GENERAL SETTINGS / CONFIGURATIONS LIST TAB -->
                    <section id="report-panel-general" data-report-panel="general" class="report-panel p-5">
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">
                                    Project Report Configurations
                                </h2>
                                <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                                    Manage automated project performance, progress, and milestone reports.
                                </p>
                            </div>

                            @can($reportPermission)
                                <button type="button" id="btn-add-project-report" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Add Report
                                </button>
                            @endcan
                        </div>

                        <!-- CONFIGURATIONS TABLE -->
                        <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                            <div class="flex flex-col space-y-5">
                                <div class="table-content w-full overflow-x-auto">
                                    <table class="w-full text-left" id="project-reports-table">
                                        <thead>
                                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[200px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Report Name</span></th>
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Frequency</span></th>
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Delivery Time</span></th>
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[150px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Timezone</span></th>
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[110px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span></th>
                                                @can($reportPermission)
                                                    <th class="px-4 py-4 whitespace-nowrap min-w-[120px] text-right"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span></th>
                                                @endcan
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                                            @forelse ($configurations as $config)
                                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500" data-config-row="{{ $config->id }}">
                                                    <td class="px-4 py-4">
                                                        <span class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                                            {{ $config->report_name ?: 'Project Report (#' . $config->id . ')' }}
                                                        </span>
                                                        <span class="block text-xs font-mono text-bgray-600 dark:text-bgray-300">
                                                            {{ $config->setting_key }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <span class="inline-flex items-center rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                                            {{ ucfirst($config->frequency) }}
                                                        </span>
                                                        @if ($config->frequency === 'weekly' && $config->weekly_day)
                                                            <span class="block text-xs text-bgray-500 dark:text-bgray-300 mt-0.5">
                                                                Every {{ [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'][$config->weekly_day] ?? 'Day ' . $config->weekly_day }}
                                                            </span>
                                                        @elseif ($config->frequency === 'monthly' && isset($config->settings['schedule']['monthly_day']))
                                                            <span class="block text-xs text-bgray-500 dark:text-bgray-300 mt-0.5">
                                                                {{ $config->settings['schedule']['monthly_day'] === 'last' ? 'Last Day of Month' : 'Day ' . $config->settings['schedule']['monthly_day'] . ' of month' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium">
                                                        {{ $config->delivery_time ? \Carbon\Carbon::parse($config->delivery_time)->format($globalTimeFormat ?? config('constants.time_format', 'H:i')) : '—' }}
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300">
                                                        {{ $config->timezone }}
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        @can($reportPermission)
                                                            <button type="button" class="project-report-status-toggle switch-btn {{ $config->enabled ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-id="{{ $config->id }}" data-url="{{ route('settings.project-reports.toggle', $config->id) }}" role="switch" aria-checked="{{ $config->enabled ? 'true' : 'false' }}" title="{{ $config->enabled ? 'Click to disable' : 'Click to enable' }}">
                                                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $config->enabled ? 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                                                {{ $config->enabled ? 'Enabled' : 'Disabled' }}
                                                            </span>
                                                        @endcan
                                                    </td>
                                                    @can($reportPermission)
                                                        <td class="px-4 py-4 text-right whitespace-nowrap">
                                                            <div class="inline-flex items-center gap-2">
                                                                <button type="button" class="btn-edit-project-report inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" data-id="{{ $config->id }}" title="Edit Configuration">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                </button>
                                                                <button type="button" class="btn-delete-project-report inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" data-id="{{ $config->id }}" data-name="{{ $config->report_name ?: 'Project Report (#' . $config->id . ')' }}" data-url="{{ route('settings.project-reports.destroy', $config->id) }}" title="Delete Configuration">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    @endcan
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="@can($reportPermission) 6 @else 5 @endcan" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                                        No project report configurations found. Click "+ Add Report" to create one.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- RECIPIENTS TAB -->
                    <section id="report-panel-recipients" data-report-panel="recipients" class="report-panel hidden p-5">
                        @if ($configurations->isEmpty())
                            <div class="rounded-lg border border-dashed border-bgray-300 p-8 text-center text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">
                                <p class="text-base font-medium">No Project Report configurations found.</p>
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">Please create a project report configuration in General Settings before managing recipients.</p>
                            </div>
                        @else
                            <!-- Configuration Selector -->
                            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                                <div class="flex items-center gap-3">
                                    <label for="select-report-recipient-config" class="text-sm font-semibold text-bgray-900 dark:text-white whitespace-nowrap">
                                        Select Report:
                                    </label>
                                    <select id="select-report-recipient-config" class="rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm font-medium text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white min-w-[240px]">
                                        @foreach ($configurations as $cfg)
                                            <option value="{{ $cfg->id }}" @selected($selectedConfiguration && $selectedConfiguration->id === $cfg->id)>
                                                {{ $cfg->report_name ?: 'Project Report (#' . $cfg->id . ')' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="text-xs text-bgray-500 dark:text-bgray-400">
                                    Recipients are configured independently for each project report configuration.
                                </div>
                            </div>

                            @can($reportPermission)
                                <form id="project-report-recipients-form" class="mb-6 rounded-lg border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                                    @csrf
                                    <input type="hidden" id="recipient-config-id" name="config_id" value="{{ $selectedConfiguration?->id }}">

                                    <!-- Internal PMS Users -->
                                    <div class="border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                                        <h3 class="text-base font-semibold text-bgray-900 dark:text-white">Internal Recipients</h3>
                                        <div class="mt-3">
                                            <label for="recipient-internal-users" class="sr-only">Internal Users</label>
                                            <select id="recipient-internal-users" name="user_ids[]" multiple class="tom-select w-full" data-placeholder="Select employee(s)...">
                                                @foreach ($activeUsers as $user)
                                                    <option value="{{ $user->id }}" @selected($recipients->where('user_id', $user->id)->isNotEmpty())>
                                                        {{ $user->name }} ({{ $user->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="mt-1 hidden text-xs text-red-500" data-recipient-error="user_ids"></span>
                                        </div>
                                    </div>

                                    <!-- External Recipients -->
                                    <div class="mt-5 border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                                        <div class="flex items-center justify-between mb-3">
                                            <div>
                                                <h3 class="text-base font-semibold text-bgray-900 dark:text-white">External Recipients</h3>
                                                <p class="text-xs text-bgray-700 dark:text-bgray-400 mt-0.5">Send report to external stakeholders, clients, or third-party email addresses.</p>
                                            </div>
                                            <button type="button" id="btn-add-external-email" class="inline-flex items-center gap-1 rounded-lg border border-success-300 bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-400 transition hover:bg-success-100 dark:bg-success-900/30 dark:text-success-300">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                <span>Add Email</span>
                                            </button>
                                        </div>

                                        <div id="external-recipients-list" class="space-y-3">
                                            @php
                                                $externalRecipients = $recipients->whereNull('user_id')->values();
                                            @endphp
                                            @foreach ($externalRecipients as $idx => $ext)
                                                <div class="external-recipient-row flex flex-col sm:flex-row items-center gap-3" data-row-index="{{ $idx }}">
                                                    <div class="w-full sm:w-1/3">
                                                        <input type="text" name="external_recipients[{{ $idx }}][name]" value="{{ $ext->name }}" placeholder="Recipient Name (Optional)" maxlength="255" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                                    </div>
                                                    <div class="w-full sm:w-1/2">
                                                        <input type="email" name="external_recipients[{{ $idx }}][email]" value="{{ $ext->email }}" placeholder="Email Address *" required maxlength="254" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn-remove-external-recipient inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" title="Remove email">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div id="no-external-recipients-note" class="py-2 text-xs italic text-bgray-500 dark:text-bgray-400 {{ $externalRecipients->isNotEmpty() ? 'hidden' : '' }}">
                                            No external recipients added. Click "+ Add Email" to add one.
                                        </div>
                                        <span class="mt-1 hidden text-xs text-red-500" data-recipient-error="external_recipients"></span>
                                    </div>

                                    <div class="mt-4 flex items-center justify-between">
                                        <div id="recipients-feedback-msg" class="text-xs font-medium text-bgray-500"></div>
                                        <button type="submit" id="btn-save-project-report-recipients" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                                            Save Recipients
                                        </button>
                                    </div>
                                </form>
                            @endcan

                            <!-- Recipient List Table -->
                            <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-semibold text-bgray-900 dark:text-white">Configured Recipients</h3>
                                    <span id="recipient-count-badge" class="rounded-full bg-bgray-100 px-2.5 py-0.5 text-xs font-medium text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $recipients->count() }} {{ Str::plural('recipient', $recipients->count()) }}
                                    </span>
                                </div>
                                <div class="table-content w-full overflow-x-auto">
                                    <table id="project-report-recipients-table" class="w-full">
                                        <thead>
                                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                                <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">Type</th>
                                                <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">Name</th>
                                                <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">Email Address</th>
                                                <th class="px-4 py-3 text-center text-sm font-medium text-bgray-600 dark:text-bgray-300">Status</th>
                                                @can($reportPermission)
                                                    <th class="px-4 py-3 text-right text-sm font-medium text-bgray-600 dark:text-bgray-300">Actions</th>
                                                @endcan
                                            </tr>
                                        </thead>
                                        <tbody id="project-report-recipients-tbody">
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
                                                        @can($reportPermission)
                                                            <button type="button" class="project-recipient-status-toggle switch-btn {{ $recipient->is_active ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-id="{{ $recipient->id }}" data-config-id="{{ $selectedConfiguration?->id }}" data-url="{{ route('settings.project-reports.recipients.toggle.scoped', [$selectedConfiguration?->id ?? 0, $recipient->id]) }}" role="switch" aria-checked="{{ $recipient->is_active ? 'true' : 'false' }}" title="{{ $recipient->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $recipient->is_active ? 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                                                {{ $recipient->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        @endcan
                                                    </td>
                                                    @can($reportPermission)
                                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                                            <button type="button" class="btn-delete-project-recipient inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" data-id="{{ $recipient->id }}" data-config-id="{{ $selectedConfiguration?->id }}" data-url="{{ route('settings.project-reports.recipients.destroy.scoped', [$selectedConfiguration?->id ?? 0, $recipient->id]) }}" data-email="{{ $recipient->email }}" title="Delete recipient">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    @endcan
                                                </tr>
                                            @empty
                                                <tr id="empty-recipients-row">
                                                    <td colspan="@can($reportPermission) 5 @else 4 @endcan" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                                        No recipients configured for this report.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    @include('settings.report-settings.project-reports.partials.report-modal')
@endsection

@push('scripts')
    @vite('resources/js/settings/data-sync/report-settings/project-report.js')
@endpush
