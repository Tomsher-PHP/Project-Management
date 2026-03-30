@extends('layouts.master')

@section('page-content')
    @php
        $canEdit = auth()->user()->can('configuration.edit');
    @endphp

    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-6 py-8 dark:bg-darkblack-600">
                    <form action="{{ route('settings.configurations.update') }}" method="POST" class="space-y-10" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @if (!$canEdit)
                            <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                                You have view-only access to this configuration.
                            </div>
                            <fieldset disabled class="opacity-60 cursor-not-allowed">
                        @endif

                        <!-- Basic Information Fields -->
                        <div class="flex flex-col xl:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start xl:items-center">

                            <!-- Logos -->
                            <div class="flex flex-row gap-6">
                                <!-- Logo Image -->
                                <div class="flex-shrink-0 flex flex-col items-center gap-2">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Logo</span>
                                    <div id="drop-area-logo" class="relative flex h-28 w-28 items-center justify-center rounded-md border-2 border-dashed border-gray-300 overflow-hidden cursor-pointer">
                                        <img id="preview-logo" class="absolute inset-0 h-full w-full object-cover rounded-md {{ isset($config) && $config->logo ? '' : 'hidden' }}" alt="Preview Logo" src="{{ $config->logo_url ?? '' }}" />

                                        <button type="button" id="remove-btn-logo" class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 {{ isset($config) && $config->logo ? '' : 'hidden' }}">✕</button>

                                        <div id="placeholder-logo" class="flex items-center justify-center text-sm text-gray-600 {{ isset($config) && $config->logo ? 'hidden' : '' }}">
                                            <label for="logo" class="cursor-pointer text-indigo-600 flex flex-col items-center">
                                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M19.9997 13.3333V26.6666M26.6663 19.9999H13.333M19.9997 36.6666C29.2044 36.6666 36.6663 29.2047 36.6663 19.9999C36.6663 10.7952 29.2044 3.33325 19.9997 3.33325C10.7949 3.33325 3.33301 10.7952 3.33301 19.9999C3.33301 29.2047 10.7949 36.6666 19.9997 36.6666Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <input id="logo" name="logo" type="file" class="hidden" accept="image/*" />
                                                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                                            </label>
                                        </div>
                                    </div>
                                    @error('logo')
                                        <p class="text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Company Details -->
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6 w-full mt-6 xl:mt-0">
                                <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-2 dark:border-darkblack-400 dark:text-white">
                                    Company Information
                                </h3>

                                <!-- Company Name -->
                                <div class="flex flex-col gap-2">
                                    <label for="company_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Company Name
                                    </label>
                                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $config->company_name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                                        bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                                        @error('company_name') border-red-500 focus:ring-red-500 @enderror">
                                    @error('company_name')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Company Email -->
                                <div class="flex flex-col gap-2">
                                    <label for="company_email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Company Email
                                    </label>
                                    <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $config->company_email ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                                        bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                                        @error('company_email') border-red-500 focus:ring-red-500 @enderror">
                                    @error('company_email')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Company Phone -->
                                <div class="flex flex-col gap-2 col-span-full md:col-span-1">
                                    <label for="company_phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Company Phone
                                    </label>
                                    <input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $config->company_phone ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                                        bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                                        @error('company_phone') border-red-500 focus:ring-red-500 @enderror">
                                    @error('company_phone')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="border-b pb-8 dark:border-darkblack-400">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label for="company_address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Company Address
                                    </label>
                                    <textarea name="company_address" id="company_address" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                                        @error('company_address') border-red-500 focus:ring-red-500 @enderror">{{ old('company_address', $config->company_address ?? '') }}</textarea>
                                    @error('company_address')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                                System Settings
                            </h3>

                            <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
                                <!-- Timezone -->
                                <div class="flex-1 min-w-0">
                                    <label for="timezone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Timezone
                                    </label>
                                    <select name="timezone" id="timezone" class="tom-select w-full">
                                        <option value="">Select Timezone</option>
                                        @foreach ($timezones as $tz)
                                            <option value="{{ $tz->zone_name }}" {{ old('timezone', $config->timezone ?? '') == $tz->zone_name ? 'selected' : '' }}>
                                                {{ $tz->zone_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('timezone')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Date Format -->
                                <div class="flex-1 min-w-0">
                                    <label for="date_format" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Date Format
                                    </label>
                                    <select name="date_format" id="date_format" class="tom-select-no-search w-full">
                                        <option value="">Select Date Format</option>
                                        @foreach ($dateFormats as $format)
                                            <option value="{{ $format }}" {{ old('date_format', $config->date_format ?? '') == $format ? 'selected' : '' }}>
                                                {{ $format }} ({{ date($format) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('date_format')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Time Format -->
                                <div class="flex-1 min-w-0">
                                    <label for="time_format" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Time Format
                                    </label>
                                    <select name="time_format" id="time_format" class="tom-select-no-search w-full">
                                        <option value="">Select Time Format</option>
                                        @foreach ($timeFormats as $format)
                                            <option value="{{ $format }}" {{ old('time_format', $config->time_format ?? '') == $format ? 'selected' : '' }}>
                                                {{ $format }} ({{ date($format) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('time_format')
                                        <p class="mt-2 text-sm text-error-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @if (!$canEdit)
                            </fieldset>
                        @endif

                        @if ($canEdit)
                            <!-- Submit Button -->
                            <div class="pt-6 border-t flex justify-end dark:border-darkblack-400">
                                <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
                                    Save Configuration
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

@endsection
