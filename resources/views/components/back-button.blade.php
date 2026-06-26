@props(['url'])
<button class="inline-flex items-center justify-center rounded-lg border border-bgray-500 bg-white text-base text-bgray-700 transition duration-300 ease-in-out hover:border-success-300 dark:bg-darkblack-600 dark:text-white px-2 py-2" onclick="window.location='{{ $url }}'">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
    </svg>
    <span>Back</span>
</button>
