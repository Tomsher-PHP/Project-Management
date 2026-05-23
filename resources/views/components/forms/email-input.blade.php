@props([
    'label' => 'Email',
    'name' => 'email',
    'id' => 'email',
    'value' => '',
    'required' => false,
    'disabled' => false,
    'placeholder' => 'Enter email address',
    'errorKey' => null,
    'domainSuffix' => null,
])

@php
    $errorKey = $errorKey ?? $name;
    $resolvedDomainSuffix = $domainSuffix ?? ($globalEmailSuffix ?? null);
    $inputClasses = $errors->has($errorKey) ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-darkblack-400';
@endphp

<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between gap-3">
        <label for="{{ $id }}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
            {{ $label }}
            @if ($required)
                <x-red-star />
            @endif
        </label>

        @if (filled($resolvedDomainSuffix))
            @if ($disabled)
                <span class="text-xs font-semibold text-bgray-500 dark:text-bgray-300">
                    {{ $resolvedDomainSuffix }}
                </span>
            @else
                <button type="button" class="text-xs font-semibold text-success-400 transition hover:text-success-500" data-email-domain-shortcut data-email-target="{{ $id }}" data-email-domain="{{ $resolvedDomainSuffix }}">
                    {{ $resolvedDomainSuffix }}
                </button>
            @endif
        @endif
    </div>

    <input type="email" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" class="w-full rounded-lg border p-2 text-gray-900 focus:border-success-300 focus:ring-0 disabled:cursor-not-allowed disabled:border-bgray-200 @if($disabled) bg-bgray-200 @else bg-white @endif disabled:text-bgray-500 dark:bg-darkblack-500 dark:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-600 dark:disabled:text-bgray-400 {{ $inputClasses }}" oninput="this.value = this.value.toLowerCase()" @disabled($disabled)>

    @error($errorKey)
        <p class="mt-2 text-sm text-error-300">
            {{ $message }}
        </p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function(event) {
                const trigger = event.target.closest('[data-email-domain-shortcut]');

                if (!trigger) {
                    return;
                }

                const fieldId = trigger.dataset.emailTarget;
                const domain = String(trigger.dataset.emailDomain || '').trim().toLowerCase();
                const field = document.getElementById(fieldId);

                if (!field || field.disabled || !domain) {
                    return;
                }

                const currentValue = String(field.value || '').trim().toLowerCase();

                if (!currentValue) {
                    field.focus();
                    return;
                }

                if (currentValue.endsWith(domain)) {
                    field.focus();
                    return;
                }

                const nextValue = currentValue.includes('@') ?
                    `${currentValue.split('@')[0]}${domain}` :
                    `${currentValue}${domain}`;

                field.value = nextValue;
                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.focus();

                if (typeof field.setSelectionRange === 'function') {
                    field.setSelectionRange(nextValue.length, nextValue.length);
                }
            });
        </script>
    @endpush
@endonce
