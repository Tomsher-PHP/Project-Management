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
                <input type="text" id="customer_code" name="customer_code" disabled value="{{ $customerCode }}" placeholder="Auto-generated customer code" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 text-gray-900 bg-bgray-100 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
            </div>

            <!-- Company Name -->
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Name <x-red-star /></label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" placeholder="Enter company name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Company Email -->
            <x-forms.email-input label="Company Email" name="email" id="email" :value="old('email', $customer->email ?? '')" placeholder="Enter company email" domain-suffix="@gmail.com" />

            <!-- Industry -->
            <div class="flex flex-col gap-2">
                <label for="industry_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Industry</label>

                <div class="flex items-center gap-2">
                    <select name="industry_id" id="industry_id" class="tom-select w-full border-gray-300 dark:border-darkblack-400" data-sort="0">
                        <option value="">Select Industry</option>
                        @foreach ($industries as $industry)
                            <option value="{{ $industry->id }}" {{ old('industry_id', $customer->industry_id ?? '') == $industry->id ? 'selected' : '' }}>{{ $industry->name }}</option>
                        @endforeach
                    </select>

                    @can('industry.create')
                        <button type="button" data-target="#customer-industry-modal" data-select-target="industry_id" data-milestone="Industry" data-url="{{ route('settings.industries.store') }}" data-method="POST" data-sort_order="{{ $nextIndustrySortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Industry" aria-label="Add Industry">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    @endcan
                </div>

                @error('industry_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Website -->
            <div class="flex flex-col gap-2">
                <label for="website" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Website</label>
                <input type="text" id="website" name="website" value="{{ old('website', $customer->website ?? '') }}" placeholder="https://example.com" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('website')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sales Person -->
            <div class="flex flex-col gap-2">
                <label for="sales_person_id" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Sales Person <x-red-star /></label>
                <select name="sales_person_id" id="sales_person_id" class="tom-select w-full border-gray-300 dark:border-darkblack-400" data-sort="0">
                    <option value="">Select Sales Person</option>
                    @foreach ($salesPeople as $salesPerson)
                        <option value="{{ $salesPerson->id }}" {{ old('sales_person_id', $customer->sales_person_id ?? '') == $salesPerson->id ? 'selected' : '' }}>
                            {{ $salesPerson->name }}
                        </option>
                    @endforeach
                </select>
                @error('sales_person_id')
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
                <select name="registered_country_id" id="registered_country_id" class="tom-select-lazy w-full border-gray-300 dark:border-darkblack-400" data-placeholder="Start typing to search..." data-sort="0" data-route="{{ route('countries.search') }}">

                    @if (old('registered_country_id', $customer->registered_country_id ?? false))
                        <option value="{{ old('registered_country_id', $customer->registered_country_id ?? '') }}" selected>
                            {{ old('registered_country_name', $customer->country->name ?? '') }}
                        </option>
                    @endif

                </select>
                @error('registered_country_id')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Emirate -->
            <div class="flex flex-col gap-2">
                <label for="emirate" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Emirate</label>

                <select name="emirate" id="emirate" class="tom-select-no-search w-full border-gray-300 dark:border-darkblack-400" data-sort="1">
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
                <input type="text" id="google_map_link" name="google_map_link" value="{{ old('google_map_link', $customer->google_map_link ?? '') }}" placeholder="https://maps.google.com/..." class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
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
                <textarea name="company_address" id="company_address" rows="3" placeholder="Enter company address" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">{{ old('company_address', $customer->company_address ?? '') }}</textarea>
                @error('company_address')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- New to Company -->
            <div class="flex flex-col gap-2">
                <label class="inline-flex items-center space-x-2 text-base font-medium text-bgray-600 dark:text-bgray-50">
                    <input type="checkbox" name="new_to_company" value="1" {{ old('new_to_company', $customer->new_to_company ?? 1) ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
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
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">Contact Information</h3>
        <!-- para for primary contact -->
        <p class="text-base font-medium text-bgray-600 dark:text-bgray-50">Primary point of contact information for this customer.</p>
        <div class="h-4"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Contact Name -->
            <div class="flex flex-col gap-2">
                <label for="contact_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Contact Name <x-red-star /></label>
                <input type="text" id="contact_name" name="primary_name" value="{{ old('primary_name', $customer->primaryContact->name ?? '') }}" placeholder="Enter contact name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('primary_name')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Email -->
            <x-forms.email-input label="Contact Email" name="primary_email" id="contact_email" :value="old('primary_email', $customer->primaryContact->email ?? '')" placeholder="Enter contact email" domain-suffix="@gmail.com" />

            <!-- Designation -->
            <div class="flex flex-col gap-2">
                <label for="contact_designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</label>
                <input type="text" id="contact_designation" name="primary_designation" value="{{ old('primary_designation', $customer->primaryContact->designation ?? '') }}" placeholder="Enter designation" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('primary_designation')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Mobile -->
            <div class="flex flex-col gap-2">
                <label for="contact_mobile" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Mobile Number</label>
                <input type="text" id="contact_mobile" name="primary_mobile" value="{{ old('primary_mobile', $customer->primaryContact->mobile ?? '') }}" placeholder="Enter mobile number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('primary_mobile')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Landline -->
            <div class="flex flex-col gap-2">
                <label for="contact_landline" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Landline</label>
                <input type="text" id="contact_landline" name="primary_landline" value="{{ old('primary_landline', $customer->primaryContact->landline ?? '') }}" placeholder="Enter landline" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('primary_landline')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- WhatsApp -->
            <div class="flex flex-col gap-2">
                <label for="contact_whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">WhatsApp Number</label>
                <input type="text" id="contact_whatsapp" name="primary_whatsapp" value="{{ old('primary_whatsapp', $customer->primaryContact->whatsapp ?? '') }}" placeholder="Enter WhatsApp number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" />
                @error('primary_whatsapp')
                    <p class="mt-1 text-sm text-error-300">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="pt-6 border-t flex justify-left dark:border-darkblack-400 dark:text-white">
            <button type="button" data-target="#multi-step-modal" data-module="Extra Contact" class="modal-open px-4 py-2 bg-basicWhite text-white rounded-lg text-sm hover:bg-bgray-600 transition">
                + Extra Contacts
            </button>
        </div>

    </div>

    <div id="extraContactsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @include('customers._contact-template-card', ['customer' => $customer])
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

@can('industry.create')
    <x-form-modal modalId="customer-industry-modal" module="Industry" formId="customerIndustryForm" action="{{ route('settings.industries.store') }}" button="Create Industry">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Parent Industry</label>
            <select name="parent_id" id="customer_industry_parent_id" class="tom-select w-full" data-sort="0">
                <option value="">Select Parent Industry</option>
                @foreach (($parentIndustries ?? []) as $parentIndustry)
                    <option value="{{ $parentIndustry->id }}">{{ $parentIndustry->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>
    </x-form-modal>
@endcan
