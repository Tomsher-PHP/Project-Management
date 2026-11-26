<div id="overViewTab" class="tab-pane active">
    <div class="grid grid-cols-12 gap-8">
        <div class="col-span-12 space-y-10 xl:col-span-12">
            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Basic Information
                </h4>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Name</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->email ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date of Birth</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->dob?->format($globalDateFormat) ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->whatsapp ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Gender</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ ucfirst($user->details?->gender ?? '-') }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Organization Details
                </h4>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Role</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Department</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->department?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->designation?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporting To</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->reporter?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Manager</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->manager?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Employee ID</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->employee_id ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date of Joining</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->joining_date?->format($globalDateFormat) ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Address Information
                </h4>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Residence Address</label>
                        <p class="text-gray-700 dark:text-bgray-50 whitespace-pre-line">{{ $user->details?->address ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Native Address</label>
                        <p class="text-gray-700 dark:text-bgray-50 whitespace-pre-line">{{ $user->details?->native_address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Emergency Contact Information
                </h4>
                @php
                    $emergencyContacts = $user->details?->emergency_contacts ?? [];
                    if (empty($emergencyContacts) && (!empty($user->details?->contact_person) || !empty($user->details?->contact_person_number))) {
                        $emergencyContacts = [
                            [
                                'name' => $user->details->contact_person ?? '',
                                'number' => $user->details->contact_person_number ?? '',
                                'relation' => '-',
                            ],
                        ];
                    }
                @endphp
                @if (!empty($emergencyContacts))
                    <div class="mt-6 space-y-4">
                        @foreach ($emergencyContacts as $contact)
                            <div class="rounded-lg border border-bgray-200 dark:border-darkblack-400 p-4 bg-gray-50 dark:bg-darkblack-500">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Name</label>
                                        <p class="text-gray-700 dark:text-bgray-50 font-medium">{{ $contact['name'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Number</label>
                                        <p class="text-gray-700 dark:text-bgray-50">{{ $contact['number'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Relation</label>
                                        <p class="text-gray-700 dark:text-bgray-50">{{ $contact['relation'] ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-6 text-gray-700 dark:text-bgray-50">-</p>
                @endif
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Native Contact Information
                </h4>
                @php
                    $nativeContacts = $user->details?->native_contacts ?? [];
                @endphp
                @if (!empty($nativeContacts))
                    <div class="mt-6 space-y-4">
                        @foreach ($nativeContacts as $contact)
                            <div class="rounded-lg border border-bgray-200 dark:border-darkblack-400 p-4 bg-gray-50 dark:bg-darkblack-500">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Name</label>
                                        <p class="text-gray-700 dark:text-bgray-50 font-medium">{{ $contact['name'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Number</label>
                                        <p class="text-gray-700 dark:text-bgray-50">{{ $contact['number'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Relation</label>
                                        <p class="text-gray-700 dark:text-bgray-50">{{ $contact['relation'] ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-6 text-gray-700 dark:text-bgray-50">-</p>
                @endif
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Personal Information & Preferences
                </h4>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Marital Status</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->marital_status ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Children Count</label>
                        <p class="text-gray-700 dark:text-bgray-50">
                            @if (isset($user->details?->children_count))
                                {{ config('constants.children_counts.' . $user->details->children_count, $user->details->children_count) }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Favorite Color</label>
                        <p class="text-gray-700 dark:text-bgray-50">
                            @if ($user->details?->favorite_color)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block h-3.5 w-3.5 rounded-full border border-gray-300 shadow-sm" style="background-color: {{ strtolower($user->details->favorite_color) }};"></span>
                                    {{ $user->details->favorite_color }}
                                </span>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Casual Shirt Size</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->shirt_sizes['casual'] ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Formal Shirt Size</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->shirt_sizes['formal'] ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Casual Shoe Size</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->shoe_sizes['casual'] ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Formal Shoe Size</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details?->shoe_sizes['formal'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
