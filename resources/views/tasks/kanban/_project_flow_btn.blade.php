<div id="flow-switcher" class="inline-flex rounded-lg border sm:ml-auto">
    <div class="relative inline-flex">
        <button data-flow="agile" class="flow-btn rounded-l-lg px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
            Agile
        </button>
        @if ($agileNewTaskCount > 0)
            <span class="pointer-events-none absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white z-10">
                {{ $agileNewTaskCount }}
            </span>
        @endif
    </div>
    <div class="relative inline-flex">
        <button data-flow="linear" class="flow-btn rounded-r-lg px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
            Linear
        </button>
        @if ($linearNewTaskCount > 0)
            <span class="pointer-events-none absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                {{ $linearNewTaskCount }}
            </span>
        @endif
    </div>
</div>
