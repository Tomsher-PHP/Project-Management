<div id="flow-switcher" class="inline-flex rounded-lg border sm:ml-auto">
    <div class="relative inline-flex">
        <button data-flow="agile" class="flow-btn rounded-l-lg px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
            Agile
        </button>
        <span class="@if ($agileNewTaskCount <= 0) hidden @endif pointer-events-none absolute -right-1 -top-1 z-10 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
            data-flow-count="agile">{{ $agileNewTaskCount }}</span>
    </div>
    <div class="relative inline-flex">
        <button data-flow="linear" class="flow-btn rounded-r-lg px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
            Linear
        </button>
        <span class="@if ($linearNewTaskCount <= 0) hidden @endif pointer-events-none absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
            data-flow-count="linear">{{ $linearNewTaskCount }}</span>
    </div>
</div>
