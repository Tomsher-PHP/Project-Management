@extends('layouts.master')

@section('page-content')

    @php
        $currentRecipients = old('recipients', $selectedRecipients ?? []);

        $selectedExternalEmails = collect($currentRecipients)
            ->filter(fn ($recipient) => str_starts_with($recipient, 'email:'))
            ->map(fn ($recipient) => substr($recipient, 6))
            ->implode("\n");

        $externalEmails = old('external_emails', $selectedExternalEmails);
    @endphp

    {{-- Page Header --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                {{-- Leave Settings Tabs --}}
                @include('settings.leave_settings.leave_tabs', [
                    'currentTab' => 'notifications'
                ])

                {{-- Page Heading --}}
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-bgray-900 dark:text-white">
                        Leave Notification Recipients
                    </h2>

                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                        Configure additional recipients for leave-related notifications.
                        Select internal PMS users and enter external email addresses separately.
                    </p>
                </div>

                <form
                    id="leave-notification-form"
                    action="{{ route('settings.leave-notifications.update') }}"
                    method="POST"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    {{-- Enable / Disable Notifications --}}
                    <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                        <div class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                id="leave-notifications-enabled"
                                name="is_enabled"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300"
                                {{ old('is_enabled', $setting->is_enabled) ? 'checked' : '' }}
                            >

                            <div>
                                <label
                                    for="leave-notifications-enabled"
                                    class="text-sm font-medium text-bgray-800 dark:text-white"
                                >
                                    Enable Leave Notifications
                                </label>

                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                    Enable or disable notifications to the additional recipients
                                    configured below.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Recipient Settings --}}
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        {{-- Internal PMS Users --}}
                        <div>
                            <label
                                for="leave-notification-users"
                                class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white"
                            >
                                Recipients (PMS Users)
                            </label>

                            <select
                                name="recipients[]"
                                id="leave-notification-users"
                                multiple
                                class="tom-select-multiple w-full"
                                data-placeholder="Select PMS users..."
                            >
                                @foreach ($users as $user)
                                    <option
                                        value="user:{{ $user->id }}"
                                        @selected(
                                            in_array(
                                                'user:' . $user->id,
                                                $currentRecipients
                                            )
                                        )
                                    >
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-2 text-xs text-bgray-500 dark:text-bgray-400">
                                Search by name or email and select one or more PMS users.
                            </p>
                        </div>

                        {{-- External Email Addresses --}}
                        <div>
                            <label
                                for="leave-notification-external-emails"
                                class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white"
                            >
                                Additional Email Recipients
                            </label>

                            <textarea
                                id="leave-notification-external-emails"
                                name="external_emails"
                                rows="5"
                                placeholder="Enter one email address per line&#10;hr@example.com&#10;accounts@example.com"
                                class="w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                            >{{ $externalEmails }}</textarea>

                            <p class="mt-2 text-xs text-bgray-500 dark:text-bgray-400">
                                Enter one external email address per line. These recipients do not
                                need a PMS account.
                            </p>

                            @error('external_emails')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Validation Errors --}}
                    @error('recipients')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    @error('recipients.*')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Save --}}
                    <div class="flex justify-end border-t border-bgray-200 pt-5 dark:border-darkblack-400">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Settings
                        </button>
                    </div>

                </form>
            </div>
        </section>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('leave-notification-form');
    const usersElement = document.getElementById('leave-notification-users');
    const externalEmailsElement = document.getElementById(
        'leave-notification-external-emails'
    );

    if (!form || !usersElement || !externalEmailsElement) {
        return;
    }

    // Initialize Tom Select for internal PMS users.
    if (usersElement.tomselect) {
        usersElement.tomselect.destroy();
    }

    if (typeof TomSelect !== 'undefined') {
        new TomSelect(usersElement, {
            plugins: ['remove_button'],
            persist: false,
            maxOptions: 100,
            placeholder: 'Select PMS users...',
            closeAfterSelect: false
        });
    } else {
        console.error('Tom Select JavaScript is not loaded.');
    }

    // Keep the existing backend recipients[] format.
    // External email addresses are submitted as email:address.
    form.addEventListener('submit', function () {
        form.querySelectorAll('[data-external-recipient]').forEach(function (input) {
            input.remove();
        });

        const emails = externalEmailsElement.value
            .split(/[\n,;]+/)
            .map(email => email.trim().toLowerCase())
            .filter(Boolean);

        const uniqueEmails = [...new Set(emails)];

        uniqueEmails.forEach(function (email) {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = 'recipients[]';
            input.value = 'email:' + email;
            input.dataset.externalRecipient = 'true';

            form.appendChild(input);
        });
    });
});
</script>
@endpush
