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
                        <p class="text-gray-700 dark:text-bgray-50">{{ optional($user->details->dob)->format($globalDateFormat) ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->whatsapp ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Gender</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ ucfirst($user->details->gender ?? '-') }}</p>
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
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->getRoleNameAttribute() ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Department</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->department->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->designation->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporting To</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->reporter->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Manager</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->manager->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Employee ID</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->employee_id ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date of Joining</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ optional($user->details->joining_date)->format($globalDateFormat) ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Emergency Contact
                </h4>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Person</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->contact_person ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Number</label>
                        <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->contact_person_number ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="col-span-full mb-6 border-b pb-4 text-xl font-bold text-gray-800 dark:border-darkblack-400 dark:text-white">
                    Address
                </h4>
                <div class="mt-6">
                    <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">Address</label>
                    <p class="text-gray-700 dark:text-bgray-50">{{ $user->details->address ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
