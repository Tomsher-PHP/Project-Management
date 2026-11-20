{{-- resources/views/settings/daily-work-reports/partials/user-settings.blade.php --}}

@php
    $teamIdsByUser = [];

    foreach ($teams as $team) {
        foreach ($team->users as $teamUser) {
            $teamIdsByUser[$teamUser->id][] = $team->id;
        }
    }
@endphp

<div class="space-y-5">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="font-semibold text-bgray-900 dark:text-white">
                Employee Report Preferences
            </h2>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                Enable or disable {{ $currentReport['label'] ?? 'this report' }}
                for individual employees. Employees are enabled by default.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-300 dark:bg-success-900/30 dark:text-success-300">
                Enabled by default
            </span>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-bgray-600 dark:bg-gray-800 dark:text-bgray-900">
                {{ $users->count() }} employees
            </span>
        </div>
    </div>

    {{-- Search and team filter --}}
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

        <div>
            <label for="report-user-search" class="mb-1 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                Search employee
            </label>

            <input type="search" id="report-user-search" placeholder="Search by employee name or email..." class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
        </div>

        <div>
            <label for="report-team-filter" class="mb-1 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                Filter by team
            </label>

            <select id="report-team-filter" class="tom-select-no-search w-full">
                <option value="">All teams</option>

                @foreach ($teams as $team)
                    <option value="{{ $team->id }}">
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Bulk update --}}
    @can($reportPermission)
        <form id="bulk-user-preferences-form" method="POST" action="{{ route('settings.daily-work-reports.users.preferences.bulk') }}" class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-darkblack-500 sm:flex-row sm:items-center sm:justify-between">
            @csrf
            @method('PATCH')

            <input type="hidden" name="report_key" value="{{ $reportKey }}">

            <div>
                <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                    Bulk employee settings
                </p>
                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                    Select employees below and apply one action.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" name="is_enabled" value="1" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white hover:bg-success-400">
                    Enable selected
                </button>

                <button type="submit" name="is_enabled" value="0" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-bgray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-bgray-200 dark:hover:bg-darkblack-400">
                    Disable selected
                </button>
            </div>
        </form>
    @endcan

    {{-- Employee table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">

        <table class="w-full text-left text-sm">

            <thead class="bg-gray-50 text-bgray-600 dark:bg-gray-800 dark:text-bgray-300">
                <tr>
                    @can($reportPermission)
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox" id="select-all-report-users" class="rounded border-gray-300 text-blue-600" aria-label="Select all employees">
                        </th>
                    @endcan

                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Teams</th>
                    <th class="px-4 py-3">Report status</th>
                </tr>
            </thead>

            <tbody id="report-user-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($users as $user)
                    @php
                        $preference = $user->reportPreferences->firstWhere('report_key', $reportKey);

                        // Missing preference means enabled.
                        $isEnabled = $preference?->is_enabled ?? true;

                        $preferenceToggleModel = (object) [
                            'id' => $user->id,
                            'is_active' => (bool) $isEnabled,
                        ];

                        $userTeamIds = $teamIdsByUser[$user->id] ?? [];
                        $userTeamNames = $teams->whereIn('id', $userTeamIds)->pluck('name')->implode(', ');
                    @endphp

                    <tr class="report-user-row" data-user-name="{{ strtolower($user->name) }}" data-user-email="{{ strtolower($user->email ?? '') }}" data-team-ids="{{ implode(',', $userTeamIds) }}">

                        @can($reportPermission)
                            <td class="px-4 py-3">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" form="bulk-user-preferences-form" class="report-user-checkbox rounded border-gray-300 text-blue-600" aria-label="Select {{ $user->name }}">
                            </td>
                        @endcan

                        <td class="px-4 py-3 font-medium text-bgray-900 dark:text-white">
                            {{ $user->name }}
                        </td>

                        <td class="px-4 py-3 text-bgray-600 dark:text-bgray-300">
                            {{ $user->email ?: '—' }}
                        </td>

                        <td class="px-4 py-3 text-bgray-600 dark:text-bgray-300">
                            {{ $userTeamNames ?: '—' }}
                        </td>

                        <td class="px-4 py-3">
                            @can($reportPermission)
                                <x-status-toggle :model="$preferenceToggleModel" route="settings.daily-work-reports.users.preference" entity="user-report-preference" permission="report_settings.manage" />
                            @else
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $isEnabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-bgray-600 dark:bg-gray-800 dark:text-bgray-300' }}">
                                    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            @endcan
                        </td>



                    </tr>

                @empty
                    <tr>
                        <td colspan="@can($reportPermission) 5 @else 4 @endcan" class="px-4 py-8 text-center text-bgray-500 dark:text-bgray-400">
                            No employees found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p id="report-user-no-results" class="hidden px-4 py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
            No employees match the selected filters.
        </p>
    </div>
</div>
