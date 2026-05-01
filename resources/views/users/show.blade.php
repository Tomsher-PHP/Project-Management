@extends('layouts.master')
@section('page-content')
<!-- layout start -->
<main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
    <div x-data="{ openEdit: false }"
     x-on:close-edit-modal.window="openEdit = false"
     x-cloak>


        <div class="grid grid-cols-1 rounded-xl bg-white dark:bg-darkblack-600 xl:grid-cols-12">
            <!-- Sidebar -->
            <aside class="col-span-3 border-r border-bgray-200 dark:border-darkblack-400">
                <!-- Sidebar Tabs -->

                <div class="px-4 py-6">
                    <!-- TOP ACTIONS -->
                    <div class="flex items-center justify-between mb-4">
                        <!-- BACK BUTTON -->
                        <x-back-button :url="session('users_return_url', route('users.index'))" />

                        <!-- EDIT BUTTON (opens modal) -->
                        <x-modal-button @click="openEdit = true" />
                    </div>
                    <!-- user profile -->
                    <div class="col-span-12 xl:col-span-4">
                        <div class="rounded-xl p-6 text-center">
                            <img src="{{ $user->profileImageUrl ?? asset('images/default-user.png') }}"
                                class="w-24 h-24 rounded-full mx-auto object-cover mb-4" />
                            <h4 class="col-span-full text-xl font-bold text-gray-800 mb-1 dark:border-darkblack-400 dark:text-white">
                                {{ $user->name }}
                            </h4>
                            <p class="text-gray-500 dark:text-white">
                                {{ $user->details->designation->name ?? '' }}
                            </p>
                        </div>
                    </div>
                    <!-- overview -->
                    <div class="tab active flex gap-x-4 rounded-lg p-4 transition-all" data-tab="overViewTab">
                        <div
                            class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all dark:bg-darkblack-500">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="12" cy="17.5" rx="7" ry="3.5" stroke="currentColor" stroke-width="1.5"
                                    stroke-linejoin="round" />
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Overview
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Summary of user profile and organizational details
                            </p>
                        </div>
                    </div>
                    <!-- notifications -->
                    <div class="tab flex gap-x-4 rounded-lg p-4 transition-all" data-tab="notificationTab">
                        <div
                            class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.0717 4.06949C8.26334 4.49348 6.01734 6.81294 5.67964 9.79403L5.33476 12.8385C5.24906 13.595 4.94246 14.3069 4.45549 14.88C3.42209 16.0964 4.26081 18 5.83014 18H18.1699C19.7392 18 20.5779 16.0964 19.5445 14.88C19.0575 14.3069 18.7509 13.595 18.6652 12.8385L18.4373 10.8267M15 20C14.5633 21.1652 13.385 22 12 22C10.615 22 9.43668 21.1652 9 20M20 5C20 6.65685 18.6569 8 17 8C15.3431 8 14 6.65685 14 5C14 3.34315 15.3431 2 17 2C18.6569 2 20 3.34315 20 5Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Notification manager
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Manage user notifications and project-related alerts
                            </p>
                        </div>
                    </div>
                    <!-- general settings -->
                    <div data-tab="generalSettingsTab" class="tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div
                            class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M2 6H6M2 12H6M2 18H6M18 6L10 6M14 10L10 10M8 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2H8C5.79086 2 4 3.79086 4 6V18C4 20.2091 5.79086 22 8 22Z"
                                    stroke-width="1.5" stroke-linecap="round" stroke="currentColor" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                General Settings
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Configure user preferences and account settings
                            </p>
                        </div>
                    </div>
                    <!-- change password -->
                    <div data-tab="changePasswordTab" class="tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div
                            class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M15 12H18M18 12C18 13.6569 16.6569 15 15 15H9C7.34315 15 6 13.6569 6 12V6C6 4.34315 7.34315 3 9 3H15C16.6569 3 18 4.34315 18 6V12Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Change Password
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Update your account password
                            </p>
                        </div>
                    </div>
                </div>

                @php
                $profile = $user->profileCompletion();
                @endphp
                <!-- Progressbar -->
                <div class="px-8 pb-6">
                    <div class="rounded-xl bg-bgray-200 p-7 dark:bg-darkblack-500">
                        <div class="flex flex-row items-center space-x-3 md:flex-col md:space-x-0 2xl:flex-row 2xl:space-x-3">
                            <div class="progess-bar mb-0 flex justify-center md:mb-[13px] xl:mb-0">
                                <div class="bonus-per relative">
                                    <div class="bonus-outer">
                                        <div class="bonus-inner">
                                            <div class="number">
                                                <span class="text-sm font-medium text-bgray-900">{{ $profile['percentage'] }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="80px" height="80px">
                                        @php
                                        $percentage = $profile['percentage'];
                                        $circumference = 2 * 3.1416 * 35; // r = 35
                                        $offset = $circumference - ($circumference * $percentage / 100);
                                        @endphp

                                        <circle
                                            style="stroke-dashoffset: {{ $offset }};"
                                            cx="40"
                                            cy="40"
                                            r="35"
                                            stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex flex-col items-start md:items-center xl:items-start">
                                <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                    Complete profile
                                </h4>
                                <span class="text-xs font-medium text-bgray-700 dark:text-darkblack-300">
                                    Complete your profile for accurate project allocation
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
            <!--Tab Content -->
            <div class="tab-content col-span-9 px-10 py-8">
                <!-- overview content -->
                <div id="overViewTab" class="tab-pane active">
                    <div class="grid grid-cols-12 gap-8">
                        <!-- LEFT SIDE -->
                        <div class="col-span-12 xl:col-span-12 space-y-10">
                            <!-- BASIC INFO -->
                            <div>
                                <h4 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                                    Basic Information
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Name</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->email ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date of Birth</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ optional($user->details->dob)->format($globalDateFormat) ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->phone ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->whatsapp ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Gender</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ ucfirst($user->details->gender ?? '-') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ORGANIZATION -->
                            <div>
                                <h4 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                                    Organization Details
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Role</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->getRoleNameAttribute() ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Department</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->department->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->designation->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporting To</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->reporter->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Manager</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->manager->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Employee ID</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->employee_id ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date of Joining</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ optional($user->details->joining_date)->format($globalDateFormat) ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- EMERGENCY CONTACT -->
                            <div>
                                <h4 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                                    Emergency Contact
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Person</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->contact_person ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Number</label>
                                        <p class="text-gray-700 dark:text-bgray-50">
                                            {{ $user->details->contact_person_number ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ADDRESS -->
                            <div>
                                <h4 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                                    Address
                                </h4>
                                <div class="mt-6">
                                    <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Address</label>
                                    <p class="text-gray-700 dark:text-bgray-50">
                                        {{ $user->details->address ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
                <!-- Notification manager content-->
                <div id="notificationTab" class="tab-pane">
                    <h3 class="mb-5 text-2xl font-bold text-bgray-900 dark:text-white">
                        Notification Preferences
                    </h3>
                    <div class="space-y-5">
                        <div class="!mb-0 grid grid-cols-12 items-center px-4 py-3 border-b font-semibold text-bgray-600 dark:text-white">
                            <div class="col-span-6">
                                Action
                            </div>
                            <div class="col-span-3 text-center">
                                In-App
                            </div>
                            <div class="col-span-3 text-center">
                                Email
                            </div>
                        </div>

                        @php
                        $userSettings = $user->notificationSettings->keyBy('action');
                        @endphp
                        @foreach($userNotificationSettings as $key => $setting)
                        <div class="!mt-0 grid grid-cols-12 items-center px-4 border-b border-bgray-200 dark:border-darkblack-400">
                            <!-- LEFT SIDE -->
                            <div class="col-span-6 flex items-center gap-4 min-h-[70px]">
                                <div class="w-10 h-10">
                                    {!! $setting['icon'] !!}
                                </div>
                                <div class="leading-tight">
                                    <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                        {{ $setting['label'] }}
                                    </h4>
                                    <p class="text-sm text-bgray-500 dark:text-darkblack-300">
                                        Manage notifications for {{ $setting['label'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- IN APP -->
                            <div class="col-span-3 flex justify-center items-center">
                                <button type="button"
                                    class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                                    {{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->in_app ? 'active' : '' }}"
                                    role="switch"
                                    data-user="{{ $user->id }}"
                                    data-action="{{ $setting['action'] }}"
                                    data-field="in_app"
                                    aria-checked="{{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->in_app ? 'true' : 'false' }}">
                                    <span aria-hidden="true"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                    </span>
                                </button>
                            </div>

                            <!-- EMAIL -->
                            <div class="col-span-3 flex justify-center items-center">
                                <button type="button"
                                    class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                                    {{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->mail ? 'active' : '' }}"
                                    role="switch"
                                    data-user="{{ $user->id }}"
                                    data-action="{{ $setting['action'] }}"
                                    data-field="mail"
                                    aria-checked="{{ isset($userSettings[$setting['action']]) && $userSettings[$setting['action']]->mail ? 'true' : 'false' }}">

                                    <span aria-hidden="true"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                    </span>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <!-- General Settings -->
                <div id="generalSettingsTab" class="tab-pane">
                    <div class="grid grid-cols-12">
                        <div class="col-span-12 border-bgray-300 2xl:col-span-9 2xl:border-r">

                            <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
                                General Settings
                            </h3>

                            <div class="space-y-10">
                                <!-- Default Kanban View -->
                                <div class="flex flex-col gap-4">
                                    <label class="text-lg font-semibold text-bgray-800 dark:text-white">
                                        Default Kanban View
                                    </label>
                                    <div class="flex items-center gap-6">
                                        <!-- Default Kanban View -->
                                        <label class="flex items-center gap-2">
                                            <input type="radio"
                                                name="kanban_view"
                                                value="agile"
                                                class="general-setting h-4 w-4 text-success-300 focus:ring-0"
                                                data-field="kanban_view"
                                                data-user="{{ $user->id }}"
                                                {{ ($generalSettings->kanban_view ?? 'agile') == 'agile' ? 'checked' : '' }}>
                                            <span class="text-bgray-700 dark:text-bgray-50">Agile</span>
                                        </label>

                                        <label class="flex items-center gap-2">
                                            <input type="radio"
                                                name="kanban_view"
                                                value="linear"
                                                class="general-setting h-4 w-4 text-success-300 focus:ring-0"
                                                data-field="kanban_view"
                                                data-user="{{ $user->id }}"
                                                {{ ($generalSettings->kanban_view ?? 'linear') == 'linear' ? 'checked' : '' }}>
                                            <span class="text-bgray-700 dark:text-bgray-50">Linear</span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Default Theme -->
                                <div class="flex flex-col gap-4">
                                    <label class="text-lg font-semibold text-bgray-800 dark:text-white">
                                        Default Theme
                                    </label>
                                    <div class="flex items-center gap-6">
                                        <label class="flex items-center gap-2">
                                            <input type="radio"
                                                name="theme"
                                                value="light"
                                                class="general-setting h-4 w-4 text-success-300 focus:ring-0"
                                                data-field="theme"
                                                data-user="{{ $user->id }}"
                                                data-login-user="{{ auth()->user()->id }}"
                                                {{ ($generalSettings->theme ?? '') == 'light' ? 'checked' : '' }}>
                                            <span class="text-bgray-700 dark:text-bgray-50">Light</span>
                                        </label>

                                        <label class="flex items-center gap-2">
                                            <input type="radio"
                                                name="theme"
                                                value="dark"
                                                class="general-setting h-4 w-4 text-success-300 focus:ring-0"
                                                data-field="theme"
                                                data-user="{{ $user->id }}"
                                                data-login-user="{{ auth()->user()->id }}"
                                                {{ ($generalSettings->theme ?? '') == 'dark' ? 'checked' : '' }}>
                                            <span class="text-bgray-700 dark:text-bgray-50">Dark</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- change password -->
                <div id="changePasswordTab" class="tab-pane">

                    <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
                        Change Password
                    </h3>

                    <form id="changePasswordForm" method="POST" action="{{ route('users.change.password') }}">
                        @csrf

                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                            @if(!auth()->user()->is_super_admin)
                            <!-- Current Password -->
                            <div class="flex flex-col gap-2">
                                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Current Password <x-red-star />
                                </label>
                                <input type="password"
                                    name="current_password"
                                    @if(!auth()->user()->is_super_admin) required @endif
                                class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                            </div>
                            @endif

                            <!-- New Password -->
                            <div class="flex flex-col gap-2">
                                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    New Password <x-red-star />
                                </label>
                                <input type="password"
                                    name="new_password"
                                    required
                                    class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                            </div>

                            <!-- Confirm Password -->
                            <div class="flex flex-col gap-2">
                                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Confirm New Password <x-red-star />
                                </label>
                                <input type="password"
                                    name="new_password_confirmation"
                                    required
                                    class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                            </div>

                            <!-- Hidden User ID -->
                            <input type="hidden" name="user_id" value="{{ $user->id }}">

                            <div class="col-span-full">
                                <button type="submit"
                                    class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
                                    Change Password
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- edit user modal form -->
        @include('users.edit-user-modal',['user' => $user])
    </div>
</main>
@endsection

@push('scripts')
@vite('resources/js/modules/users/user-notification-settings.js')
@vite('resources/js/modules/users/general-settings.js')
@vite('resources/js/modules/users/change-password.js')
@vite('resources/js/modules/users/user-edit.js')
@endpush