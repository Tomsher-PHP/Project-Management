@extends('layouts.master')

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                @include('settings.project-tabs', ['currentTab' => $currentTab])

                <div class="mt-4">
                    <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">Timeline Ending Soon Notification</h3>
                    <form action="{{ $setting->exists ? route('settings.project-notifications.update', $setting->id) : route('settings.project-notifications.store') }}" method="POST">
                        @csrf
                        @if ($setting->exists)
                            @method('PUT')
                        @endif

                        <div class="mb-6 grid grid-cols-3 items-end gap-5 md:grid-cols-12">

                            <!-- Enable Notification -->
                            <div class="md:col-span-3">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Enable Notification
                                </label>

                                <div class="flex h-[42px] items-center">
                                    <input type="hidden" name="is_enabled" id="is_enabled_hidden" value="{{ $setting->is_enabled ? '1' : '0' }}">

                                    <button type="button" id="is_enabled_toggle" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ $setting->is_enabled ? 'active' : '' }}" role="switch" aria-checked="{{ $setting->is_enabled ? 'true' : 'false' }}" onclick="toggleIsEnabled(this)">
                                        <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Notify Before -->
                            <div class="md:col-span-3">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Notify before (days) <x-red-star />
                                </label>

                                <input type="number" name="days_before" value="{{ $setting->days_before }}" min="0" required class="h-[42px] w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                @error('days_before')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Recipients -->
                            <div class="md:col-span-6">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Recipients (Users)
                                </label>

                                <select name="user_ids[]" id="user_ids" multiple class="tom-select-multiple w-full border-gray-300 dark:border-darkblack-400" data-placeholder="Select recipients...">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" {{ in_array($user->id, $selectedUsers) ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('user_ids')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        @if (($setting->exists && auth()->user()->can($editPermission)) || (!$setting->exists && auth()->user()->can($createPermission)))
                            <div class="flex justify-end">
                                <button type="submit" class="rounded-lg bg-success-300 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                                    Save Settings
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </section>
    </div>

    <script>
        function toggleIsEnabled(button) {
            setTimeout(() => {
                const isActive = button.classList.contains('active');
                button.setAttribute('aria-checked', isActive);
                document.getElementById('is_enabled_hidden').value = isActive ? '1' : '0';
            }, 10);
        }
    </script>
@endsection
