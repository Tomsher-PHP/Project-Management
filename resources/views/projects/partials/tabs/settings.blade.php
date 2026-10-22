@php
    $canEdit = auth()->user()->can('project.edit') && !$project->trashed();
    $notificationTypes = $notificationTypes ?? config('project_constants.project_notification_types', []);
    $preferences = $preferences ?? $project->notificationPreferences()->get()->keyBy('notification_type');
@endphp

<div class="space-y-6">
    <div class="rounded-xl border border-bgray-200 bg-white p-6 dark:border-darkblack-400 dark:bg-darkblack-600">
        <form id="project-notification-settings-form" action="{{ route('projects.settings.notifications.update', $project) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sub heading for this section "Notifications" -->
            <div class="mb-6 flex flex-col gap-1 border-b border-bgray-200 pb-4 dark:border-darkblack-400">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                    Notifications
                </h3>
            </div>

            <!-- Notifications list from config/project_constants.php -->
            <div class="divide-y divide-bgray-200 rounded-xl border border-bgray-200 dark:divide-darkblack-400 dark:border-darkblack-400">
                @foreach ($notificationTypes as $typeKey => $typeLabel)
                    @php
                        $preference = $preferences->get($typeKey);
                        $isEnabled = $preference !== null ? (bool) $preference->is_enabled : true;
                    @endphp
                    <div class="flex items-center justify-between p-4 sm:p-5 transition hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/50">
                        <div class="pr-4">
                            <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                {{ $typeLabel }}
                            </h4>
                            <p class="mt-0.5 text-xs text-bgray-700 dark:text-bgray-300">
                                @if ($typeKey === 'timeline_ending_soon')
                                    Receive alerts when project timelines are approaching their end dates.
                                @elseif ($typeKey === 'hosting_ending_soon')
                                    Receive alerts when server and hosting subscriptions are expiring.
                                @else
                                    Receive notifications for {{ strtolower($typeLabel) }}.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="notifications[{{ $typeKey }}]" id="notification_input_{{ $typeKey }}" value="{{ $isEnabled ? '1' : '0' }}">
                            <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $isEnabled ? 'active' : '' }} @if (!$canEdit) opacity-50 cursor-not-allowed @endif" role="switch" aria-checked="{{ $isEnabled ? 'true' : 'false' }}" data-notification-switch data-target-input="notification_input_{{ $typeKey }}" @if (!$canEdit) disabled @endif>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($canEdit)
                <!-- Save settings button -->
                <div class="mt-6 flex justify-end">
                    <button type="submit" id="save-project-settings-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400 disabled:opacity-50">
                        <svg class="h-4 w-4 animate-spin hidden" id="save-settings-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="save-settings-btn-text">Save Settings</span>
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>
