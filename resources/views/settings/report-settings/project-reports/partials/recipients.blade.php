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
                        @include('settings.report-settings.project-reports.partials.external-recipient-rows', ['externalRecipients' => $externalRecipients])
                    </div>
                    <template id="external-recipient-row-template">
                        @include('settings.report-settings.project-reports.partials.external-recipient-row', ['idx' => '__INDEX__', 'ext' => null])
                    </template>
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
                        @include('settings.report-settings.project-reports.partials.recipient-table-rows', [
                            'recipients' => $recipients,
                            'configuration' => $selectedConfiguration,
                            'reportPermission' => $reportPermission,
                        ])
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

