<form action="{{ isset($customer) ? route('customers.update', $customer->id) : route('customers.store') }}" method="POST" class="space-y-10">
    @csrf
    @if (isset($customer))
        @method('PUT')
    @endif

    <!-- ================= BASIC COMPANY INFORMATION ================= -->
    <div class="flex flex-col md:flex-row gap-8 border-b pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <h3 class="col-span-full text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
                Company Information
            </h3>

            <!-- Customer Code -->
            <div class="flex flex-col gap-2">
                <label for="customer_code" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Customer Code</label>
                <input type="text" id="customer_code" name="customer_code" disabled value="{{ $customerCode }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 text-gray-900 bg-bgray-100 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
            </div>

            <!-- Company Name -->
            <div class="flex flex-col gap-2">
                <label for="company_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $customer->company_name ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('company_name') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('company_name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Company Email -->
            <div class="flex flex-col gap-2">
                <label for="company_email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Email</label>
                <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $customer->company_email ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('company_email') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('company_email')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Industry -->
            <div class="flex flex-col gap-2">
                <label for="industry_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Industry</label>
                <select name="industry_id" id="industry_id" class="tom-select w-full @error('industry_id') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" data-sort="0">
                    <option value="">Select Industry</option>
                    @foreach ($industries as $industry)
                        <option value="{{ $industry->id }}" {{ old('industry_id', $customer->industry_id ?? '') == $industry->id ? 'selected' : '' }}>{{ $industry->name }}</option>
                    @endforeach
                </select>
                @error('industry_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Website -->
            <div class="flex flex-col gap-2">
                <label for="website" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Website</label>
                <input type="text" id="website" name="website" value="{{ old('website', $customer->website ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('website') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('website')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sales Person -->
            <div class="flex flex-col gap-2">
                <label for="sales_person" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Sales Person</label>
                <input type="text" id="sales_person" name="sales_person" value="{{ old('sales_person', $customer->sales_person ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('sales_person') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('sales_person')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- ================= LOCATION DETAILS ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">Location Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Registered Country -->
            <div class="flex flex-col gap-2">
                <label for="registered_country_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Registered Country</label>
                <select name="registered_country_id" id="registered_country_id" class="tom-select-lazy w-full @error('registered_country_id') border-b-alertsErrorBase @enderror" data-placeholder="Start typing to search..." data-sort="0" data-route="{{ route('countries.search') }}">
                    <option value="{{ old('registered_country_id', $customer->registered_country_id ?? '') }}" selected hidden>
                        {{ old('registered_country_name', $customer->registered_country_name ?? 'Select Country') }}
                    </option>
                </select>
                @error('registered_country_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Emirate -->
            <div class="flex flex-col gap-2">
                <label for="emirate" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Emirate</label>

                <select name="emirate" id="emirate" class="select-no-search w-full @error('emirate') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" data-sort="1">
                    <option value="">Select Emirate</option>
                    @foreach ($emirates as $id => $emirate)
                        <option value="{{ $id }}" {{ old('emirate', $customer->emirate ?? '') == $id ? 'selected' : '' }}>
                            {{ $emirate }}
                        </option>
                    @endforeach
                </select>
                @error('emirate')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Google Map Link -->
            <div class="flex flex-col gap-2">
                <label for="google_map_link" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Google Map Link</label>
                <input type="text" id="google_map_link" name="google_map_link" value="{{ old('google_map_link', $customer->google_map_link ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('google_map_link') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('google_map_link')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- ================= ADDITIONAL INFORMATION ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">Additional Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Company Address -->
            <div class="flex flex-col gap-2 md:col-span-2">
                <label for="company_address" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Address</label>
                <textarea name="company_address" id="company_address" rows="3" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('company_address') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror">{{ old('company_address', $customer->company_address ?? '') }}</textarea>
                @error('company_address')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- New to Company -->
            <div class="flex flex-col gap-2">
                <label class="inline-flex items-center space-x-2 text-base font-medium text-bgray-600 dark:text-bgray-50">
                    <input type="checkbox" name="new_to_company" value="1" {{ old('new_to_company', $customer->new_to_company ?? 1) ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 @error('new_to_company') border-b-alertsErrorBase @enderror">
                    <span>New to Company</span>
                </label>
                @error('new_to_company')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    <!-- ================= PRIMARY CONTACT ================= -->
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">Primary Contact Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Contact Name -->
            <div class="flex flex-col gap-2">
                <label for="contact_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Name</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $customer->contact_name ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_name') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Email -->
            <div class="flex flex-col gap-2">
                <label for="contact_email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $customer->contact_email ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_email') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_email')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Designation -->
            <div class="flex flex-col gap-2">
                <label for="contact_designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
                <input type="text" id="contact_designation" name="contact_designation" value="{{ old('contact_designation', $customer->contact_designation ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_designation') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_designation')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Mobile -->
            <div class="flex flex-col gap-2">
                <label for="contact_mobile" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Mobile Number</label>
                <input type="text" id="contact_mobile" name="contact_mobile" value="{{ old('contact_mobile', $customer->contact_mobile ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_mobile') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_mobile')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Landline -->
            <div class="flex flex-col gap-2">
                <label for="contact_landline" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Landline</label>
                <input type="text" id="contact_landline" name="contact_landline" value="{{ old('contact_landline', $customer->contact_landline ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_landline') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_landline')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- WhatsApp -->
            <div class="flex flex-col gap-2">
                <label for="contact_whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp Number</label>
                <input type="text" id="contact_whatsapp" name="contact_whatsapp" value="{{ old('contact_whatsapp', $customer->contact_whatsapp ?? '') }}" class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white @error('contact_whatsapp') border-b-alertsErrorBase @else border-gray-300 dark:border-darkblack-400 @enderror" />
                @error('contact_whatsapp')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- ================= SUBMIT BUTTON ================= -->
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400 dark:text-white">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">
            @if (isset($customer))
                Update Customer
            @else
                Create Customer
            @endif
        </button>
    </div>
</form>
