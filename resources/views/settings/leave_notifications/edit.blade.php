
@extends('layouts.master')

@section('page-content')

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

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-bgray-900 dark:text-white">
                        Leave Notification Recipients
                    </h2>

                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                        Configure the email addresses that should receive leave-related notifications.
                        You can select PMS users and add external email addresses.
                    </p>
                </div>

                <form action="{{ route('settings.leave-notifications.update') }}"
                      method="POST"
                      class="space-y-6">

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
                                <label for="leave-notifications-enabled"
                                       class="text-sm font-medium text-bgray-800 dark:text-white">
                                    Enable Leave Notifications
                                </label>

                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                    Turn this off to disable notifications sent to the configured recipient list.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Notification Recipients --}}
                    <div>
                        <label for="leave-notification-recipients"
                            class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                            Notification Recipients
                        </label>

                        <select
                            name="recipients[]"
                            id="leave-notification-recipients"
                            class="w-full tom-select-multiple "
                            multiple
                        >
                            @foreach ($users as $user)
                                <option
                                    value="user:{{ $user->id }}"
                                    @selected(
                                        in_array(
                                            'user:' . $user->id,
                                            old('recipients', $selectedRecipients)
                                        )
                                    )
                                >
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach

                            @foreach (old('recipients', $selectedRecipients) as $recipient)
                                @if (str_starts_with($recipient, 'email:'))
                                    <option
                                        value="{{ $recipient }}"
                                        selected
                                    >
                                        {{ substr($recipient, 6) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>

                        <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                            Search for a PMS user by name or email, or type an external email
                            address and press Enter to add it. You can select multiple recipients.
                        </p>

                        @error('recipients')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        @error('recipients.*')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Save --}}
                    <div class="flex justify-end border-t border-bgray-200 pt-5 dark:border-darkblack-400">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400"
                        >
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
    const element = document.getElementById(
        'leave-notification-recipients'
    );

    if (!element) return;

    if (element.tomselect) {
        element.tomselect.destroy();
    }

    if (typeof TomSelect === 'undefined') {
        console.error('Tom Select JavaScript is not loaded.');
        return;
    }

    new TomSelect(element, {
        plugins: ['remove_button'],
        create: function (input) {
            const email = input.trim().toLowerCase();
            const validEmail =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

            if (!validEmail) return false;

            return {
                value: 'email:' + email,
                text: email
            };
        },
        createOnBlur: false,
        persist: false,
        maxOptions: 100,
        placeholder: 'Search users or type an email address...',
        closeAfterSelect: false
    });
});
</script>
@endpush
