<template id="contact-template">
    <div class="contact-item relative rounded-xl border border-success-300 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600">
        <button type="button" class="edit-contact absolute right-10 top-2 text-bgray-400 dark:text-bgray-300" data-index="">Edit</button>
        <button type="button" class="remove-contact absolute right-2 top-2 text-bgray-400 dark:text-bgray-300">✕</button>

        <div class="pr-20">
            <h4 class="contact-name text-lg font-semibold text-bgray-800 dark:text-bgray-300"></h4>
        </div>

        <p class="mt-2"><span class="font-medium text-bgray-700 dark:text-bgray-300">Email:</span> <span class="contact-email">--</span></p>
        <div class="my-3 border-t border-gray-200 dark:border-darkblack-400"></div>

        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
            <p><span class="font-medium text-bgray-700 dark:text-bgray-300">Designation:</span> <span class="contact-designation">--</span></p>
            <p><span class="font-medium text-bgray-700 dark:text-bgray-300">Mobile:</span> <span class="contact-mobile">--</span></p>
            <p><span class="font-medium text-bgray-700 dark:text-bgray-300">Landline:</span> <span class="contact-landline">--</span></p>
            <p><span class="font-medium text-bgray-700 dark:text-bgray-300">WhatsApp:</span> <span class="contact-whatsapp">--</span></p>
        </div>

        <input type="hidden" class="contact-name-input">
        <input type="hidden" class="contact-email-input">
        <input type="hidden" class="contact-designation-input">
        <input type="hidden" class="contact-mobile-input">
        <input type="hidden" class="contact-landline-input">
        <input type="hidden" class="contact-whatsapp-input">
    </div>
</template>

@php
    $contacts = old('contacts') ?? (isset($customer) ? $customer->extraContacts->toArray() : []);
@endphp

@foreach ($contacts as $key => $contact)
    <div class="contact-item relative rounded-xl border border-success-300 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600" data-contact-id="{{ $contact['id'] ?? '' }}">
        <button type="button" class="edit-contact absolute right-10 top-2 text-bgray-900 dark:text-bgray-300" data-index="{{ $key }}">Edit</button>
        <button type="button" class="remove-contact absolute right-2 top-2 text-bgray-900 dark:text-bgray-300">✕</button>

        <div class="pr-20">
            <h4 class="contact-name text-lg font-semibold text-bgray-800 dark:text-bgray-300">{{ $contact['name'] ?? '' }}</h4>
        </div>

        <p class="mt-2"><span class="font-medium text-bgray-800 dark:text-bgray-300">Email:</span> <span class="contact-email text-bgray-800 dark:text-bgray-300">{{ filled($contact['email'] ?? null) ? $contact['email'] : '--' }}</span></p>
        <div class="my-3 border-t"></div>

        <div class="space-y-1 text-sm">
            <p><span class="font-medium text-bgray-600 dark:text-bgray-300">Designation:</span> <span class="contact-designation text-bgray-800 dark:text-bgray-300">{{ filled($contact['designation'] ?? null) ? $contact['designation'] : '--' }}</span></p>
            <p><span class="font-medium text-bgray-600 dark:text-bgray-300">Mobile:</span> <span class="contact-mobile text-bgray-800 dark:text-bgray-300">{{ filled($contact['mobile'] ?? null) ? $contact['mobile'] : '--' }}</span></p>
            <p><span class="font-medium text-bgray-600 dark:text-bgray-300">Landline:</span> <span class="contact-landline text-bgray-800 dark:text-bgray-300">{{ filled($contact['landline'] ?? null) ? $contact['landline'] : '--' }}</span></p>
            <p><span class="font-medium text-bgray-600 dark:text-bgray-300">WhatsApp:</span> <span class="contact-whatsapp text-bgray-800 dark:text-bgray-300">{{ filled($contact['whatsapp'] ?? null) ? $contact['whatsapp'] : '--' }}</span></p>
        </div>

        @if (isset($contact['id']))
            <input type="hidden" class="contact-id-input" name="contacts[{{ $key }}][id]" value="{{ $contact['id'] }}">
        @endif
        <input type="hidden" class="contact-name-input " name="contacts[{{ $key }}][name]" value="{{ $contact['name'] ?? '' }}">
        <input type="hidden" class="contact-email-input" name="contacts[{{ $key }}][email]" value="{{ $contact['email'] ?? '' }}">
        <input type="hidden" class="contact-designation-input" name="contacts[{{ $key }}][designation]" value="{{ $contact['designation'] ?? '' }}">
        <input type="hidden" class="contact-mobile-input" name="contacts[{{ $key }}][mobile]" value="{{ $contact['mobile'] ?? '' }}">
        <input type="hidden" class="contact-landline-input" name="contacts[{{ $key }}][landline]" value="{{ $contact['landline'] ?? '' }}">
        <input type="hidden" class="contact-whatsapp-input" name="contacts[{{ $key }}][whatsapp]" value="{{ $contact['whatsapp'] ?? '' }}">
    </div>
@endforeach
