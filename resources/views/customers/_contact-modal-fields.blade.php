<div class="grid grid-cols-1 gap-4 md:grid-cols-2" data-login-scope>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
            Name <x-red-star />
        </label>
        <input type="text" id="extra_contact_name" name="name" placeholder="Enter name" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
        <input type="text" id="extra_contact_designation" name="designation" placeholder="Enter designation" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>

    <div class="md:col-span-2 flex flex-wrap items-center gap-3 border-y border-bgray-200 py-4 dark:border-darkblack-400">
        <label class="inline-flex cursor-pointer items-center gap-3 text-base font-semibold text-bgray-700 dark:text-bgray-50">
            <input type="checkbox" name="login_enabled" value="1" data-login-toggle class="h-5 w-5 rounded border-bgray-300 text-success-300 focus:ring-success-200">
            <span>Login access?</span>
        </label>
        <span class="text-sm text-bgray-600 dark:text-bgray-300">Enable portal access for this contact.</span>
    </div>

    <div class="md:col-span-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50">
        Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
            Email <span data-login-email-required class="hidden text-error-300">*</span>
        </label>
        <input type="email" id="extra_contact_email" name="email" data-login-email placeholder="Enter email address" autocomplete="email" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" oninput="this.value = this.value.toLowerCase()">
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_mobile" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Mobile Number</label>
        <input type="text" id="extra_contact_mobile" name="mobile" placeholder="Enter mobile number" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_landline" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Landline</label>
        <input type="text" id="extra_contact_landline" name="landline" placeholder="Enter landline" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp Number</label>
        <input type="text" id="extra_contact_whatsapp" name="whatsapp" placeholder="Enter WhatsApp number" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
    </div>

    <div data-login-fields class="hidden flex flex-col gap-2 md:col-span-2">
        <label for="extra_contact_password" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Password</label>
        <div class="relative">
            <input type="password" name="password" id="extra_contact_password" placeholder="Enter password (optional)" autocomplete="new-password" disabled class="w-full rounded-lg border border-gray-300 bg-white p-2 pr-11 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            <button type="button" data-password-toggle data-target="extra_contact_password" class="absolute inset-y-0 right-3 flex items-center text-bgray-500 hover:text-success-300 dark:text-bgray-50" aria-label="Show password" title="Show password">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" /><circle cx="12" cy="12" r="3" /></svg>
            </button>
        </div>
        <p class="text-xs text-bgray-600 dark:text-bgray-300">Leave blank to allow the contact to set a password later.</p>
    </div>
</div>
