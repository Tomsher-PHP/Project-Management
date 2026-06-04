<div id="generalSettingsTab" class="tab-pane">
    <div class="grid grid-cols-12">
        <div class="col-span-12 border-bgray-300 2xl:col-span-9 2xl:border-r">
            <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
                General Settings
            </h3>

            <div class="space-y-10">
                <div class="flex flex-col gap-4">
                    <label class="text-lg font-semibold text-bgray-800 dark:text-white">
                        Default Kanban View
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="kanban_view" value="agile" class="general-setting h-4 w-4 text-success-300 focus:ring-0" data-field="kanban_view" data-user="{{ $user->id }}" {{ ($generalSettings->kanban_view ?? 'agile') == 'agile' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Agile</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="radio" name="kanban_view" value="linear" class="general-setting h-4 w-4 text-success-300 focus:ring-0" data-field="kanban_view" data-user="{{ $user->id }}" {{ ($generalSettings->kanban_view ?? 'linear') == 'linear' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Linear</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <label class="text-lg font-semibold text-bgray-800 dark:text-white">
                        Default Theme
                    </label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="theme" value="light" class="general-setting h-4 w-4 text-success-300 focus:ring-0" data-field="theme" data-user="{{ $user->id }}" data-login-user="{{ auth()->user()->id }}" {{ ($generalSettings->theme ?? '') == 'light' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Light</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="radio" name="theme" value="dark" class="general-setting h-4 w-4 text-success-300 focus:ring-0" data-field="theme" data-user="{{ $user->id }}" data-login-user="{{ auth()->user()->id }}" {{ ($generalSettings->theme ?? '') == 'dark' ? 'checked' : '' }}>
                            <span class="text-bgray-700 dark:text-bgray-50">Dark</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
