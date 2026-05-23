<form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST" class="space-y-10" enctype="multipart/form-data">
    @csrf
    @if (isset($user))
        @method('PUT')
    @endif

    @php
        $isEditMode = isset($user);
    @endphp

    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">

        <!-- Profile Image -->
        <div class="flex-shrink-0">
            <div id="drop-area" class="relative flex h-28 w-28 items-center justify-center rounded-md border-2 border-dashed border-gray-300 overflow-hidden cursor-pointer">
                <!-- Preview Image -->
                <img id="preview" class="absolute inset-0 h-full w-full object-cover rounded-md {{ $user?->hasProfileImage ? '' : 'hidden' }}" alt="Preview" src="{{ $user->profileImageUrl ?? '' }}" />

                <!-- Remove Button -->
                <button type="button" id="remove-btn" class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full text-gray-700 shadow-md hover:bg-red-600 {{ $user?->hasProfileImage ? '' : 'hidden' }}">
                    ✕
                </button>

                <!-- Upload Placeholder -->
                <div id="placeholder" class="flex items-center justify-center text-sm text-gray-600 {{ $user?->hasProfileImage ? 'hidden' : '' }}">
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

        <!-- Basic Information Fields -->
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                Basic Information
            </h3>

            <!-- User Name -->
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Name <x-red-star />
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="Enter full name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                    @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <x-forms.email-input label="Email" name="email" id="email" :value="old('email', $user->email ?? '')" :required="!isset($user)" :disabled="isset($user)" placeholder="Enter email address" />

            @unless ($isEditMode)
                <!-- Password -->
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Password <x-red-star />
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Minimum 8 characters, must include letters and numbers.
                            </span>
                        </span>
                    </label>

                    <div class="relative" data-password-field>
                        <input type="password" id="password" name="password" autocomplete="new-password" value="{{ old('password') }}" placeholder="Enter password" data-password-input class="user-password-input w-full rounded-lg border border-gray-300 bg-white p-2 pr-12 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white @error('password') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <button type="button" class="absolute inset-y-0 right-4 inline-flex items-center text-bgray-500 transition hover:text-bgray-700 dark:text-bgray-300 dark:hover:text-white" data-password-toggle aria-label="Show password" aria-pressed="false">
                            <svg data-password-icon="show" class="h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M1 10C2.714 5.83333 6.04733 3.75 11 3.75C15.9527 3.75 19.286 5.83333 21 10C19.286 14.1667 15.9527 16.25 11 16.25C6.04733 16.25 2.714 14.1667 1 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="11" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <svg data-password-icon="hide" class="hidden h-5 w-5" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M2 1L20 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Confirm Password <x-red-star />
                    </label>

                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" value="{{ old('password_confirmation') }}" placeholder="Confirm password" class="user-password-input w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white @error('password_confirmation') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endunless

            <!-- Date of Birth -->
            <div class="flex flex-col gap-2">
                <label for="date_of_birth" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Birth
                </label>

                <input type="date" name="dob" id="date_of_birth" value="{{ old('dob', isset($user) ? optional($user->details?->dob)->format('Y-m-d') : '') }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" data-format="{{ $globalDateFormat }}" data-open-to-date="{{ now(config('constants.timezone'))->subYears(17)->startOfYear()->toDateString() }}" placeholder="Select a date">

                @error('dob')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Phone -->
            <div class="flex flex-col gap-2">
                <label for="phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Phone Number
                </label>

                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->details->phone ?? '') }}" placeholder="Enter phone number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('phone')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- WhatsApp -->
            <div class="flex flex-col gap-2">
                <label for="whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    WhatsApp Number
                </label>

                <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $user->details->whatsapp ?? '') }}" placeholder="Enter WhatsApp number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('whatsapp') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('whatsapp')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Gender -->
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

    <!-- ================= ORGANIZATION DETAILS ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Organization Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <!-- Role -->
            <div class="flex flex-col gap-2">
                <label for="role" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Role <x-red-star />
                </label>

                <select name="role" id="role" class="tom-select w-full">

                    <option value="">Select Role</option>

                    @foreach ($roles as $key => $role)
                        <option value="{{ $role->id }}" {{ old('role', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
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

            <!-- Department -->
            <div class="flex flex-col gap-2">
                <label for="department" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Department
                </label>

                <div class="flex items-center gap-2">
                    <select name="department_id" id="department" class="tom-select w-full">

                        <option value="">Select Department</option>

                        @foreach ($departments as $key => $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $user->details->department_id ?? '') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>

                    @can('department.create')
                        <button type="button" data-target="#user-department-modal" data-select-target="department_id" data-module="Department" data-url="{{ route('settings.departments.store') }}" data-method="POST" data-sort_order="{{ $nextDepartmentSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Department" aria-label="Add Department">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    @endcan
                </div>

                @error('department_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Designation -->
            <div class="flex flex-col gap-2">
                <label for="designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Designation
                </label>

                <div class="flex items-center gap-2">
                    <select name="designation_id" id="designation" class="tom-select w-full">

                        <option value="">Select Designation</option>

                        @foreach ($designations as $key => $designation)
                            <option value="{{ $designation->id }}" {{ old('designation_id', $user->details->designation_id ?? '') == $designation->id ? 'selected' : '' }}>
                                {{ $designation->name }}
                            </option>
                        @endforeach
                    </select>

                    @can('designation.create')
                        <button type="button" data-target="#user-designation-modal" data-select-target="designation_id" data-module="Designation" data-url="{{ route('settings.designations.store') }}" data-method="POST" data-sort_order="{{ $nextDesignationSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Designation" aria-label="Add Designation">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    @endcan
                </div>

                @error('designation_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Reporting To -->
            <div class="flex flex-col gap-2">
                <label for="reporting_to" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Reporting To
                </label>

                <select name="reporter_id" id="reporting_to" class="tom-select w-full">

                    <option value="">Select Reporting Manager</option>

                    @foreach ($managers as $key => $reporter)
                        <option value="{{ $reporter->id }}" {{ old('reporter_id', $user->details->reporter_id ?? '') == $reporter->id ? 'selected' : '' }}>
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

            <!-- Manager -->
            <div class="flex flex-col gap-2">
                <label for="manager" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Manager
                </label>
                <select name="manager_id" id="manager" class="tom-select w-full">

                    <option value="">Select Manager</option>

                    @foreach ($managers as $key => $manager)
                        <option value="{{ $manager->id }}" {{ old('manager_id', $user->details->manager_id ?? '') == $manager->id ? 'selected' : '' }}>
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

            <!-- Employee ID -->
            <div class="flex flex-col gap-2">
                <label for="employee_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Employee ID
                </label>

                <input type="text" name="employee_id" id="employee_id" value="{{ old('employee_id', $user->details->employee_id ?? '') }}" placeholder="Enter employee ID" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('employee_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('employee_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Date of Joining -->
            <div class="flex flex-col gap-2">
                <label for="date_of_joining" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Joining
                </label>

                <input type="date" name="joining_date" id="date_of_joining" value="{{ old('joining_date', isset($user) ? optional($user->details?->joining_date)->format('Y-m-d') : now(config('constants.timezone'))->toDateString()) }}" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('joining_date') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" data-format="{{ $globalDateFormat }}"
                    placeholder="Select a date">

                @error('joining_date')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    <!-- ================= EMERGENCY CONTACT ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Emergency Contact
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Contact Person Name -->
            <div class="flex flex-col gap-2">
                <label for="contact_person_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Name
                </label>

                <input type="text" name="contact_person" id="contact_person_name" value="{{ old('contact_person', $user->details->contact_person ?? '') }}" placeholder="Enter emergency contact name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('contact_person') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('contact_person')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Contact Person Number -->
            <div class="flex flex-col gap-2">
                <label for="contact_person_number" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Number
                </label>

                <input type="text" name="contact_person_number" id="contact_person_number" value="{{ old('contact_person_number', $user->details->contact_person_number ?? '') }}" placeholder="Enter emergency contact number" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('contact_person_number') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('contact_person_number')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    <!-- ================= ADDRESS ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Address Information
        </h3>

        <div class="grid grid-cols-1 gap-6">
            <!-- Address -->
            <div class="flex flex-col gap-2">
                <label for="address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Address
                </label>

                <textarea name="address" id="address" rows="3" placeholder="Enter full address" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                       @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('address', $user->details->address ?? '') }}</textarea>

                @error('address')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>
    <!-- ================= KPI ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            KPI Information
        </h3>

        <div class="grid grid-cols-1 gap-6">
            <!-- KPI -->
            <div class="flex flex-col gap-2">
                <label for="kpi_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    KPI
                </label>

                <select name="kpi_id[]" id="kpi_id" class="tom-select w-full" multiple>
                    <option value="">Select KPI</option>

                    @php
                        $selectedKpis = old('kpi_id', $user?->kpis->pluck('id')->toArray() ?? []);
                    @endphp

                    @foreach ($kpis as $kpi)
                        <option value="{{ $kpi->id }}" {{ in_array($kpi->id, $selectedKpis) ? 'selected' : '' }}>
                            {{ $kpi->name }}
                        </option>
                    @endforeach
                </select>

                @error('kpi_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Submit Button -->
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
@can('department.create')
    <x-form-modal modalId="user-department-modal" module="Department" formId="userDepartmentForm" action="{{ route('settings.departments.store') }}" button="Create Department">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" placeholder="Enter department name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>
    </x-form-modal>
@endcan

@can('designation.create')
    <x-form-modal modalId="user-designation-modal" module="Designation" formId="userDesignationForm" action="{{ route('settings.designations.store') }}" button="Create Designation">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" placeholder="Enter designation name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>
    </x-form-modal>
@endcan

@once
    @push('styles')
        <style>
            .user-password-input[type="password"] {
                font-size: 1.125rem;
                letter-spacing: 0.08em;
            }

            .user-password-input[type="password"]::placeholder {
                font-size: 1rem;
                letter-spacing: normal;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const updatePasswordToggle = (button, input) => {
                    const isVisible = input.type === 'text';

                    button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
                    button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');

                    const showIcon = button.querySelector('[data-password-icon="show"]');
                    const hideIcon = button.querySelector('[data-password-icon="hide"]');

                    showIcon?.classList.toggle('hidden', isVisible);
                    hideIcon?.classList.toggle('hidden', !isVisible);
                };

                document.querySelectorAll('[data-password-field]').forEach((field) => {
                    const input = field.querySelector('[data-password-input]');
                    const button = field.querySelector('[data-password-toggle]');

                    if (!input || !button) {
                        return;
                    }

                    updatePasswordToggle(button, input);
                });

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-password-toggle]');

                    if (!button || button.closest('#changePasswordForm')) {
                        return;
                    }

                    const field = button.closest('[data-password-field]');
                    const input = field?.querySelector('[data-password-input]');

                    if (!input) {
                        return;
                    }

                    input.type = input.type === 'password' ? 'text' : 'password';
                    updatePasswordToggle(button, input);
                });
            });
        </script>
    @endpush
@endonce
