<template id="contact-template">
    <div class="relative bg-white dark:bg-darkblack-600 border border-success-300 dark:border-darkblack-400 rounded-xl p-4 shadow-sm hover:shadow-md transition contact-item">
        <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-contact">✕</button>

        <!-- Name -->
        <h4 class="text-lg font-semibold text-gray-800 dark:text-white contact-name"></h4>

        <!-- Email -->
        <p>
            <span class="font-medium text-gray-700 dark:text-gray-200">Email:</span>
            <span class="contact-email">--</span>
        </p>

        <!-- Divider -->
        <div class="my-3 border-t border-gray-200 dark:border-darkblack-400"></div>

        <!-- Info -->
        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">

            <p>
                <span class="font-medium text-gray-700 dark:text-gray-200">Designation:</span>
                <span class="contact-designation">--</span>
            </p>

            <p>
                <span class="font-medium text-gray-700 dark:text-gray-200">Mobile:</span>
                <span class="contact-mobile">--</span>
            </p>

            <p>
                <span class="font-medium text-gray-700 dark:text-gray-200">Landline:</span>
                <span class="contact-landline">--</span>
            </p>

            <p>
                <span class="font-medium text-gray-700 dark:text-gray-200">WhatsApp:</span>
                <span class="contact-whatsapp">--</span>
            </p>

        </div>

        <input type="hidden" class="contact-name-input">
        <input type="hidden" class="contact-email-input">
        <input type="hidden" class="contact-designation-input">
        <input type="hidden" class="contact-mobile-input">
        <input type="hidden" class="contact-landline-input">
        <input type="hidden" class="contact-whatsapp-input">
    </div>
</template>

<!-- Existing Contacts for edit form -->
@if (isset($customer) && $customer->extraContacts->count() > 0)
    @foreach ($customer->extraContacts as $key => $contact)
        <div class="relative bg-white dark:bg-darkblack-600 border border-success-300 dark:border-darkblack-400 rounded-xl p-4 shadow-sm hover:shadow-md transition contact-item">
            <button type="button" class="absolute top-2 right-10 text-gray-400 hover:text-blue-500 edit-contact" data-index="{{ $loop->index }}">edit</button>
            <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 remove-contact">✕</button>

            <!-- Name -->
            <h4 class="text-lg font-semibold text-gray-800 dark:text-white contact-name">{{ $contact->name }}</h4>

            <!-- Email -->
            <p>
                <span class="font-medium text-gray-700 dark:text-gray-200">Email:</span>
                <span class="contact-email">{{ $contact->email }}</span>
            </p>

            <!-- Divider -->
            <div class="my-3 border-t border-gray-200 dark:border-darkblack-400"></div>

            <!-- Info -->
            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">

                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-200">Designation:</span>
                    <span class="contact-designation">{{ $contact->designation }}</span>
                </p>

                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-200">Mobile:</span>
                    <span class="contact-mobile">{{ $contact->mobile }}</span>
                </p>

                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-200">Landline:</span>
                    <span class="contact-landline">{{ $contact->landline }}</span>
                </p>

                <p>
                    <span class="font-medium text-gray-700 dark:text-gray-200">WhatsApp:</span>
                    <span class="contact-whatsapp">{{ $contact->whatsapp }}</span>
                </p>

            </div>

            <input type="hidden" class="contact-id-input" name="contacts[{{ $key }}][id]" value="{{ $contact->id }}">
            <input type="hidden" class="contact-name-input" name="contacts[{{ $key }}][name]" value="{{ $contact->name }}">
            <input type="hidden" class="contact-email-input" name="contacts[{{ $key }}][email]" value="{{ $contact->email }}">
            <input type="hidden" class="contact-designation-input" name="contacts[{{ $key }}][designation]" value="{{ $contact->designation }}">
            <input type="hidden" class="contact-mobile-input" name="contacts[{{ $key }}][mobile]" value="{{ $contact->mobile }}">
            <input type="hidden" class="contact-landline-input" name="contacts[{{ $key }}][landline]" value="{{ $contact->landline }}">
            <input type="hidden" class="contact-whatsapp-input" name="contacts[{{ $key }}][whatsapp]" value="{{ $contact->whatsapp }}">
        </div>
    @endforeach
@endif
