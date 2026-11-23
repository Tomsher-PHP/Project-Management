<!-- RECIPIENTS TAB -->
<section id="report-panel-recipients" data-report-panel="recipients" class="report-panel hidden p-5">
    @can($reportPermission)
        <form method="POST" action="{{ route('settings.daily-work-reports.recipients.store') }}" class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            @csrf
            <input type="hidden" name="report_key" value="{{ $reportKey }}">

            <h2 class="mb-4 font-semibold text-bgray-900 dark:text-white">
                Add recipient
            </h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="recipient-scope" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                        Recipient scope
                    </label>
                    <select name="scope" id="recipient-scope" required class="tom-select-no-search w-full">
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
                    <label id="recipient-scope-id-label" for="recipient-scope-id" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                        Select target
                    </label>

                    <select name="scope_id" id="recipient-scope-id" class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" disabled>
                        <option value="">Select...</option>

                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" data-scope="team" @selected(old('scope') === 'team' && (string) old('scope_id') === (string) $team->id)>
                                {{ $team->name }}
                            </option>
                        @endforeach

                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" data-scope="user" @selected(old('scope') === 'user' && (string) old('scope_id') === (string) $user->id)>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="recipient-name" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                        Recipient name
                    </label>
                    <input id="recipient-name" name="name" type="text" maxlength="255" value="{{ old('name') }}" class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Optional">
                </div>

                <div>
                    <label for="recipient-email" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                        Email address
                    </label>
                    <input id="recipient-email" name="email" type="email" maxlength="254" value="{{ old('email') }}" required class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="name@company.com">
                </div>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-bgray-700 dark:text-bgray-300">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', true)) class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                Active
            </label>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
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
                                    <x-status-toggle :model="$recipient" route="settings.daily-work-reports.recipients.toggle" :route-parameters="['recipient' => $recipient->id]" entity="daily-work-report-recipient" />
                                </td>

                                @can($reportPermission)
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0 text-center">
                                        <form method="POST" action="{{ route('settings.daily-work-reports.recipients.destroy', $recipient) }}" onsubmit="return confirm('Remove this recipient?')">
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
                                <td colspan="@can($reportPermission) 6 @else 5 @endcan" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
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

