<div id="generalSettingsTab" class="tab-pane">
    <div class="grid grid-cols-12">
        <div class="col-span-12 2xl:col-span-9">
            <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
                General Settings
            </h3>

            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-2">
                    <div class="sm:w-1/2">
                        <label class="text-base font-semibold text-bgray-800 dark:text-white">
                            Default Kanban View
                        </label>
                    </div>
                    <div class="sm:w-1/2 flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="kanban_view" value="agile" class="general-setting h-4 w-4 cursor-pointer text-success-300 focus:ring-0" data-field="kanban_view" data-user="{{ $user->id }}" {{ ($generalSettings->kanban_view ?? 'agile') == 'agile' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Agile</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="kanban_view" value="linear" class="general-setting h-4 w-4 cursor-pointer text-success-300 focus:ring-0" data-field="kanban_view" data-user="{{ $user->id }}" {{ ($generalSettings->kanban_view ?? 'linear') == 'linear' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Linear</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-2">
                    <div class="sm:w-1/2">
                        <label class="text-base font-semibold text-bgray-800 dark:text-white">
                            Default Theme
                        </label>
                    </div>
                    <div class="sm:w-1/2 flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="theme" value="light" class="general-setting h-4 w-4 cursor-pointer text-success-300 focus:ring-0" data-field="theme" data-user="{{ $user->id }}" data-login-user="{{ auth()->user()->id }}" {{ ($generalSettings->theme ?? '') == 'light' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Light</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="theme" value="dark" class="general-setting h-4 w-4 cursor-pointer text-success-300 focus:ring-0" data-field="theme" data-user="{{ $user->id }}" data-login-user="{{ auth()->user()->id }}" {{ ($generalSettings->theme ?? '') == 'dark' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Dark</span>
                        </label>
                    </div>
                </div>

                @if (!empty($userSettings))
                    @php
                        $canEditUser = auth()->user()->can('user.edit');
                    @endphp
                    @foreach ($userSettings as $settingKey => $settingLabel)
                        @php
                            $userSettingRecord = $user->settings->firstWhere('key', $settingKey);
                            $isSettingEnabled = $userSettingRecord ? (bool) $userSettingRecord->value : true;
                        @endphp

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-2">
                            <div class="sm:w-1/2">
                                <label class="text-base font-semibold text-bgray-800 dark:text-white">
                                    {{ $settingLabel }}
                                </label>
                            </div>
                            <div class="sm:w-1/2 flex items-center gap-2">
                                <label class="flex items-center gap-2 {{ $canEditUser ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                    <input type="checkbox" name="{{ $settingKey }}" value="1" class="general-setting h-5 w-5 {{ $canEditUser ? 'cursor-pointer' : 'cursor-not-allowed' }} rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-field="{{ $settingKey }}" data-user="{{ $user->id }}" {{ $isSettingEnabled ? 'checked' : '' }} @disabled(!$canEditUser)>
                                    <span class="text-bgray-700 dark:text-bgray-50">Enabled</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
