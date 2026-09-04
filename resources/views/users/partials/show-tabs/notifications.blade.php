<div id="notificationTab" class="tab-pane">
    <h3 class="mb-5 text-2xl font-bold text-bgray-900 dark:text-white">
        Notification Preferences
    </h3>
    <div class="space-y-5">
        @php
            $userSettings = $user->notificationSettings->keyBy('action');
            $notificationGroupOrder = [
                'Project Management',
                'Task Management',
                'Team & Shift',
                'Requests & Approvals',
            ];
            $groupedNotificationSettings = collect($userNotificationSettings)->groupBy('group');

            $allActions = collect($userNotificationSettings)->pluck('action')->filter()->values();
            $totalActions = $allActions->count();

            $allInAppEnabled = $totalActions > 0 && $allActions->every(function ($act) use ($userSettings) {
                return isset($userSettings[$act]) && (bool) $userSettings[$act]->in_app;
            });

            $allMailEnabled = $totalActions > 0 && $allActions->every(function ($act) use ($userSettings) {
                return isset($userSettings[$act]) && (bool) $userSettings[$act]->mail;
            });
        @endphp

        <div class="!mb-0 grid grid-cols-12 items-center border-b px-4 py-3 font-semibold text-bgray-600 dark:text-white">
            <div class="col-span-6">
                Action
            </div>
            <div class="col-span-3 flex items-center justify-center gap-2">
                <span>In-App</span>
                <input type="checkbox" class="bulk-notification-toggle h-4 w-4 cursor-pointer rounded border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-field="in_app" data-user="{{ $user->id }}" {{ $allInAppEnabled ? 'checked' : '' }}>
            </div>
            <div class="col-span-3 flex items-center justify-center gap-2">
                <span>Email</span>
                <input type="checkbox" class="bulk-notification-toggle h-4 w-4 cursor-pointer rounded border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-field="mail" data-user="{{ $user->id }}" {{ $allMailEnabled ? 'checked' : '' }}>
            </div>
        </div>

        @foreach ($notificationGroupOrder as $notificationGroup)
            @if ($groupedNotificationSettings->has($notificationGroup))
                <div class="!mt-0 border-b border-bgray-200 px-4 py-3 text-base font-semibold text-bgray-900 dark:border-darkblack-400 dark:text-white" style="background-color: rgba(0, 0, 0, 0.02); text-align: center;">
                    {{ $notificationGroup }}
                </div>

                @foreach ($groupedNotificationSettings[$notificationGroup] as $setting)
                    <div class="!mt-0 grid grid-cols-12 items-center border-b border-bgray-200 px-4 dark:border-darkblack-400">
                        <div class="col-span-6 flex min-h-[70px] items-center gap-4">
                            <div class="h-10 w-10">
                                {!! $setting['icon'] !!}
                            </div>
                            <div class="leading-tight">
                                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                    {{ $setting['label'] }}
                                </h4>
                                @if (!empty($setting['subtitle']))
                                    <p class="mt-1 text-sm font-normal leading-5 text-bgray-500 dark:text-bgray-300">
                                        {{ $setting['subtitle'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="col-span-3 flex items-center justify-center">
                            <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->in_app ? 'active' : '' }}" role="switch" data-user="{{ $user->id }}" data-action="{{ $setting['action'] }}" data-field="in_app" aria-checked="{{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->in_app ? 'true' : 'false' }}">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>

                        <div class="col-span-3 flex items-center justify-center">
                            <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->mail ? 'active' : '' }}" role="switch" data-user="{{ $user->id }}" data-action="{{ $setting['action'] }}" data-field="mail" aria-checked="{{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->mail ? 'true' : 'false' }}">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        @endforeach
    </div>
</div>
