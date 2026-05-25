<!-- ================= MODAL ================= -->
<div x-show="openEdit" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div @click.outside="openEdit = false" class="w-full max-w-4xl rounded-xl bg-white p-6 dark:bg-darkblack-600">

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                Edit User
            </h3>

            <button @click="openEdit = false">✕</button>
        </div>

        <form id="userEditForm" action="{{ route('users.modal.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($user))
                @method('PUT')
            @endif

            <div class="flex flex-col md:flex-row gap-8 pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">

                <!-- Profile Image -->
                <div class="flex-shrink-0">
                    <div id="drop-area" class="relative flex h-28 w-28 items-center justify-center rounded-md border-2 border-dashed border-gray-300 overflow-hidden cursor-pointer">
                        <!-- Preview Image -->
                        <img id="preview" class="{{ $user->hasProfileImage ? '' : 'hidden' }} absolute inset-0 h-full w-full object-cover rounded-md" alt="Preview" src="{{ $user->profileImageUrl ?? '' }}" />

                        <!-- Remove Button -->
                        <button type="button" id="remove-btn" class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-gray-700 shadow-md hover:bg-red-600 {{ $user->hasProfileImage ? '' : 'hidden' }}">
                            ✕
                        </button>

                        <!-- Upload Placeholder -->
                        <div id="placeholder" class="flex items-center justify-center text-sm text-gray-600 {{ $user->hasProfileImage ? 'hidden' : '' }}">
                            <label for="profile-image" class="cursor-pointer text-indigo-600">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.9997 13.3333V26.6666M26.6663 19.9999H13.333M19.9997 36.6666C29.2044 36.6666 36.6663 29.2047 36.6663 19.9999C36.6663 10.7952 29.2044 3.33325 19.9997 3.33325C10.7949 3.33325 3.33301 10.7952 3.33301 19.9999C3.33301 29.2047 10.7949 36.6666 19.9997 36.6666Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <input id="profile-image" name="profile_image" type="file" class="hidden" accept="image/*" />
                                <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                            </label>
                        </div>
                    </div>
                    @error('profile_image')
                        <p class="mt-2 text-sm text-error-300">
                            {{ $message }}
                        </p>
                    @enderror
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
                <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 mt-6 dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
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
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                    <input type="text" name="name" placeholder="Enter department name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                    <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                </div>
            </x-form-modal>
        @endcan

        @can('designation.create')
            <x-form-modal modalId="user-designation-modal" module="Designation" formId="userDesignationForm" action="{{ route('settings.designations.store') }}" button="Create Designation">
                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                    <input type="text" name="name" placeholder="Enter designation name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                    <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                </div>
            </x-form-modal>
        @endcan


    </div>
</div>
@push('scripts')
    @vite('resources/js/image-draggable.js')
@endpush
