@props(['url'])
<button class="inline-flex items-center justify-center rounded-lg border border-transparent bg-white p-4 text-base text-bgray-600 transition duration-300 ease-in-out hover:border-success-300 dark:bg-darkblack-600 dark:text-white px-4 py-3" onclick="window.location='{{ $url }}'">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 6L9 12L15 18" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
    </svg>
    <span>Back</span>
</button>