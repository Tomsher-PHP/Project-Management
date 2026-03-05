<form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST" class="space-y-10" enctype="multipart/form-data">
    @csrf
    @if (isset($user))
        @method('PUT')
    @endif

    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">

        {{-- Profile Image --}}
        <div class="flex-shrink-0">
            <div id="drop-area" class="relative flex h-28 w-28 items-center justify-center rounded-md border-2 border-dashed border-gray-300 overflow-hidden cursor-pointer">
                <!-- Preview Image -->
                <img id="preview" class="absolute inset-0 h-full w-full object-cover rounded-md {{ isset($user->profileImageUrl) ? '' : 'hidden' }}" alt="Preview" src="{{ $user->profileImageUrl ?? '' }}" />

                <!-- Remove Button -->
                <button type="button" id="remove-btn" class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 {{ isset($user->profileImageUrl) ? '' : 'hidden' }}">
                    ✕
                </button>

                <!-- Upload Placeholder -->
                <div id="placeholder" class="flex items-center justify-center text-sm text-gray-600 {{ isset($user->profileImageUrl) ? 'hidden' : '' }}">
                    <label for="profile-image" class="cursor-pointer text-indigo-600">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19.9997 13.3333V26.6666M26.6663 19.9999H13.333M19.9997 36.6666C29.2044 36.6666 36.6663 29.2047 36.6663 19.9999C36.6663 10.7952 29.2044 3.33325 19.9997 3.33325C10.7949 3.33325 3.33301 10.7952 3.33301 19.9999C3.33301 29.2047 10.7949 36.6666 19.9997 36.6666Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <input id="profile-image" name="profile_image" type="file" class="hidden" accept="image/*" />
                        <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                    </label>
                </div>
            </div>
        </div>

        {{-- Basic Information Fields --}}
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                Basic Information
            </h3>

            {{-- User Name --}}
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Name
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                    @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="flex flex-col gap-2">
                <label for="email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Email
                </label>

                <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                      @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" oninput="this.value = this.value.toLowerCase()" @isset($user) disabled @endisset>

                @error('email')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="flex flex-col gap-2">
                <label for="password" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Password
                </label>

                <input type="password" id="password" name="password" autocomplete="new-password" value="{{ old('password') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                      @error('password') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('password')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Date of Birth --}}
            <div class="flex flex-col gap-2">
                <label for="date_of_birth" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Birth
                </label>

                <input type="date" name="dob" id="date_of_birth" value="{{ old('dob', $user->details->dob ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('dob') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('dob')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Phone --}}
            <div class="flex flex-col gap-2">
                <label for="phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Phone Number
                </label>

                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->details->phone ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('phone')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- WhatsApp --}}
            <div class="flex flex-col gap-2">
                <label for="whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    WhatsApp Number
                </label>

                <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $user->details->whatsapp ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('whatsapp') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('whatsapp')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Gender --}}
            <div class="flex flex-col gap-2">
                <label for="gender" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Gender
                </label>

                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="female" {{ old('gender', $user->details->gender ?? '') == 'female' ? 'checked' : 'checked' }} class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Female</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="male" {{ old('gender', $user->details->gender ?? '') == 'male' ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Male</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="other" {{ old('gender', $user->details->gender ?? '') == 'other' ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Other</span>
                    </label>
                </div>

                @error('gender')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

    </div>

    {{-- ================= ORGANIZATION DETAILS ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Organization Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {{-- Role --}}
            <div class="flex flex-col gap-2">
                <label for="role" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Role
                </label>

                <select name="role" id="role" class="select-subtypes w-full">

                    <option value="">Select Role</option>

                    @foreach ($roles as $key => $role)
                        <option value="{{ $role->id }}" {{ old('role', $user->role_id ?? '') == $role->id ? 'selected' : '' }} data-subtype="{{ config('constants.user_types')[$role->user_type] }}">
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>

                @error('role')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Department --}}
            <div class="flex flex-col gap-2">
                <label for="department" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Department
                </label>

                <select name="department_id" id="department" class="tom-select w-full">

                    <option value="">Select Department</option>

                    @foreach ($departments as $key => $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $user->details->department_id ?? '') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>

                @error('department_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Designation --}}
            <div class="flex flex-col gap-2">
                <label for="designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Designation
                </label>

                <select name="designation_id" id="designation" class="tom-select w-full">

                    <option value="">Select Designation</option>

                    @foreach ($designations as $key => $designation)
                        <option value="{{ $designation->id }}" {{ old('designation_id', $user->details->designation_id ?? '') == $designation->id ? 'selected' : '' }}>
                            {{ $designation->name }}
                        </option>
                    @endforeach
                </select>

                @error('designation_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Reporting To --}}
            <div class="flex flex-col gap-2">
                <label for="reporting_to" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Reporting To
                </label>

                <select name="reporter_id" id="reporting_to" class="select-subtypes w-full">

                    <option value="">Select Reporting Manager</option>

                    @foreach ($managers as $key => $reporter)
                        <option value="{{ $reporter->id }}" {{ old('reporter_id', $user->details->reporter_id ?? '') == $reporter->id ? 'selected' : '' }} data-subtype="{{ config('constants.user_types')[$reporter->user_type] }}">
                            {{ $reporter->name }}
                        </option>
                    @endforeach
                </select>

                @error('reporter_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Manager --}}
            <div class="flex flex-col gap-2">
                <label for="manager" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Manager
                </label>
                <select name="manager_id" id="manager" class="select-subtypes w-full">

                    <option value="">Select Manager</option>

                    @foreach ($managers as $key => $manager)
                        <option value="{{ $manager->id }}" {{ old('manager_id', $user->details->manager_id ?? '') == $manager->id ? 'selected' : '' }} data-subtype="{{ config('constants.user_types')[$manager->user_type] }}">
                            {{ $manager->name }}
                        </option>
                    @endforeach
                </select>

                @error('manager_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Employee ID --}}
            <div class="flex flex-col gap-2">
                <label for="employee_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Employee ID
                </label>

                <input type="text" name="employee_id" id="employee_id" value="{{ old('employee_id', $user->details->employee_id ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('employee_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('employee_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Date of Joining --}}
            <div class="flex flex-col gap-2">
                <label for="date_of_joining" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Joining
                </label>

                <input type="date" name="joining_date" id="date_of_joining" value="{{ old('joining_date', $user->details->joining_date ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('joining_date') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('joining_date')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ================= EMERGENCY CONTACT ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Emergency Contact
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Contact Person Name --}}
            <div class="flex flex-col gap-2">
                <label for="contact_person_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Name
                </label>

                <input type="text" name="contact_person" id="contact_person_name" value="{{ old('contact_person', $user->details->contact_person ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('contact_person') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('contact_person')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Contact Person Number --}}
            <div class="flex flex-col gap-2">
                <label for="contact_person_number" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Number
                </label>

                <input type="text" name="contact_person_number" id="contact_person_number" value="{{ old('contact_person_number', $user->details->contact_person_number ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('contact_person_number') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('contact_person_number')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ================= ADDRESS ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Address Information
        </h3>

        <div class="grid grid-cols-1 gap-6">
            {{-- Address --}}
            <div class="flex flex-col gap-2">
                <label for="address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Address
                </label>

                <textarea name="address" id="address" rows="3" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('address', $user->details->address ?? '') }}</textarea>

                @error('address')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

    {{-- ================= Shift Information ================= --}}
    {{-- <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Shift Information
        </h3>

        <div id="shifts-wrapper">
            @php
                $oldShifts = old('shifts', [['start_time' => '09:00', 'end_time' => '18:00', 'break_duration' => '01:00']]);
            @endphp

            @foreach ($oldShifts as $index => $shift)
                <div class="shift-item border p-4 rounded-lg mb-6 dark:border-darkblack-400">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Start Time</label>
                            <input type="time" name="start_time[]" value="{{ $shift['start_time'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">End Time</label>
                            <input type="time" name="end_time[]" value="{{ $shift['end_time'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Break (HH:MM)</label>
                            <input type="time" step="60" name="break_duration[]" value="{{ $shift['break_duration'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                        </div>

                    </div>
                </div>
            @endforeach

        </div>

        <button type="button" onclick="addShift()" class="px-4 py-2 bg-success-300 text-white rounded">
            + More Shift
        </button>

        <div class="mt-8">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 dark:text-white">
                Working Days
            </h4>

            <div class="flex flex-wrap items-center gap-6">
                @php
                    $days = [
                        'sunday' => 'Sunday',
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                    ];
                @endphp

                @foreach ($days as $key => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="working_days[]" value="{{ $key }}" {{ in_array($key, old('working_days', [])) ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                        <span class="text-sm text-gray-700 dark:text-bgray-50">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </div> --}}

    {{-- Submit Button --}}
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
            @if (isset($user))
                Update User
            @else
                Create User
            @endif
        </button>
    </div>

</form>

<div style="display: none" id="add-shift-card">
    <div class="shift-item border p-4 rounded-lg mb-6 dark:border-darkblack-400">

        <!-- Remove Button -->
        <button type="button" class="remove-shift absolute top-3 right-3 text-red-500 text-sm font-semibold">
            ✕ Remove
        </button>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {{-- Start Time --}}
            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Start Time</label>
                <input type="time" name="start_time[]" value="{{ $shift['start_time'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            {{-- End Time --}}
            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">End Time</label>
                <input type="time" name="end_time[]" value="{{ $shift['end_time'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            {{-- Break --}}
            <div class="flex flex-col gap-2">
                <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Break (HH:MM)</label>
                <input type="time" step="60" name="break_duration[]" value="{{ $shift['break_duration'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

        </div>
    </div>
</div>
