<!-- USER SETTINGS TAB -->
<section id="report-panel-users" data-report-panel="users" class="report-panel hidden p-5">
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

        
        {{-- Compact bulk employee actions --}}
        @can($reportPermission)
            <form id="bulk-user-preferences-form" method="POST" action="{{ route('settings.daily-work-reports.users.preferences.bulk') }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-darkblack-500">
                @csrf
                @method('PATCH')

                <input type="hidden" name="report_key" value="{{ $reportKey }}">

                <div class="mr-2 flex items-center gap-2">
                    <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                        Bulk actions
                    </span>
                    <span
                        id="report-selected-count"
                        class="rounded-md bg-white px-2 py-1 text-xs text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300"
                    >
                        0 selected
                    </span>
                </div>

                {{-- Report type --}}
                <select
                    name="preference_type"
                    aria-label="Report preference type"
                    class="tom-select min-w-[170px]"
                >
                    <option value="consolidated">Consolidated report</option>
                    <option value="individual">Individual report</option>
                </select>

                {{-- Action --}}
                <select
                    name="is_enabled"
                    aria-label="Action"
                    class="tom-select min-w-[110px]"
                >
                    <option value="1">Enable</option>
                    <option value="0">Disable</option>
                </select>

                {{-- Apply --}}
                <button type="submit" id="apply-report-bulk-action" disabled class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Apply
                </button>
            </form>
        @endcan




        {{-- Employee table --}}
        <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-col space-y-5">
                <div class="table-content w-full overflow-x-auto">
                    <table class="w-full text-left" id="report-user-table">
                        <thead>
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                @can($reportPermission)
                                    <th class="w-12 px-4 py-4 whitespace-nowrap">
                                        <input type="checkbox" id="select-all-report-users" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" aria-label="Select all employees">
                                    </th>
                                @endcan

                                <th class="px-4 py-4 whitespace-nowrap min-w-[200px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Employee</span></th>
                                <th class="px-4 py-4 whitespace-nowrap min-w-[180px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</span></th>
                                <th class="px-4 py-4 whitespace-nowrap min-w-[160px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Teams</span></th>
                                <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Consolidated Report Details</span></th>
                                <th class="px-4 py-4 whitespace-nowrap min-w-[160px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Individual Report Email</span></th>
                            </tr>
                        </thead>

                        <tbody id="report-user-table-body" class="divide-y divide-bgray-200 dark:divide-darkblack-400">
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
                                    $isIndividualReportEnabled = $preference?->receive_individual_report ?? true;
                                @endphp

                                <tr class="report-user-row border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500" data-user-name="{{ strtolower($user->name) }}" data-user-email="{{ strtolower($user->email ?? '') }}" data-team-ids="{{ implode(',', $userTeamIds) }}">
                                    @can($reportPermission)
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" form="bulk-user-preferences-form" class="report-user-checkbox h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" aria-label="Select {{ $user->name }}">
                                        </td>
                                    @endcan

                                    <td class="px-4 py-4">
                                        <span class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                            {{ $user->name }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                        {{ $user->email ?: '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                        {{ $userTeamNames ?: '—' }}
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @can($reportPermission)
                                            <x-status-toggle :model="$preferenceToggleModel" route="settings.daily-work-reports.users.preference" entity="user-report-preference" permission="report_settings.manage" />
                                        @else
                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                            {{ $isEnabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-bgray-600 dark:bg-gray-800 dark:text-bgray-300' }}">
                                                {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        @endcan
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @can($reportPermission)
                                            @php
                                                $individualPreferenceToggleModel = (object) [
                                                    'id' => $user->id,
                                                    'is_active' => (bool) $isIndividualReportEnabled,
                                                ];
                                            @endphp

                                            <x-status-toggle
                                                :model="$individualPreferenceToggleModel"
                                                route="settings.daily-work-reports.users.individual-preference"
                                                entity="user-individual-report-preference"
                                                permission="report_settings.manage"
                                            />
                                        @else
                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                                {{ $isIndividualReportEnabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-bgray-600 dark:bg-gray-800 dark:text-bgray-300' }}">
                                                {{ $isIndividualReportEnabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="@can($reportPermission) 6 @else 5 @endcan"
                                        class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                        No employees found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p id="report-user-no-results" class="hidden px-4 py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                    No employees match the selected filters.
                </p>
            </div>
        </div>
</section>
