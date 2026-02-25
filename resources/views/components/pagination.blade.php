<div class="pagination-content w-full">
    <div class="flex w-full items-center justify-center lg:justify-between">
        <div class="hidden items-center space-x-4 lg:flex">
            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50">Show result:</span>
            <div class="relative">
                <button onclick="dateFilterAction('#result-filter')" type="button" class="flex items-center space-x-6 rounded-lg border border-bgray-300 px-2.5 py-[14px] dark:border-darkblack-400">
                    <span class="text-sm font-semibold text-bgray-900 dark:text-bgray-50">3</span>
                    <span>
                        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.03516 6.03271L8.03516 10.0327L12.0352 6.03271" stroke="#A0AEC0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </button>
                <div id="result-filter" class="absolute right-0 top-14 z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg">
                    <ul>
                        <li onclick="dateFilterAction('#result-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-medium hover:bg-bgray-100">
                            1
                        </li>
                        <li onclick="dateFilterAction('#result-filter')" class="cursor-pointer px-5 py-2 text-sm font-medium text-bgray-900 hover:bg-bgray-100">
                            2
                        </li>

                        <li onclick="dateFilterAction('#result-filter')" class="cursor-pointer px-5 py-2 text-sm font-medium text-bgray-900 hover:bg-bgray-100">
                            3
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-5 sm:space-x-[35px]">
            <button type="button">
                <span>
                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.7217 5.03271L7.72168 10.0327L12.7217 15.0327" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </button>
            <div class="flex items-center">
                <button type="button" class="rounded-lg bg-success-50 px-4 py-1.5 text-xs font-bold text-success-300 dark:bg-darkblack-500 dark:text-bgray-50 lg:px-6 lg:py-2.5 lg:text-sm">
                    1
                </button>
                <button type="button" class="rounded-lg px-4 py-1.5 text-xs font-bold text-bgray-500 transition duration-300 ease-in-out hover:bg-success-50 hover:text-success-300 dark:hover:bg-darkblack-500 lg:px-6 lg:py-2.5 lg:text-sm">
                    2
                </button>

                <span class="text-sm text-bgray-500">. . . .</span>
                <button type="button" class="rounded-lg px-4 py-1.5 text-xs font-bold text-bgray-500 transition duration-300 ease-in-out hover:bg-success-50 hover:text-success-300 dark:hover:bg-darkblack-500 lg:px-6 lg:py-2.5 lg:text-sm">
                    20
                </button>
            </div>
            <button type="button">
                <span>
                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.72168 5.03271L12.7217 10.0327L7.72168 15.0327" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </button>
        </div>
    </div>
</div>
