<form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST" class="space-y-10" enctype="multipart/form-data">
    @csrf
    @if (isset($user))
        @method('PUT')
    @endif

    {{-- Profile Image --}}
    <div class="flex justify-center border-b pb-8 dark:border-darkblack-400 dark:text-white">
        <!-- KEEPING YOUR IMAGE CODE SAME -->
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

    {{-- ================= BASIC INFORMATION ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Basic Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            {{-- User Name --}}
            <div>
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Name
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                      @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Email
                </label>

                <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                      focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500
                      @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" oninput="this.value = this.value.toLowerCase()">

                @error('email')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Password
                </label>

                <input type="password" id="password" name="password" value="{{ old('password', $user->password ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                      @error('password') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('password')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Gender --}}
            <div>
                <label for="gender" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Gender
                </label>

                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="female" {{ old('gender', $user->details->gender ?? '') == 'female' ? 'checked' : 'checked' }} class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Female</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="male" {{ old('gender', $user->details->gender ?? '') == 'male' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Male</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="other" {{ old('gender', $user->details->gender ?? '') == 'other' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-bgray-50">Other</span>
                    </label>
                </div>

                @error('gender')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Date of Birth --}}
            <div>
                <label for="date_of_birth" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Birth
                </label>

                <input type="date" name="dob" id="date_of_birth" value="{{ old('dob', $user->details->dob ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('dob') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('dob')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Phone Number
                </label>

                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->details->phone ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('phone')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- WhatsApp --}}
            <div>
                <label for="whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    WhatsApp Number
                </label>

                <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $user->details->whatsapp ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('whatsapp') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('whatsapp')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

    {{-- ================= ORGANIZATION DETAILS ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Organization Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {{-- Role --}}
            <div>
                <label for="role" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Role
                </label>

                <select name="role" id="role" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('role') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

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

            {{-- Department --}}
            <div>
                <label for="department" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Department
                </label>

                <select name="department_id" id="department" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('department_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

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
            <div>
                <label for="designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Designation
                </label>

                <select name="designation_id" id="designation" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('designation_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

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
            <div>
                <label for="reporting_to" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Reporting To
                </label>

                <select name="reporter_id" id="reporting_to" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('reporter_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

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

            {{-- Manager --}}
            <div>
                <label for="manager" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Manager
                </label>
                <select name="manager_id" id="manager" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('manager_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

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

            {{-- Employee ID --}}
            <div>
                <label for="employee_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Employee ID
                </label>

                <input type="text" name="employee_id" id="employee_id" value="{{ old('employee_id', $user->details->employee_id ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('employee_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('employee_id')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Date of Joining --}}
            <div>
                <label for="date_of_joining" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Date of Joining
                </label>

                <input type="date" name="joining_date" id="date_of_joining" value="{{ old('joining_date', $user->details->joining_date ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
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
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Emergency Contact
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Contact Person Name --}}
            <div>
                <label for="contact_person_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Name
                </label>

                <input type="text" name="contact_person" id="contact_person_name" value="{{ old('contact_person', $user->details->contact_person ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('contact_person') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('contact_person')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Contact Person Number --}}
            <div>
                <label for="contact_person_number" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Contact Person Number
                </label>

                <input type="text" name="contact_person_number" id="contact_person_number" value="{{ old('contact_person_number', $user->details->contact_person_number ?? '') }}" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
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
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Address Information
        </h3>

        <div class="grid grid-cols-1 gap-6">
            {{-- Address --}}
            <div>
                <label for="address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Address
                </label>

                <textarea name="address" id="address" rows="3" class="w-full rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white
                       @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('address', $user->details->address ?? '') }}</textarea>

                @error('address')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Submit Button --}}
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
            @if (isset($user))
                Update User
            @else
                Create User
            @endif
        </button>
    </div>

</form>
