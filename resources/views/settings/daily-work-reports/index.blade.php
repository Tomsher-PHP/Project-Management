@extends('layouts.master')

@section('page-content')
<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $availableTabs = [
            'general' => 'General Settings',
            'recipients' => 'Recipients',
            'users' => 'User Settings',
            'advanced' => 'Advanced',
        ];

        $reportTabs = $currentReport['tabs'] ?? array_keys($availableTabs);

        $tabs = array_intersect_key(
            $availableTabs,
            array_flip($reportTabs)
        );

        $enabledValue = (bool) old('enabled', $settings->enabled);

        $reportPermission = $currentReport['permission']
            ?? 'daily_work_report.edit';
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
        {{-- REPORT NAVIGATION --}}
        <aside class="h-fit rounded-lg bg-white p-4 dark:bg-darkblack-600">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Available Reports
            </h2>

            <div class="space-y-2">
                @foreach ($reportDefinitions as $key => $definition)
                    @php
                        $isAvailable = $definition['available'] ?? true;
                        $isSelected = $reportKey === $key;
                    @endphp

                    @if ($isAvailable)
                        <a
                            href="{{ route('settings.daily-work-reports.index', ['report_key' => $key]) }}"
                            class="block rounded-lg px-3 py-3 transition
                                {{ $isSelected
                                    ? 'bg-success-300 text-white'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-darkblack-500' }}"
                        >
                            <span class="block text-sm font-semibold">
                                {{ $definition['label'] }}
                            </span>

                            @if (!empty($definition['description']))
                                <span class="mt-1 block text-xs {{ $isSelected ? 'text-white/80' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $definition['description'] }}
                                </span>
                            @endif
                        </a>
                    @else
                        <div class="rounded-lg px-3 py-3 text-gray-500 dark:text-gray-400">
                            <span class="block text-sm font-semibold">
                                {{ $definition['label'] }}
                            </span>

                            <span class="mt-1 block text-xs">
                                Coming soon
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </aside>

        {{-- RIGHT SETTINGS PANEL --}}
        <div class="min-w-0">
            <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                {{-- TABS --}}
                <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 p-4 dark:border-darkblack-400">
                    @foreach ($tabs as $key => $label)
                        <button
                            type="button"
                            data-report-tab="{{ $key }}"
                            aria-controls="report-panel-{{ $key }}"
                            aria-selected="false"
                            class="report-tab {{ $key === 'general' ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>


                {{-- GENERAL SETTINGS TAB --}}
                <section
                    id="report-panel-general"
                    data-report-panel="general"
                    class="report-panel p-5"
                >
                    <form
                        method="POST"
                        action="{{ route('settings.daily-work-reports.settings.update') }}"
                    >
                        @csrf

                        <input type="hidden" name="report_key" value="{{ $reportKey }}">

                        <div class="mb-6 flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div>
                                <h2 class="font-semibold text-gray-900 dark:text-white">
                                    Enable automatic reports
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Reports are generated only when the scheduler runs and the configured time is due.
                                </p>
                            </div>

                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="daily-work-reports-enabled"
                                    name="enabled"
                                    value="1"
                                    @checked($enabledValue)
                                    class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                                >

                                <span
                                    id="daily-work-reports-enabled-label"
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    {{ $enabledValue ? 'Enabled' : 'Disabled' }}
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            <div>
                                <label for="report-frequency" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Report frequency
                                </label>
                                <select
                                    id="report-frequency"
                                    name="frequency"
                                    required
                                    class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >
                                    <option value="daily" @selected(old('frequency', $settings->frequency) === 'daily')>
                                        Daily
                                    </option>
                                    <option value="weekdays" @selected(old('frequency', $settings->frequency) === 'weekdays')>
                                        Weekdays only
                                    </option>
                                    <option value="weekly" @selected(old('frequency', $settings->frequency) === 'weekly')>
                                        Weekly
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="delivery-time" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Delivery time
                                </label>
                                <input
                                    id="delivery-time"
                                    type="time"
                                    name="delivery_time"
                                    value="{{ old('delivery_time', substr((string) $settings->delivery_time, 0, 5)) }}"
                                    required
                                    class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >
                            </div>

                            <div>
                                <label for="report-timezone" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Timezone
                                </label>
                                <select
                                    id="report-timezone"
                                    name="timezone"
                                    required
                                    class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >
                                    @foreach (timezone_identifiers_list() as $timezone)
                                        <option
                                            value="{{ $timezone }}"
                                            @selected(old('timezone', $settings->timezone) === $timezone)
                                        >
                                            {{ $timezone }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- <div>
                                <label for="reporting-period" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Reporting period
                                </label>
                                <select
                                    id="reporting-period"
                                    name="reporting_period"
                                    required
                                    class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >
                                    <option value="current_day" @selected(old('reporting_period', $settings->reporting_period) === 'current_day')>
                                        Current day
                                    </option>
                                    <option value="previous_working_day" @selected(old('reporting_period', $settings->reporting_period) === 'previous_working_day')>
                                        Previous working day
                                    </option>
                                </select>
                            </div> --}}

                            <div
                                id="weekly-day-wrapper"
                                class="{{ old('frequency', $settings->frequency) === 'weekly' ? '' : 'hidden' }}"
                            >
                                <label for="weekly-day" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                    Weekly delivery day
                                </label>

                                <select
                                    id="weekly-day"
                                    name="weekly_day"
                                    class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >
                                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
                                        <option
                                            value="{{ $day }}"
                                            @selected((int) old('weekly_day', $settings->weekly_day) === $day)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>



                        @if ($reportKey === 'daily_work')
                            <div class="mt-6 space-y-4">
                                <div>
                                    <label for="reporting-period"
                                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                        Reporting period
                                    </label>

                                    <select
                                        id="reporting-period"
                                        name="reporting_period"
                                        required
                                        class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                    >
                                        <option value="current_day"
                                            @selected(old('reporting_period', $settings->reporting_period) === 'current_day')>
                                            Current day
                                        </option>

                                        <option value="previous_working_day"
                                            @selected(old('reporting_period', $settings->reporting_period) === 'previous_working_day')>
                                            Previous working day
                                        </option>
                                    </select>
                                </div>
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="send_zero_activity"
                                        value="1"
                                        @checked((bool) old('send_zero_activity', $settings->send_zero_activity))
                                        class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                                    >

                                    <span>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                            Send reports for employees with no activity
                                        </span>

                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            Send a report even when no reportable activity is found.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="include_inactive_users"
                                        value="1"
                                        @checked((bool) old('include_inactive_users', $settings->include_inactive_users))
                                        class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                                    >

                                    <span>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                            Include inactive employees
                                        </span>

                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            Include inactive employees when generating reports.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        @endif

                        @if (!empty($currentReport['sections']))
                            @php
                                $sections = $currentReport['sections'];

                                $selectedSections = old(
                                    'report_sections',
                                    $settings->report_sections ?? []
                                );

                                if (is_string($selectedSections)) {
                                    $selectedSections = json_decode($selectedSections, true) ?: [];
                                }

                                if (!is_array($selectedSections)) {
                                    $selectedSections = [];
                                }
                            @endphp

                            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">
                                    Report sections
                                </h3>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($sections as $key => $label)
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white">
                                            <input
                                                type="checkbox"
                                                name="report_sections[]"
                                                value="{{ $key }}"
                                                @checked(in_array($key, $selectedSections, true))
                                                class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                                            >

                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @can($reportPermission)
                            <div class="mt-6 flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400"
                                >
                                    Save Settings
                                </button>
                            </div>
                        @endcan
                    </form>
                </section>

                {{-- RECIPIENTS TAB --}}
                <section
                    id="report-panel-recipients"
                    data-report-panel="recipients"
                    class="report-panel hidden p-5"
                >
                    @can($reportPermission)
                        <form
                            method="POST"
                            action="{{ route('settings.daily-work-reports.recipients.store') }}"
                            class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                        >
                            @csrf
                            <input type="hidden" name="report_key" value="{{ $reportKey }}">

                            <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">
                                Add recipient
                            </h2>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="recipient-scope" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                        Recipient scope
                                    </label>
                                    <select
                                        name="scope"
                                        id="recipient-scope"
                                        required
                                        class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                    >
                                        <option value="global" @selected(old('scope', 'global') === 'global')>
                                            Global
                                        </option>
                                        <option value="team" @selected(old('scope') === 'team')>
                                            Team
                                        </option>
                                        <option value="user" @selected(old('scope') === 'user')>
                                            Specific employee
                                        </option>
                                    </select>
                                </div>

                                <div id="recipient-scope-id-wrapper" class="hidden">
                                    <label
                                        id="recipient-scope-id-label"
                                        for="recipient-scope-id"
                                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
                                    >
                                        Select target
                                    </label>

                                    <select
                                        name="scope_id"
                                        id="recipient-scope-id"
                                        class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                        disabled
                                    >
                                        <option value="">Select...</option>

                                        @foreach ($teams as $team)
                                            <option
                                                value="{{ $team->id }}"
                                                data-scope="team"
                                                @selected(old('scope') === 'team' && (string) old('scope_id') === (string) $team->id)
                                            >
                                                {{ $team->name }}
                                            </option>
                                        @endforeach

                                        @foreach ($users as $user)
                                            <option
                                                value="{{ $user->id }}"
                                                data-scope="user"
                                                @selected(old('scope') === 'user' && (string) old('scope_id') === (string) $user->id)
                                            >
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="recipient-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                        Recipient name
                                    </label>
                                    <input
                                        id="recipient-name"
                                        name="name"
                                        type="text"
                                        maxlength="255"
                                        value="{{ old('name') }}"
                                        class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                        placeholder="Optional"
                                    >
                                </div>

                                <div>
                                    <label for="recipient-email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                        Email address
                                    </label>
                                    <input
                                        id="recipient-email"
                                        name="email"
                                        type="email"
                                        maxlength="254"
                                        value="{{ old('email') }}"
                                        required
                                        class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                        placeholder="name@company.com"
                                    >
                                </div>
                            </div>

                            <label class="mt-4 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    @checked((bool) old('is_active', true))
                                    class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600"
                                >
                                Active
                            </label>

                            <div class="mt-4 flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400"
                                >
                                    Add Recipient
                                </button>
                            </div>
                        </form>
                    @endcan

                    <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                        <div class="flex flex-col space-y-5">
                            <div class="table-content w-full overflow-x-auto">
                                <table class="w-full">
                                    <tbody>
                                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Scope</span></th>
                                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Target ID</span></th>
                                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email address</span></th>
                                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Name</span></th>
                                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span></th>
                                            @can($reportPermission)
                                                <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span></th>
                                            @endcan
                                        </tr>
                                        @forelse ($recipients as $recipient)
                                            <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500">
                                                <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                        {{ str_replace('_', ' ', $recipient->scope) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                        {{ $recipient->scope_id ?? 'All employees' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                        {{ $recipient->email }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                        {{ $recipient->name ?: '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                    <x-status-toggle
                                                        :model="$recipient"
                                                        route="settings.daily-work-reports.recipients.toggle"
                                                        :route-parameters="['recipient' => $recipient->id]"
                                                        entity="daily-work-report-recipient"
                                                    />
                                                </td>

                                                @can($reportPermission)
                                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                                        <form
                                                            method="POST"
                                                            action="{{ route('settings.daily-work-reports.recipients.destroy', $recipient) }}"
                                                            onsubmit="return confirm('Remove this recipient?')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="report_key" value="{{ $reportKey }}">
                                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400 group">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @empty
                                            <tr>
                                                <td
                                                    colspan="@can($reportPermission) 6 @else 5 @endcan"
                                                    class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                                                >
                                                    No additional recipients configured.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- USER SETTINGS TAB --}}
                <section
                    id="report-panel-users"
                    data-report-panel="users"
                    class="report-panel hidden p-5"
                >
                    @include('settings.daily-work-reports.partials.user-settings', [
                        'users' => $users,
                        'teams' => $teams,
                        'reportKey' => $reportKey,
                        'currentReport' => $currentReport,
                        'reportPermission' => $reportPermission,
                    ])
                </section>

                {{-- EMAIL TEMPLATE TAB --}}
                {{-- <section
                    id="report-panel-template"
                    data-report-panel="template"
                    class="report-panel hidden p-5"
                >
                    <div class="mx-auto max-w-3xl overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="border-b bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                Tomsher PMS
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Daily Work Report email preview
                            </p>
                        </div>

                        <div class="space-y-5 p-5">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Daily Work Report — Sample Employee
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Example preview. Actual report values will come from generated report data.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                                @foreach ([
                                    ['label' => 'Assigned tasks', 'value' => '—'],
                                    ['label' => 'Completed', 'value' => '—'],
                                    ['label' => 'Pending', 'value' => '—'],
                                    ['label' => 'Time logged', 'value' => '—'],
                                ] as $metric)
                                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950">
                                        <p class="text-xs text-gray-600 dark:text-gray-300">
                                            {{ $metric['label'] }}
                                        </p>
                                        <p class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                                            {{ $metric['value'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    Task details
                                </h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Project, task status, due dates, time logs, comments, and developer notes will appear here when available.
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">
                        This is a visual sample only; it does not send an email.
                    </p>
                </section> --}}

                {{-- ADVANCED TAB --}}
                <section
                    id="report-panel-advanced"
                    data-report-panel="advanced"
                    class="report-panel hidden p-5"
                >
                    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                        @foreach (['queued', 'sent', 'failed', 'partial'] as $status)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="text-sm capitalize text-gray-500 dark:text-gray-400">
                                    {{ $status }} deliveries
                                </p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $deliveryCounts[$status] ?? 0 }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">
                        Recent {{ $currentReport['label'] ?? 'Report' }} History
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3">Employee</th>
                                    <th class="px-4 py-3">Report date</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Generated at</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reports as $report)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ $report->user?->name ?? 'Deleted employee' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ $report->report_date?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ ucfirst($report->status) }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ $report->generated_at?->format('d M Y H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No reports have been generated yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        Manual generation, test sending, and retry actions must be connected to their dedicated endpoints before use.
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-report-tab]');
    const panels = document.querySelectorAll('[data-report-panel]');

    const activeClasses = [
        'bg-success-300',
        'text-white',
        'shadow-sm'
    ];

    const inactiveClasses = [
        'border',
        'border-bgray-200',
        'bg-bgray-50',
        'text-bgray-700',
        'hover:border-success-300',
        'hover:text-success-400',
        'dark:border-darkblack-400',
        'dark:bg-darkblack-500',
        'dark:text-bgray-50',
        'dark:hover:border-success-300',
        'dark:hover:text-success-300'
    ];

    function activateTab(name) {
        tabs.forEach(function (tab) {
            const isActive = tab.dataset.reportTab === name;

            tab.classList.remove(
                ...(isActive ? inactiveClasses : activeClasses)
            );

            tab.classList.add(
                ...(isActive ? activeClasses : inactiveClasses)
            );

            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle(
                'hidden',
                panel.dataset.reportPanel !== name
            );
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.dataset.reportTab);
        });
    });

    const firstTab = document.querySelector('[data-report-tab]');

    if (firstTab) {
        activateTab(firstTab.dataset.reportTab);
    }


    // Enabled / Disabled label
    const enabledCheckbox = document.getElementById('daily-work-reports-enabled');
    const enabledLabel = document.getElementById('daily-work-reports-enabled-label');

    if (enabledCheckbox && enabledLabel) {
        enabledCheckbox.addEventListener('change', function () {
            enabledLabel.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    }

    // Recipient scope filtering
    const scope = document.getElementById('recipient-scope');
    const targetWrapper = document.getElementById('recipient-scope-id-wrapper');
    const targetSelect = document.getElementById('recipient-scope-id');
    const targetLabel = document.getElementById('recipient-scope-id-label');

    function updateRecipientScope() {
        if (!scope || !targetWrapper || !targetSelect || !targetLabel) {
            return;
        }

        const selectedScope = scope.value;
        const needsTarget = selectedScope !== 'global';

        targetWrapper.classList.toggle('hidden', !needsTarget);
        targetSelect.required = needsTarget;
        targetSelect.disabled = !needsTarget;

        targetLabel.textContent = selectedScope === 'team'
            ? 'Select team'
            : 'Select employee';

        Array.from(targetSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.scope !== selectedScope;
        });

        const selectedOption = targetSelect.selectedOptions[0];

        if (
            selectedOption &&
            selectedOption.value &&
            selectedOption.dataset.scope !== selectedScope
        ) {
            targetSelect.value = '';
        }
    }

    if (scope && targetSelect) {
        scope.addEventListener('change', function () {
            targetSelect.value = '';
            updateRecipientScope();
        });

        updateRecipientScope();
    }

    // Weekly delivery day visibility
    const frequencySelect = document.getElementById('report-frequency');
    const weeklyDayWrapper = document.getElementById('weekly-day-wrapper');

    function updateWeeklyDayVisibility() {
        if (!frequencySelect || !weeklyDayWrapper) {
            return;
        }

        weeklyDayWrapper.classList.toggle(
            'hidden',
            frequencySelect.value !== 'weekly'
        );
    }

    if (frequencySelect) {
        frequencySelect.addEventListener('change', updateWeeklyDayVisibility);
        updateWeeklyDayVisibility();
    }
});
</script>
@endsection
