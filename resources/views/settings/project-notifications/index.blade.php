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
                    <form action="{{ $setting->exists ? route('settings.project-notifications.update', $setting->id) : route('settings.project-notifications.store') }}" method="POST">
                        @csrf
                        @if ($setting->exists)
                            @method('PUT')
                        @endif

                        <!-- Heading with Enable Toggle aligned next to it -->
                        <div class="mb-6 flex items-center gap-3">
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Timeline Ending Soon Notification</h3>

                            <input type="hidden" name="is_enabled" id="is_enabled_hidden" value="{{ $setting->is_enabled ? '1' : '0' }}">

                            <button type="button" id="is_enabled_toggle" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ $setting->is_enabled ? 'active' : '' }}" role="switch" aria-checked="{{ $setting->is_enabled ? 'true' : 'false' }}" onclick="toggleIsEnabled(this)">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>

                        <!-- 3 fields in a single row using grid layout matching server hosting notifications -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                            <!-- Notify Before (Multiple Intervals) -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label class="block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Notify before (days)
                                    </label>
                                    <button type="button" id="add-interval-btn" class="inline-flex items-center gap-1 text-xs font-semibold text-success-300 hover:text-success-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Add Interval</span>
                                    </button>
                                </div>

                                <div id="interval-container" class="space-y-2">
                                    @php
                                        $submittedDays = old('days_before', $selectedDays);
                                        if (empty($submittedDays)) {
                                            $submittedDays = [3];
                                        }
                                    @endphp
                                    @foreach ($submittedDays as $index => $day)
                                        <div class="interval-row flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <input type="number" name="days_before[]" value="{{ $day }}" min="0" placeholder="e.g. 15" required class="h-[42px] w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            </div>
                                            @if ($index === 0)
                                                <span class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-transparent text-bgray-300 dark:text-bgray-600" title="Primary reminder interval cannot be removed">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                </span>
                                            @else
                                                <button type="button" class="remove-interval-btn inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 text-bgray-500 hover:bg-gray-100 hover:text-error-300 dark:border-darkblack-400 dark:text-bgray-400 dark:hover:bg-darkblack-500" title="Remove interval">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <span class="mt-1 block text-xs text-bgray-500 dark:text-bgray-400">
                                    Primary reminder interval required. Maximum 5 additional intervals (up to 6 total).
                                </span>

                                @error('days_before')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                                @foreach ($errors->get('days_before.*') as $errorList)
                                    @foreach ($errorList as $errorMessage)
                                        <span class="mt-1 block text-sm text-error-300">{{ $errorMessage }}</span>
                                    @endforeach
                                @endforeach
                            </div>

                            <!-- Recipients (PMS Users) -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Recipients (Users)
                                </label>

                                <select name="user_ids[]" id="user_ids" multiple class="tom-select-multiple w-full border-gray-300 dark:border-darkblack-400" data-placeholder="Select recipients...">
                                    @php
                                        $currentSelectedUsers = old('user_ids', $selectedUsers);
                                    @endphp
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" {{ in_array($user->id, $currentSelectedUsers) ? 'selected' : '' }} @if(!empty($user->email)) data-subtype="{{ $user->email }}" @endif>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('user_ids')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                                @error('user_ids.*')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Additional Email Recipients (Textarea) -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Additional Email Recipients
                                </label>

                                <textarea name="emails" id="emails" rows="4" placeholder="Enter one email address per line&#10;admin@example.com&#10;accounts@example.com" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ old('emails', $selectedEmails) }}</textarea>

                                <span class="mt-1 block text-xs text-bgray-500 dark:text-bgray-400">
                                    Enter one email address per line.
                                </span>

                                @error('emails')
                                    <span class="mt-1 block text-sm text-error-300">{{ $message }}</span>
                                @enderror
                                @foreach ($errors->get('emails.*') as $errorList)
                                    @foreach ($errorList as $errorMessage)
                                        <span class="mt-1 block text-sm text-error-300">{{ $errorMessage }}</span>
                                    @endforeach
                                @endforeach
                            </div>

                        </div>

                        <!-- Project Notification Defaults Section -->
                        <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">Project Notification Defaults</h4>
                            <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
                                Default notification behavior configured for new or uncustomized projects:
                            </p>

                            @php
                                $agileDefaultRecord = $timelineDefaults->first(fn($d) => ($d->conditions['project_flow'] ?? null) === 'agile');
                                $linearDefaultRecord = $timelineDefaults->first(fn($d) => ($d->conditions['project_flow'] ?? null) === 'linear');

                                $agileEnabled = old('defaults.agile', !empty($agileDefaultRecord->setting_value['is_enabled']));
                                $linearEnabled = old('defaults.linear', !empty($linearDefaultRecord->setting_value['is_enabled']) || is_null($linearDefaultRecord));
                            @endphp

                            <div class="mt-4 space-y-3">
                                <!-- Agile Project Flow Default -->
                                <div class="flex items-center justify-between rounded-lg bg-white p-3 dark:bg-darkblack-600">
                                    <div>
                                        <span class="text-sm font-semibold text-bgray-900 dark:text-white">Project flow: Agile</span>
                                        <p class="text-xs text-bgray-500 dark:text-bgray-400">Timeline ending notifications default for agile projects</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="defaults[agile]" id="default_agile_hidden" value="{{ $agileEnabled ? '1' : '0' }}">
                                        <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ $agileEnabled ? 'active' : '' }}" role="switch" aria-checked="{{ $agileEnabled ? 'true' : 'false' }}" onclick="toggleDefaultSwitch(this, 'default_agile_hidden')">
                                            <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Linear Project Flow Default -->
                                <div class="flex items-center justify-between rounded-lg bg-white p-3 dark:bg-darkblack-600">
                                    <div>
                                        <span class="text-sm font-semibold text-bgray-900 dark:text-white">Project flow: Linear</span>
                                        <p class="text-xs text-bgray-500 dark:text-bgray-400">Timeline ending notifications default for linear projects</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="defaults[linear]" id="default_linear_hidden" value="{{ $linearEnabled ? '1' : '0' }}">
                                        <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors {{ $linearEnabled ? 'active' : '' }}" role="switch" aria-checked="{{ $linearEnabled ? 'true' : 'false' }}" onclick="toggleDefaultSwitch(this, 'default_linear_hidden')">
                                            <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (($setting->exists && auth()->user()->can($editPermission)) || (!$setting->exists && auth()->user()->can($createPermission)))
                            <div class="mt-6 flex justify-end">
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

        function toggleDefaultSwitch(button, hiddenInputId) {
            setTimeout(() => {
                const isActive = button.classList.contains('active');
                button.setAttribute('aria-checked', isActive);
                const hiddenInput = document.getElementById(hiddenInputId);
                if (hiddenInput) {
                    hiddenInput.value = isActive ? '1' : '0';
                }
            }, 10);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('interval-container');
            const addBtn = document.getElementById('add-interval-btn');
            const MAX_TOTAL = 6; // 1 primary + 5 additional max

            function updateAddBtnState() {
                if (!container || !addBtn) return;
                const totalRows = container.querySelectorAll('.interval-row').length;
                if (totalRows >= MAX_TOTAL) {
                    addBtn.disabled = true;
                    addBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    addBtn.disabled = false;
                    addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            if (addBtn && container) {
                updateAddBtnState();

                addBtn.addEventListener('click', function () {
                    const currentRows = container.querySelectorAll('.interval-row').length;
                    if (currentRows >= MAX_TOTAL) {
                        alert('You can add a maximum of 5 additional reminder intervals (6 total).');
                        return;
                    }

                    const row = document.createElement('div');
                    row.className = 'interval-row flex items-center gap-2';
                    row.innerHTML = `
                        <div class="relative flex-1">
                            <input type="number" name="days_before[]" value="" min="0" placeholder="e.g. 15" required class="h-[42px] w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        </div>
                        <button type="button" class="remove-interval-btn inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 text-bgray-500 hover:bg-gray-100 hover:text-error-300 dark:border-darkblack-400 dark:text-bgray-400 dark:hover:bg-darkblack-500" title="Remove interval">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    `;
                    container.appendChild(row);
                    row.querySelector('input').focus();
                    updateAddBtnState();
                });

                container.addEventListener('click', function (e) {
                    const removeBtn = e.target.closest('.remove-interval-btn');
                    if (removeBtn) {
                        const row = removeBtn.closest('.interval-row');
                        if (row) {
                            row.remove();
                            updateAddBtnState();
                        }
                    }
                });
            }
        });
    </script>
@endsection
