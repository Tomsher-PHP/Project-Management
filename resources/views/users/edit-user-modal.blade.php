<!-- ================= MODAL ================= -->
<div x-show="openEdit" x-transition.opacity x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div @click.outside="openEdit = false" class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 dark:bg-darkblack-600">

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                Edit User
            </h3>

            <button type="button" @click="openEdit = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-lg">✕</button>
        </div>

        <form id="userEditForm" action="{{ route('users.modal.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($user))
                @method('PUT')
            @endif

            <div class="flex flex-col items-center gap-8 pb-8 dark:border-darkblack-400 dark:text-white md:flex-row">

                <!-- Profile Image -->
                <div class="w-28 flex-shrink-0">
                    <div>
                        <div id="drop-area" class="relative flex h-28 w-28 cursor-pointer items-center justify-center overflow-hidden rounded-md border-2 border-dashed border-gray-300">
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

                    <div class="mt-2 flex items-center gap-1.5 text-xs font-medium text-bgray-500 dark:text-bgray-300">
                        <span>Image must</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-600 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                JPG, JPEG, or PNG. Maximum file size: 2 MB.
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Basic Information Fields -->
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                        Basic Information
                    </h3>

                    <!-- User Name -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Name <x-red-star />
                        </label>

                        <input type="text" id="modal_name" name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="Enter full name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
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
                        <label for="modal_phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Phone Number
                        </label>

                        <input type="text" name="phone" id="modal_phone" value="{{ old('phone', $user->details->phone ?? '') }}" placeholder="Enter phone number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
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
                        <label for="modal_whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            WhatsApp Number
                        </label>

                        <input type="text" name="whatsapp" id="modal_whatsapp" value="{{ old('whatsapp', $user->details->whatsapp ?? '') }}" placeholder="Enter WhatsApp number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
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

            <!-- ================= ADDRESS INFORMATION ================= -->
            <div class="mt-6">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                    Address Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Residence Address -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Residence Address
                        </label>

                        <textarea name="address" id="modal_address" rows="3" placeholder="Enter residence address" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('address', $user->details->address ?? '') }}</textarea>

                        @error('address')
                            <p class="mt-2 text-sm text-error-300">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Native Address -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_native_address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Native Address
                        </label>

                        <textarea name="native_address" id="modal_native_address" rows="3" placeholder="Enter native address" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400 @error('native_address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('native_address', $user->details->native_address ?? '') }}</textarea>

                        @error('native_address')
                            <p class="mt-2 text-sm text-error-300">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- ================= EMERGENCY CONTACT INFORMATION ================= -->
            <div class="mt-6">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                    Emergency Contact Information
                </h3>

                @php
                    $modalEmergencyContacts = old('emergency_contacts', $user->details?->emergency_contacts ?? []);
                    if (empty($modalEmergencyContacts) && (!empty($user->details?->contact_person) || !empty($user->details?->contact_person_number))) {
                        $modalEmergencyContacts = [
                            [
                                'name' => $user->details->contact_person ?? '',
                                'number' => $user->details->contact_person_number ?? '',
                                'relation' => '',
                            ],
                        ];
                    }
                    if (empty($modalEmergencyContacts)) {
                        $modalEmergencyContacts = [['name' => '', 'number' => '', 'relation' => '']];
                    }
                @endphp

                <div id="modal-emergency-contacts-container" class="space-y-4">
                    @foreach ($modalEmergencyContacts as $index => $contact)
                        @include('users.partials.modal-contact-row', [
                            'index' => $index,
                            'fieldName' => 'emergency_contacts',
                            'idPrefix' => 'modal_emergency_contact',
                            'contact' => $contact,
                            'numberPlaceholder' => 'e.g. +971501234567',
                            'relationPlaceholder' => 'e.g. Father, Spouse',
                        ])
                    @endforeach
                </div>

                <div class="mt-4 flex justify-start">
                    <button type="button" id="modal-btn-add-emergency-contact" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-success-300 bg-success-50 text-success-300 hover:bg-success-100 dark:bg-darkblack-500 dark:border-success-400 text-sm font-medium transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Emergency Contact</span>
                    </button>
                </div>
            </div>

            <!-- ================= NATIVE CONTACT INFORMATION ================= -->
            <div class="mt-6">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                    Native Contact Information
                </h3>

                @php
                    $modalNativeContacts = old('native_contacts', $user->details?->native_contacts ?? []);
                    if (empty($modalNativeContacts)) {
                        $modalNativeContacts = [['name' => '', 'number' => '', 'relation' => '']];
                    }
                @endphp

                <div id="modal-native-contacts-container" class="space-y-4">
                    @foreach ($modalNativeContacts as $index => $contact)
                        @include('users.partials.modal-contact-row', [
                            'index' => $index,
                            'fieldName' => 'native_contacts',
                            'idPrefix' => 'modal_native_contact',
                            'contact' => $contact,
                            'numberPlaceholder' => 'e.g. +919876543210',
                            'relationPlaceholder' => 'e.g. Brother, Friend',
                        ])
                    @endforeach
                </div>

                <template id="modal-contact-row-template">
                    @include('users.partials.modal-contact-row', [
                        'index' => 0,
                        'fieldName' => '',
                        'idPrefix' => 'modal_contact',
                        'contact' => [],
                    ])
                </template>

                <div class="mt-4 flex justify-start">
                    <button type="button" id="modal-btn-add-native-contact" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-success-300 bg-success-50 text-success-300 hover:bg-success-100 dark:bg-darkblack-500 dark:border-success-400 text-sm font-medium transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Native Contact</span>
                    </button>
                </div>
            </div>

            <!-- ================= PERSONAL INFORMATION & PREFERENCES ================= -->
            <div class="mt-6">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                    Personal Information & Preferences
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <!-- Marital Status -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_marital_status" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Marital Status
                        </label>

                        <select name="marital_status" id="modal_marital_status" class="tom-select w-full" data-sort="0">
                            <option value="">Select Marital Status</option>
                            @php
                                $modalMaritalStatus = old('marital_status', $user->details->marital_status ?? '');
                            @endphp
                            @foreach (config('constants.marital_statuses', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalMaritalStatus === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Children Count -->
                    <div id="modal-children-count-wrapper" class="flex flex-col gap-2">
                        <label for="modal_children_count" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Children Count
                        </label>

                        <select name="children_count" id="modal_children_count" class="tom-select w-full" data-sort="0">
                            <option value="">Select Children Count</option>
                            @php
                                $modalChildrenCount = old('children_count', isset($user->details->children_count) ? (string) $user->details->children_count : '');
                            @endphp
                            @foreach (config('constants.children_counts', []) as $key => $label)
                                <option value="{{ $key }}" {{ $modalChildrenCount !== '' && $modalChildrenCount !== null && (string) $modalChildrenCount === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Favorite Color -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_favorite_color" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Favorite Color
                        </label>

                        <select name="favorite_color" id="modal_favorite_color" class="tom-select w-full" data-sort="0">
                            <option value="">Select Favorite Color</option>
                            @php
                                $modalFavoriteColor = old('favorite_color', $user->details->favorite_color ?? '');
                            @endphp
                            @foreach (config('constants.favorite_colors', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalFavoriteColor === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Casual Shirt Size -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_shirt_size_casual" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Casual Shirt Size
                        </label>

                        <select name="shirt_sizes[casual]" id="modal_shirt_size_casual" class="tom-select w-full" data-sort="0">
                            <option value="">Select Casual Shirt Size</option>
                            @php
                                $modalCasualShirt = old('shirt_sizes.casual', $user->details->shirt_sizes['casual'] ?? '');
                            @endphp
                            @foreach (config('constants.shirt_sizes', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalCasualShirt === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Formal Shirt Size -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_shirt_size_formal" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Formal Shirt Size
                        </label>

                        <select name="shirt_sizes[formal]" id="modal_shirt_size_formal" class="tom-select w-full" data-sort="0">
                            <option value="">Select Formal Shirt Size</option>
                            @php
                                $modalFormalShirt = old('shirt_sizes.formal', $user->details->shirt_sizes['formal'] ?? '');
                            @endphp
                            @foreach (config('constants.shirt_sizes', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalFormalShirt === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Casual Shoe Size -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_shoe_size_casual" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Casual Shoe Size
                        </label>

                        <select name="shoe_sizes[casual]" id="modal_shoe_size_casual" class="tom-select w-full" data-sort="0">
                            <option value="">Select Casual Shoe Size</option>
                            @php
                                $modalCasualShoe = old('shoe_sizes.casual', $user->details->shoe_sizes['casual'] ?? '');
                            @endphp
                            @foreach (config('constants.shoe_sizes', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalCasualShoe === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Formal Shoe Size -->
                    <div class="flex flex-col gap-2">
                        <label for="modal_shoe_size_formal" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Formal Shoe Size
                        </label>

                        <select name="shoe_sizes[formal]" id="modal_shoe_size_formal" class="tom-select w-full" data-sort="0">
                            <option value="">Select Formal Shoe Size</option>
                            @php
                                $modalFormalShoe = old('shoe_sizes.formal', $user->details->shoe_sizes['formal'] ?? '');
                            @endphp
                            @foreach (config('constants.shoe_sizes', []) as $key => $label)
                                <option value="{{ $key }}" {{ (string) $modalFormalShoe === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8 pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white">
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
