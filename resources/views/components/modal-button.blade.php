<button
    type="button"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center rounded-lg border border-transparent bg-white px-4 py-3 text-base text-bgray-600 transition duration-300 ease-in-out hover:border-success-300 dark:bg-darkblack-600 dark:text-white'
    ]) }}
>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z"/>
    </svg>
</button>