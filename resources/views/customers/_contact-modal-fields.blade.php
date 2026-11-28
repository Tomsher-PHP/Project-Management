<div class="grid grid-cols-1 gap-4 md:grid-cols-2">

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

    <div class="md:col-span-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50">
        Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).
    </div>

    <div class="flex flex-col gap-2">
        <label for="extra_contact_email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</label>
        <input type="email" id="extra_contact_email" name="email" placeholder="Enter email address" autocomplete="email" class="w-full rounded-lg border border-gray-300 bg-white p-2 text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" oninput="this.value = this.value.toLowerCase()">
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
</div>
