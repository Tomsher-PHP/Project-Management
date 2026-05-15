
<div class="relative w-full md:w-80">

    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-4 w-4 text-bgray-400" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>

    <input
        type="text"
        class="table-search w-full rounded-lg border border-bgray-200 bg-white pl-10 pr-3 py-2 text-sm
               text-bgray-700 shadow-sm focus:border-success-300 focus:outline-none
               dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50"
        placeholder="{{ $placeholder }}"
        data-target="{{ $target }}"
    >

</div>