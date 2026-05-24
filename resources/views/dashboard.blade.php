@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-12 xl:pb-12')

@section('page-content')
        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!-- total widget-->
                <div class="mb-[24px] w-full">
                    <div class="grid grid-cols-1 gap-[24px] lg:grid-cols-3">
                        <div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-[7px]">
                                    <div class="icon">
                                        <span>
                                            <img src="./assets/images/icons/total-earn.svg" alt="icon" />
                                        </span>
                                    </div>
                                    <span class="text-lg font-semibold text-bgray-900 dark:text-white">Total earnings</span>
                                </div>
                                <div>
                                    <img src="./assets/images/avatar/members-2.png" alt="members" />
                                </div>
                            </div>
                            <div class="flex items-end justify-between">
                                <div class="flex-1">
                                    <p class="text-3xl font-bold leading-[48px] text-bgray-900 dark:text-white">
                                        $7,245.00
                                    </p>
                                    <div class="flex items-center space-x-1">
                                        <span>
                                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.4318 0.522827L12.4446 0.522827L8.55575 0.522827L7.56859 0.522827C6.28227 0.522827 5.48082 1.91818 6.12896 3.02928L9.06056 8.05489C9.7037 9.1574 11.2967 9.1574 11.9398 8.05489L14.8714 3.02928C15.5196 1.91818 14.7181 0.522828 13.4318 0.522827Z" fill="#22C55E" />
                                                <path opacity="0.4" d="M2.16878 13.0485L3.15594 13.0485L7.04483 13.0485L8.03199 13.0485C9.31831 13.0485 10.1198 11.6531 9.47163 10.542L6.54002 5.5164C5.89689 4.41389 4.30389 4.41389 3.66076 5.5164L0.729153 10.542C0.0810147 11.6531 0.882466 13.0485 2.16878 13.0485Z" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium text-success-300">
                                            + 3.5%
                                        </span>
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            from last week
                                        </span>
                                    </div>
                                </div>
                                <div class="w-[106px]">
                                    <canvas id="totalEarn" height="68"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-[7px]">
                                    <div class="icon">
                                        <span>
                                            <img src="./assets/images/icons/total-earn.svg" alt="icon" />
                                        </span>
                                    </div>
                                    <span class="text-lg font-semibold text-bgray-900 dark:text-white">Total Spending</span>
                                </div>
                                <div>
                                    <img src="./assets/images/avatar/members-2.png" alt="members" />
                                </div>
                            </div>
                            <div class="flex items-end justify-between">
                                <div class="flex-1">
                                    <p class="text-3xl font-bold leading-[48px] text-bgray-900 dark:text-white">
                                        $7,245.00
                                    </p>
                                    <div class="flex items-center space-x-1">
                                        <span>
                                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.4318 0.522827L12.4446 0.522827L8.55575 0.522827L7.56859 0.522827C6.28227 0.522827 5.48082 1.91818 6.12896 3.02928L9.06056 8.05489C9.7037 9.1574 11.2967 9.1574 11.9398 8.05489L14.8714 3.02928C15.5196 1.91818 14.7181 0.522828 13.4318 0.522827Z" fill="#22C55E" />
                                                <path opacity="0.4" d="M2.16878 13.0485L3.15594 13.0485L7.04483 13.0485L8.03199 13.0485C9.31831 13.0485 10.1198 11.6531 9.47163 10.542L6.54002 5.5164C5.89689 4.41389 4.30389 4.41389 3.66076 5.5164L0.729153 10.542C0.0810147 11.6531 0.882466 13.0485 2.16878 13.0485Z" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium text-success-300">
                                            + 3.5%
                                        </span>
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            from last week
                                        </span>
                                    </div>
                                </div>
                                <div class="w-[106px]">
                                    <canvas id="totalSpending" height="68"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-[7px]">
                                    <div class="icon">
                                        <span>
                                            <img src="./assets/images/icons/total-earn.svg" alt="icon" />
                                        </span>
                                    </div>
                                    <span class="text-lg font-semibold text-bgray-900 dark:text-white">Spending Goal</span>
                                </div>
                                <div>
                                    <img src="./assets/images/avatar/members-2.png" alt="members" />
                                </div>
                            </div>
                            <div class="flex items-end justify-between">
                                <div class="flex-1">
                                    <p class="text-3xl font-bold leading-[48px] text-bgray-900 dark:text-white">
                                        $7,245.00
                                    </p>
                                    <div class="flex items-center space-x-1">
                                        <span>
                                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.4318 0.522827L12.4446 0.522827L8.55575 0.522827L7.56859 0.522827C6.28227 0.522827 5.48082 1.91818 6.12896 3.02928L9.06056 8.05489C9.7037 9.1574 11.2967 9.1574 11.9398 8.05489L14.8714 3.02928C15.5196 1.91818 14.7181 0.522828 13.4318 0.522827Z" fill="#22C55E" />
                                                <path opacity="0.4" d="M2.16878 13.0485L3.15594 13.0485L7.04483 13.0485L8.03199 13.0485C9.31831 13.0485 10.1198 11.6531 9.47163 10.542L6.54002 5.5164C5.89689 4.41389 4.30389 4.41389 3.66076 5.5164L0.729153 10.542C0.0810147 11.6531 0.882466 13.0485 2.16878 13.0485Z" fill="#22C55E" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium text-success-300">
                                            + 3.5%
                                        </span>
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            from last week
                                        </span>
                                    </div>
                                </div>
                                <div class="w-[106px]">
                                    <canvas id="totalGoal" height="68"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- revenue, flow -->
                <div class="mb-[24px] w-full xl:flex xl:space-x-[24px]">
                    <div class="flex w-full flex-col justify-between rounded-lg bg-white px-[24px] py-3 dark:bg-darkblack-600 xl:w-66">
                        <div class="mb-2 flex items-center justify-between border-b border-bgray-300 pb-2 dark:border-darkblack-400">
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white sm:text-2xl">
                                Revenue Flow
                            </h3>
                            <div class="hidden items-center space-x-[28px] sm:flex">
                                <div class="flex items-center space-x-2">
                                    <div class="h-3 w-3 rounded-full bg-warning-300"></div>
                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">Pending
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="h-3 w-3 rounded-full bg-success-300"></div>
                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">Signed
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="h-3 w-3 rounded-full bg-orange"></div>
                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">Lost
                                    </span>
                                </div>
                            </div>
                            <div class="date-filter relative">
                                <button onclick="dateFilterAction('#date-filter-body')" type="button" class="flex items-center space-x-1 overflow-hidden rounded-lg bg-bgray-100 px-3 py-2 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-sm font-medium text-bgray-900 dark:text-white">Jan 10 - Jan 16</span>
                                    <span>
                                        <svg class="stroke-bgray-900 dark:stroke-gray-50" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 6.5L8 10.5L12 6.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </button>
                                <div id="date-filter-body" class="absolute right-0 top-[44px] z-10 hidden overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                    <ul>
                                        <li onclick="dateFilterAction('#date-filter-body')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            Jan 10 - Jan 16
                                        </li>
                                        <li onclick="dateFilterAction('#date-filter-body')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            Jan 10 - Jan 16
                                        </li>
                                        <li onclick="dateFilterAction('#date-filter-body')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            Jan 10 - Jan 16
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="w-full">
                            <canvas id="revenueFlow" height="255"></canvas>
                        </div>
                    </div>
                    <div class="hidden flex-1 xl:block">
                        <div class="rounded-lg bg-white dark:bg-darkblack-600">
                            <div class="flex items-center justify-between border-b border-bgray-300 px-[20px] py-[12px] dark:border-darkblack-400">
                                <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                                    Efficiency
                                </h3>
                                <div class="date-filter relative">
                                    <button onclick="dateFilterAction('#month-filter')" type="button" class="flex items-center space-x-1">
                                        <span class="text-base font-semibold text-bgray-900 dark:text-white">Monthly</span>
                                        <span>
                                            <svg class="stroke-bgray-900 dark:stroke-bgray-50" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4 6.5L8 10.5L12 6.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </button>
                                    <div id="month-filter" class="absolute right-0 top-5 z-10 hidden overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                        <ul>
                                            <li onclick="dateFilterAction('#month-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                January
                                            </li>
                                            <li onclick="dateFilterAction('#month-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                February
                                            </li>

                                            <li onclick="dateFilterAction('#month-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                March
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="px-[20px] py-[12px]">
                                <div class="mb-4 flex items-center space-x-8">
                                    <div class="relative w-[180px]">
                                        <canvas id="pie_chart" height="168"></canvas>
                                        <div class="absolute z-0 h-[34px] w-[34px] rounded-full bg-[#EDF2F7]" style="
                left: calc(50% - 17px);
                top: calc(50% - 17px);
                "></div>
                                    </div>
                                    <div class="counting">
                                        <div class="mb-6">
                                            <div class="flex items-center space-x-[2px]">
                                                <p class="text-lg font-bold text-success-300">
                                                    $5,230
                                                </p>
                                                <span><svg width="14" height="12" viewBox="0 0 14 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.7749 0.558058C10.5309 0.313981 10.1351 0.313981 9.89107 0.558058L7.39107 3.05806C7.14699 3.30214 7.14699 3.69786 7.39107 3.94194C7.63514 4.18602 8.03087 4.18602 8.27495 3.94194L9.70801 2.50888V11C9.70801 11.3452 9.98783 11.625 10.333 11.625C10.6782 11.625 10.958 11.3452 10.958 11V2.50888L12.3911 3.94194C12.6351 4.18602 13.0309 4.18602 13.2749 3.94194C13.519 3.69786 13.519 3.30214 13.2749 3.05806L10.7749 0.558058Z" fill="#22C55E" />
                                                        <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M3.22407 11.4419C3.46815 11.686 3.86388 11.686 4.10796 11.4419L6.60796 8.94194C6.85203 8.69786 6.85203 8.30214 6.60796 8.05806C6.36388 7.81398 5.96815 7.81398 5.72407 8.05806L4.29102 9.49112L4.29101 1C4.29101 0.654823 4.01119 0.375001 3.66602 0.375001C3.32084 0.375001 3.04102 0.654823 3.04102 1L3.04102 9.49112L1.60796 8.05806C1.36388 7.81398 0.968151 7.81398 0.724074 8.05806C0.479996 8.30214 0.479996 8.69786 0.724074 8.94194L3.22407 11.4419Z" fill="#22C55E" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <p class="text-base font-medium text-bgray-600">
                                                Arrival
                                            </p>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-[2px]">
                                                <p class="text-lg font-bold text-bgray-900 dark:text-white">
                                                    $6,230
                                                </p>
                                                <span>
                                                    <svg class="fill-bgray-900 dark:fill-bgray-50" width="14" height="12" viewBox="0 0 14 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.7749 0.558058C10.5309 0.313981 10.1351 0.313981 9.89107 0.558058L7.39107 3.05806C7.14699 3.30214 7.14699 3.69786 7.39107 3.94194C7.63514 4.18602 8.03087 4.18602 8.27495 3.94194L9.70801 2.50888V11C9.70801 11.3452 9.98783 11.625 10.333 11.625C10.6782 11.625 10.958 11.3452 10.958 11V2.50888L12.3911 3.94194C12.6351 4.18602 13.0309 4.18602 13.2749 3.94194C13.519 3.69786 13.519 3.30214 13.2749 3.05806L10.7749 0.558058Z" />
                                                        <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M3.22407 11.4419C3.46815 11.686 3.86388 11.686 4.10796 11.4419L6.60796 8.94194C6.85203 8.69786 6.85203 8.30214 6.60796 8.05806C6.36388 7.81398 5.96815 7.81398 5.72407 8.05806L4.29102 9.49112L4.29101 1C4.29101 0.654823 4.01119 0.375001 3.66602 0.375001C3.32084 0.375001 3.04102 0.654823 3.04102 1L3.04102 9.49112L1.60796 8.05806C1.36388 7.81398 0.968151 7.81398 0.724074 8.05806C0.479996 8.30214 0.479996 8.69786 0.724074 8.94194L3.22407 11.4419Z" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <p class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Spending
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="status">
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-2.5 w-2.5 rounded-full bg-success-300"></div>
                                            <span class="text-sm font-medium text-bgray-600 dark:text-bgray-50">Goal</span>
                                        </div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-bgray-50">
                                            13%
                                        </p>
                                    </div>
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-2.5 w-2.5 rounded-full bg-warning-300"></div>
                                            <span class="text-sm font-medium text-bgray-600 dark:text-white">Spending</span>
                                        </div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-bgray-50">
                                            28%
                                        </p>
                                    </div>
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-2.5 w-2.5 rounded-full bg-bgray-200"></div>
                                            <span class="text-sm font-medium text-bgray-600 dark:text-white">Others</span>
                                        </div>
                                        <p class="text-sm font-bold text-bgray-900 dark:text-bgray-50">
                                            59%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="flex h-[56px] w-full space-x-4">
                            <div class="hidden h-full rounded-lg border border-transparent bg-bgray-100 px-[18px] focus-within:border-success-300 dark:bg-darkblack-500 sm:block sm:w-70 lg:w-88">
                                <div class="flex h-full w-full items-center space-x-[15px]">
                                    <span>
                                        <svg class="stroke-bgray-900 dark:stroke-white" width="21" height="22" viewBox="0 0 21 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="9.80204" cy="10.6761" r="8.98856" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16.0537 17.3945L19.5777 20.9094" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <label for="listSearch" class="w-full">
                                        <input type="text" id="listSearch" placeholder="Search by name, email, or others..." class="search-input w-full border-none bg-bgray-100 px-0 text-sm tracking-wide text-bgray-600 placeholder:text-sm placeholder:font-medium placeholder:text-bgray-500 focus:outline-none focus:ring-0 dark:bg-darkblack-500" />
                                    </label>
                                </div>
                            </div>
                            <div class="relative h-full flex-1">
                                <button onclick="dateFilterAction('#table-filter')" type="button" class="flex h-full w-full items-center justify-center rounded-lg border border-bgray-300 bg-bgray-100 dark:border-darkblack-500 dark:bg-darkblack-500">
                                    <div class="flex items-center space-x-3">
                                        <span>
                                            <svg class="stroke-bgray-900 dark:stroke-success-400" width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7.55169 13.5022H1.25098" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M10.3623 3.80984H16.663" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94797 3.75568C5.94797 2.46002 4.88981 1.40942 3.58482 1.40942C2.27984 1.40942 1.22168 2.46002 1.22168 3.75568C1.22168 5.05133 2.27984 6.10193 3.58482 6.10193C4.88981 6.10193 5.94797 5.05133 5.94797 3.75568Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2214 13.4632C17.2214 12.1675 16.1641 11.1169 14.8591 11.1169C13.5533 11.1169 12.4951 12.1675 12.4951 13.4632C12.4951 14.7589 13.5533 15.8095 14.8591 15.8095C16.1641 15.8095 17.2214 14.7589 17.2214 13.4632Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="text-base font-medium text-success-300">Filters</span>
                                    </div>
                                </button>
                                <div id="table-filter" class="absolute right-0 top-[60px] z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                    <ul>
                                        <li onclick="dateFilterAction('#table-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            January
                                        </li>
                                        <li onclick="dateFilterAction('#table-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            February
                                        </li>

                                        <li onclick="dateFilterAction('#table-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            March
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="filter-content w-full">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                                <div class="w-full">
                                    <p class="mb-2 text-base font-bold leading-[24px] text-bgray-900 dark:text-white">
                                        Location
                                    </p>
                                    <div class="relative h-[56px] w-full">
                                        <button onclick="dateFilterAction('#province-filter')" type="button" class="relative flex h-full w-full items-center justify-between rounded-lg bg-bgray-100 px-4 dark:bg-darkblack-500">
                                            <span class="text-base text-bgray-500">State or province</span>
                                            <span>
                                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div id="province-filter" class="absolute right-0 top-14 z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                            <ul>
                                                <li onclick="dateFilterAction('#province-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    January
                                                </li>
                                                <li onclick="dateFilterAction('#province-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    February
                                                </li>

                                                <li onclick="dateFilterAction('#province-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    March
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <p class="mb-2 text-base font-bold leading-[24px] text-bgray-900 dark:text-white">
                                        Amount Spent
                                    </p>
                                    <div class="relative h-[56px] w-full">
                                        <button onclick="dateFilterAction('#amount-filter')" type="button" class="relative flex h-full w-full items-center justify-between rounded-lg bg-bgray-100 px-4 dark:bg-darkblack-500">
                                            <span class="text-base text-bgray-500">State or province</span>
                                            <span>
                                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div id="amount-filter" class="absolute right-0 top-14 z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                            <ul>
                                                <li onclick="dateFilterAction('#amount-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    January
                                                </li>
                                                <li onclick="dateFilterAction('#amount-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    February
                                                </li>

                                                <li onclick="dateFilterAction('#amount-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    March
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <p class="mb-2 text-base font-bold leading-[24px] text-bgray-900 dark:text-white">
                                        Transaction list Date
                                    </p>
                                    <div class="relative h-[56px] w-full">
                                        <button onclick="dateFilterAction('#date-filter-table')" type="button" class="relative flex h-full w-full items-center justify-between rounded-lg bg-bgray-100 px-4 dark:bg-darkblack-500">
                                            <span class="text-base text-bgray-500">State or province</span>
                                            <span>
                                                <svg class="stroke-bgray-500 dark:stroke-white" width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M18.6758 5.8186H6.67578C5.57121 5.8186 4.67578 6.71403 4.67578 7.8186V19.8186C4.67578 20.9232 5.57121 21.8186 6.67578 21.8186H18.6758C19.7804 21.8186 20.6758 20.9232 20.6758 19.8186V7.8186C20.6758 6.71403 19.7804 5.8186 18.6758 5.8186Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.6758 3.8186V7.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M8.67578 3.8186V7.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M4.67578 11.8186H20.6758" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M11.6758 15.8186H12.6758" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.6758 15.8186V18.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div id="date-filter-table" class="absolute right-0 top-14 z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                            <ul>
                                                <li onclick="dateFilterAction('#amount-filter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    January
                                                </li>
                                                <li onclick="dateFilterAction('#amount-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    February
                                                </li>

                                                <li onclick="dateFilterAction('#amount-filter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    March
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <p class="mb-2 text-base font-bold leading-[24px] text-bgray-900 dark:text-white">
                                        Type of transaction
                                    </p>
                                    <div class="relative h-[56px] w-full">
                                        <button onclick="dateFilterAction('#trans-filter-tb')" type="button" class="relative flex h-full w-full items-center justify-between rounded-lg bg-bgray-100 px-4 dark:bg-darkblack-500">
                                            <span class="text-base text-bgray-500">State or province</span>
                                            <span>
                                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5.58203 8.3186L10.582 13.3186L15.582 8.3186" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div id="trans-filter-tb" class="absolute right-0 top-14 z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                            <ul>
                                                <li onclick="dateFilterAction('#trans-filter-tb')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    January
                                                </li>
                                                <li onclick="dateFilterAction('#trans-filter-tb')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    February
                                                </li>

                                                <li onclick="dateFilterAction('#trans-filter-tb')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                                    March
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <label class="text-center">
                                            <input type="checkbox" class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 bg-transparent text-success-300 focus:outline-none focus:ring-0" />
                                        </label>
                                    </td>
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Customer name</span>
                                            <span>
                                                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.332 1.31567V13.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M3.66602 13.3157V1.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</span>
                                            <span>
                                                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.332 1.31567V13.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M3.66602 13.3157V1.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Location</span>
                                            <span>
                                                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.332 1.31567V13.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M3.66602 13.3157V1.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="w-[165px] px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Spent</span>
                                            <span>
                                                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.332 1.31567V13.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M3.66602 13.3157V1.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0"></td>
                                </tr>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <label class="text-center">
                                            <input type="checkbox" class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 bg-transparent text-success-300 focus:outline-none focus:ring-0" />
                                        </label>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <div class="h-10 w-10 overflow-hidden rounded-full">
                                                <img src="./assets/images/avatar/user-40x40.png" alt="avatar" class="h-full w-full object-cover" />
                                            </div>
                                            <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                Devon Lane
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            devon@mail.com
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            Philadelphia, USA
                                        </p>
                                    </td>
                                    <td class="w-[165px] px-6 py-5 xl:px-0">
                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                            $101.00
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex justify-center">
                                            <button type="button">
                                                <svg width="18" height="4" viewBox="0 0 18 4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 2.00024C8 2.55253 8.44772 3.00024 9 3.00024C9.55228 3.00024 10 2.55253 10 2.00024C10 1.44796 9.55228 1.00024 9 1.00024C8.44772 1.00024 8 1.44796 8 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M1 2.00024C1 2.55253 1.44772 3.00024 2 3.00024C2.55228 3.00024 3 2.55253 3 2.00024C3 1.44796 2.55228 1.00024 2 1.00024C1.44772 1.00024 1 1.44796 1 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M15 2.00024C15 2.55253 15.4477 3.00024 16 3.00024C16.5523 3.00024 17 2.55253 17 2.00024C17 1.44796 16.5523 1.00024 16 1.00024C15.4477 1.00024 15 1.44796 15 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <label class="text-center">
                                            <input type="checkbox" class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 bg-transparent text-success-300 focus:outline-none focus:ring-0" />
                                        </label>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <div class="h-10 w-10 overflow-hidden rounded-full">
                                                <img src="./assets/images/avatar/user-40x40-1.png" alt="avatar" class="h-full w-full object-cover" />
                                            </div>
                                            <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                Bessie Cooper
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            devon@mail.com
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            Philadelphia, USA
                                        </p>
                                    </td>
                                    <td class="w-[165px] px-6 py-5 xl:px-0">
                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                            $101.00
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex justify-center">
                                            <button type="button">
                                                <svg width="18" height="4" viewBox="0 0 18 4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 2.00024C8 2.55253 8.44772 3.00024 9 3.00024C9.55228 3.00024 10 2.55253 10 2.00024C10 1.44796 9.55228 1.00024 9 1.00024C8.44772 1.00024 8 1.44796 8 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M1 2.00024C1 2.55253 1.44772 3.00024 2 3.00024C2.55228 3.00024 3 2.55253 3 2.00024C3 1.44796 2.55228 1.00024 2 1.00024C1.44772 1.00024 1 1.44796 1 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M15 2.00024C15 2.55253 15.4477 3.00024 16 3.00024C16.5523 3.00024 17 2.55253 17 2.00024C17 1.44796 16.5523 1.00024 16 1.00024C15.4477 1.00024 15 1.44796 15 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <label class="text-center">
                                            <input type="checkbox" class="h-5 w-5 cursor-pointer rounded-full border border-bgray-400 bg-transparent text-success-300 focus:outline-none focus:ring-0" />
                                        </label>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <div class="h-10 w-10 overflow-hidden rounded-full">
                                                <img src="./assets/images/avatar/user-40x40-2.png" alt="avatar" class="h-full w-full object-cover" />
                                            </div>
                                            <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                Dianne Russell
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            devon@mail.com
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <p class="text-base font-medium text-bgray-900 dark:text-white">
                                            Philadelphia, USA
                                        </p>
                                    </td>
                                    <td class="w-[165px] px-6 py-5 xl:px-0">
                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                            $101.00
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <div class="flex justify-center">
                                            <button type="button">
                                                <svg width="18" height="4" viewBox="0 0 18 4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 2.00024C8 2.55253 8.44772 3.00024 9 3.00024C9.55228 3.00024 10 2.55253 10 2.00024C10 1.44796 9.55228 1.00024 9 1.00024C8.44772 1.00024 8 1.44796 8 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M1 2.00024C1 2.55253 1.44772 3.00024 2 3.00024C2.55228 3.00024 3 2.55253 3 2.00024C3 1.44796 2.55228 1.00024 2 1.00024C1.44772 1.00024 1 1.44796 1 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M15 2.00024C15 2.55253 15.4477 3.00024 16 3.00024C16.5523 3.00024 17 2.55253 17 2.00024C17 1.44796 16.5523 1.00024 16 1.00024C15.4477 1.00024 15 1.44796 15 2.00024Z" stroke="#A0AEC0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
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
                    </div>
                </div>
            </section>
            <section class="flex w-full flex-col space-x-0 lg:flex-row lg:space-x-6 2xl:w-[400px] 2xl:flex-col 2xl:space-x-0">
                <div class="mb-6 w-full rounded-lg bg-white px-[42px] py-5 dark:border dark:border-darkblack-400 dark:bg-darkblack-600 lg:mb-0 lg:w-1/2 2xl:mb-6 2xl:w-full">
                    <div class="my-wallet mb-8 w-full">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                                My Wallet
                            </h3>
                            <div class="payment-select relative mb-3">
                                <button onclick="dateFilterAction('#cardsOptions')" type="button">
                                    <svg width="18" height="4" viewBox="0 0 18 4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8 2C8 2.55228 8.44772 3 9 3C9.55228 3 10 2.55228 10 2C10 1.44772 9.55228 1 9 1C8.44772 1 8 1.44772 8 2Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M1 2C1 2.55228 1.44772 3 2 3C2.55228 3 3 2.55228 3 2C3 1.44772 2.55228 1 2 1C1.44772 1 1 1.44772 1 2Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15 2C15 2.55228 15.4477 3 16 3C16.5523 3 17 2.55228 17 2C17 1.44772 16.5523 1 16 1C15.4477 1 15 1.44772 15 2Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div id="cardsOptions" class="absolute right-0 top-full z-10 hidden min-w-[150px] overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                    <ul>
                                        <li onclick="dateFilterAction('#cardsOptions')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            Master Card
                                        </li>
                                        <li onclick="dateFilterAction('#cardsOptions')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            VISA Card
                                        </li>
                                        <li onclick="dateFilterAction('#cardsOptions')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                            Others
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <div class="card-slider relative w-[280px] md:w-[340px]">
                                <div class="w-full">
                                    <img src="./assets/images/payments/card-1.svg" alt="card" />
                                </div>
                                <div class="w-full">
                                    <img src="./assets/images/payments/card-2.svg" alt="card" />
                                </div>
                                <div class="w-full">
                                    <img src="./assets/images/payments/card-3.svg" alt="card" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">
                            Quick Transfer
                        </h3>
                        <div class="payment-select relative mb-3">
                            <button onclick="dateFilterAction('#paymentFilter')" type="button" class="flex h-[56px] w-full items-center justify-between overflow-hidden rounded-lg border border-bgray-200 px-5 dark:border-darkblack-400">
                                <div class="flex items-center space-x-2">
                                    <span>
                                        <img src="./assets/images/payments/master-mini.svg" alt="master" />
                                    </span>
                                    <span class="text-sm font-medium text-bgray-900 dark:text-white">Debit</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-bgray-900 dark:text-bgray-50">
                                        $10,431
                                    </span>
                                    <span class="text-sm font-medium text-bgray-900">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 6L8 10L12 6" stroke="#A0AEC0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                            </button>
                            <div id="paymentFilter" class="absolute right-0 top-full z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500">
                                <ul>
                                    <li onclick="dateFilterAction('#paymentFilter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                        Jan 10 - Jan 16
                                    </li>
                                    <li onclick="dateFilterAction('#paymentFilter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                        Jan 10 - Jan 16
                                    </li>
                                    <li onclick="dateFilterAction('#paymentFilter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600">
                                        Jan 10 - Jan 16
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="flex h-[98px] w-full flex-col justify-between rounded-lg border border-bgray-200 p-4 focus-within:border-success-300 dark:border-darkblack-400">
                            <p class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                                Enter amount
                            </p>
                            <div class="flex h-[35px] w-full items-center justify-between">
                                <span class="text-2xl font-bold text-bgray-900 dark:text-white">$</span>
                                <label class="w-full">
                                    <input type="text" class="w-full border-none p-0 text-2xl font-bold text-bgray-900 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" />
                                </label>
                                <div>
                                    <img src="./assets/images/avatar/members-3.png" alt="members" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex w-full flex-col justify-between rounded-lg bg-white dark:border dark:border-darkblack-400 dark:bg-darkblack-600 lg:w-1/2 2xl:w-full">
                    <div class="flex justify-between border-b border-bgray-300 px-[26px] py-6 dark:border-darkblack-400">
                        <h1 class="text-2xl font-semibold text-bgray-900 dark:text-white">
                            Team Chat
                        </h1>
                        <div class="flex items-center space-x-3">
                            <div>
                                <img src="./assets/images/avatar/members-3.png" alt="members" />
                            </div>
                            <button type="button" class="flex h-[36px] w-[36px] items-center justify-center rounded-full bg-bgray-200">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7.75 1C7.75 0.585786 7.41421 0.25 7 0.25C6.58579 0.25 6.25 0.585786 6.25 1V6.25H1C0.585786 6.25 0.25 6.58579 0.25 7C0.25 7.41421 0.585786 7.75 1 7.75H6.25V13C6.25 13.4142 6.58579 13.75 7 13.75C7.41421 13.75 7.75 13.4142 7.75 13V7.75H13C13.4142 7.75 13.75 7.41421 13.75 7C13.75 6.58579 13.4142 6.25 13 6.25H7.75V1Z" fill="#718096" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="w-full px-5 py-6 lg:px-[35px] lg:py-[38px]">
                        <div class="mb-5 flex flex-col space-y-[32px]">
                            <div class="flex justify-start">
                                <div class="flex items-end space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-[35px] w-[36px] overflow-hidden rounded-full">
                                            <img src="./assets/images/avatar/user-1.png" alt="avater" class="h-full w-full object-cover" />
                                        </div>
                                        <div class="rounded-lg bg-bgray-100 p-3 dark:bg-darkblack-500">
                                            <p class="text-sm font-medium text-bgray-900 dark:text-white">
                                                Hi, What can I help you with?
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-bgray-500">10:00 PM</span>
                                </div>
                            </div>
                            <div class="flex justify-start">
                                <div class="flex items-end space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-[35px] w-[36px] overflow-hidden rounded-full">
                                            <img src="./assets/images/avatar/user-1.png" alt="avater" class="h-full w-full object-cover" />
                                        </div>
                                        <div>
                                            <img src="./assets/images/others/mp3.png" class="block dark:hidden" alt="mp3" />
                                            <img src="./assets/images/others/mp3-dark.png" class="hidden dark:block" alt="mp3" />
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-bgray-500">10:00 PM</span>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <div class="flex items-end space-x-3">
                                    <span class="text-xs font-medium text-bgray-500">10:00 PM</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="rounded-b-lg rounded-l-lg bg-bgray-100 p-3 dark:bg-darkblack-500">
                                            <p class="text-sm font-medium text-bgray-900 dark:text-white">
                                                Hi, What can I help you with?
                                            </p>
                                        </div>
                                        <div class="h-[35px] w-[36px] overflow-hidden rounded-full">
                                            <img src="./assets/images/avatar/user-1.png" alt="avater" class="h-full w-full object-cover" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-start">
                                <div class="flex items-end space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-[35px] w-[36px] overflow-hidden rounded-full">
                                            <img src="./assets/images/avatar/user-1.png" alt="avater" class="h-full w-full object-cover" />
                                        </div>
                                        <div class="rounded-lg bg-bgray-100 p-3 dark:bg-darkblack-500">
                                            <p class="text-sm font-medium text-bgray-900 dark:text-white">
                                                Hi, What can I help you with?
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-bgray-500">10:00 PM</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex h-[58px] w-full items-center space-x-4">
                            <div class="flex h-full w-full items-center justify-between rounded-lg border border-transparent bg-bgray-100 px-5 focus-within:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 lg:w-[318px]">
                                <span>
                                    <svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.66652 4.1112L5.22208 8.55565C4.60843 9.1693 4.60843 10.1642 5.22208 10.7779C5.83573 11.3915 6.83065 11.3915 7.4443 10.7779L11.8887 6.33343C13.116 5.10613 13.116 3.11628 11.8887 1.88898C10.6614 0.661681 8.6716 0.661681 7.4443 1.88898L2.99985 6.33343C1.1589 8.17438 1.1589 11.1591 2.99985 13.0001C4.8408 14.841 7.82557 14.841 9.66652 13.0001L14.111 8.55565" stroke="#CBD5E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <label class="w-full">
                                    <input type="text" placeholder="Type your Message..." class="w-full border-none bg-bgray-100 p-0 pl-[15px] font-medium placeholder:text-sm placeholder:font-medium placeholder:text-bgray-400 focus:outline-none focus:ring-0 dark:bg-darkblack-500 dark:text-white" />
                                </label>
                                <span>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 11V12C19 15.866 15.866 19 12 19M5 11V12C5 15.866 8.13401 19 12 19M12 19V22M12 22H15M12 22H9M12 16C9.79086 16 8 14.2091 8 12V6C8 3.79086 9.79086 2 12 2C14.2091 2 16 3.79086 16 6V12C16 14.2091 14.2091 16 12 16Z" stroke="#A0AEC0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </div>
                            <button type="button">
                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.3894 0H2.61094C0.339326 0 -0.844596 2.63548 0.696196 4.26234L3.78568 7.52441C4.23 7.99355 4.47673 8.60858 4.47673 9.24704V15.4553C4.47673 17.8735 7.61615 18.9233 9.13941 17.0145L19.4463 4.09894C20.7775 2.43071 19.5578 0 17.3894 0Z" fill="#22C55E" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
@endsection

@push('scripts')
    <script>
        //dashboard
        function totalEarn() {
            const ctx_bids = document.getElementById("totalEarn").getContext("2d");
            const bitsMonth = [
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Feb",
                "Mar",
                "Afril",
                "May",
            ];
            const bitsData = [
                0, 10, 0, 65, 0, 25, 0, 35, 20, 100, 40, 75, 50, 85, 60,
            ];
            const totalEarn = new Chart(ctx_bids, {
                type: "line",
                data: {
                    labels: bitsMonth,
                    datasets: [{
                        label: "Visitor",
                        data: bitsData,
                        backgroundColor: () => {
                            const chart = document
                                .getElementById("totalEarn")
                                .getContext("2d");
                            const gradient = chart.createLinearGradient(0, 0, 0, 450);
                            gradient.addColorStop(0, "rgba(34, 197, 94,0.41)");
                            gradient.addColorStop(0.2, "rgba(255, 255, 255, 0)");

                            return gradient;
                        },
                        borderColor: "#22C55E",
                        pointRadius: 0,
                        pointBackgroundColor: "#fff",
                        pointBorderColor: "#22C55E",
                        borderWidth: 1,
                        fill: true,
                        fillColor: "#fff",
                        tension: 0.4,
                    }, ],
                },
                options: {
                    layout: {
                        padding: {
                            bottom: -20,
                        },
                    },
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                        y: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                    },

                    plugins: {
                        legend: {
                            position: "top",
                            display: false,
                        },
                        title: {
                            display: false,
                            text: "Visitor: 2k",
                        },
                        tooltip: {
                            enabled: false,
                        },
                    },
                },
            });
        }
        totalEarn();

        function totalSpendingChart() {
            let ctx_bids = document
                .getElementById("totalSpending")
                .getContext("2d");
            let bitsMonth = [
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Feb",
                "Mar",
                "Afril",
                "May",
            ];
            let bitsData = [
                0, 10, 0, 65, 0, 25, 0, 35, 20, 100, 40, 75, 50, 85, 60,
            ];
            let totalEarn = new Chart(ctx_bids, {
                type: "line",
                data: {
                    labels: bitsMonth,
                    datasets: [{
                        label: "Visitor",
                        data: bitsData,
                        backgroundColor: () => {
                            const chart = document
                                .getElementById("totalEarn")
                                .getContext("2d");
                            const gradient = chart.createLinearGradient(0, 0, 0, 450);
                            gradient.addColorStop(0, "rgba(34, 197, 94,0.41)");
                            gradient.addColorStop(0.2, "rgba(255, 255, 255, 0)");

                            return gradient;
                        },
                        borderColor: "#22C55E",
                        pointRadius: 0,
                        pointBackgroundColor: "#fff",
                        pointBorderColor: "#22C55E",
                        borderWidth: 1,
                        fill: true,
                        fillColor: "#fff",
                        tension: 0.4,
                    }, ],
                },
                options: {
                    layout: {
                        padding: {
                            bottom: -20,
                        },
                    },
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                        y: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                    },

                    plugins: {
                        legend: {
                            position: "top",
                            display: false,
                        },
                        title: {
                            display: false,
                            text: "Visitor: 2k",
                        },
                        tooltip: {
                            enabled: false,
                        },
                    },
                },
            });
        }
        totalSpendingChart();

        $(".card-slider").slick({
            dots: true,
            infinite: true,
            autoplay: true,
            speed: 500,
            fade: true,
            cssEase: "linear",
            arrows: false,
        });

        function totalGoal() {
            let ctx_bids = document.getElementById("totalGoal").getContext("2d");
            let bitsMonth = [
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Jan",
                "Feb",
                "Mar",
                "Afril",
                "May",
                "Feb",
                "Mar",
                "Afril",
                "May",
            ];
            let bitsData = [
                0, 10, 0, 65, 0, 25, 0, 35, 20, 100, 40, 75, 50, 85, 60,
            ];
            let totalEarn = new Chart(ctx_bids, {
                type: "line",
                data: {
                    labels: bitsMonth,
                    datasets: [{
                        label: "Visitor",
                        data: bitsData,
                        backgroundColor: () => {
                            const chart = document
                                .getElementById("totalGoal")
                                .getContext("2d");
                            const gradient = chart.createLinearGradient(0, 0, 0, 450);
                            gradient.addColorStop(0, "rgba(34, 197, 94,0.41)");
                            gradient.addColorStop(0.2, "rgba(255, 255, 255, 0)");
                            console.log({
                                gradient
                            });
                            return gradient;
                        },
                        borderColor: "#22C55E",
                        pointRadius: 0,
                        pointBackgroundColor: "#fff",
                        pointBorderColor: "#22C55E",
                        borderWidth: 1,
                        fill: true,
                        fillColor: "#fff",
                        tension: 0.4,
                    }, ],
                },
                options: {
                    layout: {
                        padding: {
                            bottom: -20,
                        },
                    },
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                        y: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                    },

                    plugins: {
                        legend: {
                            position: "top",
                            display: false,
                        },
                        title: {
                            display: false,
                            text: "Visitor: 2k",
                        },
                        tooltip: {
                            enabled: false,
                        },
                    },
                },
            });
        }
        totalGoal();

        let revenueFlowElement = document
            .getElementById("revenueFlow")
            .getContext("2d");
        let month = [
            "Jan",
            "Feb",
            "Mar",
            "April",
            "May",
            "Jun",
            "July",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ];

        let revenueFlow = new Chart(revenueFlowElement, {
            type: "bar",
            data: {
                labels: month,
                datasets: dataSetsLight,
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgb(243 ,246, 255 ,1)",
                        },
                        gridLines: {
                            zeroLineColor: "transparent",
                        },
                        ticks: {
                            callback(value) {
                                return `${value}% `;
                            },
                        },
                    },
                    x: {
                        grid: {
                            color: "rgb(243 ,246, 255 ,1)",
                        },
                        gridLines: {
                            zeroLineColor: "transparent",
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                },
            },
        });
        //pie chart
        let pieChart = document.getElementById("pie_chart").getContext("2d");

        const data = {
            labels: [10, 20, 30],
            datasets: [{
                label: "My First Dataset",
                data: [15, 20, 35, 40],
                backgroundColor: ["#1A202C", "#61C660", "#F8CC4B", "#EDF2F7"],
                borderColor: ["#ffffff", "#ffffff", "#ffffff", "#1A202C"],
                hoverOffset: 18,
                borderWidth: 0,
            }, ],
        };
        const customDatalabels = {
            id: "customDatalabels",
            afterDatasetsDraw(chart, args, pluginOptions) {
                const {
                    ctx,
                    data,
                    chartArea: {
                        top,
                        bottom,
                        left,
                        right,
                        width,
                        height
                    },
                } = chart;
                ctx.save();
                data.datasets[0].data.forEach((datapoint, index) => {
                    const {
                        x,
                        y
                    } = chart
                        .getDatasetMeta(0)
                        .data[index].tooltipPosition();
                    ctx.font = "bold 12px sans-serif";
                    ctx.fillStyle = data.datasets[0].borderColor[index];
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(`${datapoint}%`, x, y);
                });
            },
        };
        const config = {
            type: "doughnut",
            data,
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10,
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
            plugins: [customDatalabels],
        };

        let pieChartConfiig = new Chart(pieChart, config);

        //chart dark mode
        let themeToggleSwitch = document.getElementById("theme-toggle");

        //onclick

        if (themeToggleSwitch) {
            themeToggleSwitch.addEventListener("click", function() {
                if (
                    document.documentElement.classList[0] === "light" ||
                    localStorage.theme === "light"
                ) {
                    revenueFlow.data.datasets = dataSetsLight;
                    revenueFlow.options.scales.y.ticks.color = "black";
                    revenueFlow.options.scales.x.ticks.color = "black";
                    revenueFlow.options.scales.x.grid.color = "rgb(243 ,246, 255 ,1)";
                    revenueFlow.options.scales.y.grid.color = "rgb(243 ,246, 255 ,1)";
                    revenueFlow.update();
                } else {
                    revenueFlow.data.datasets = dataSetsLight;
                    revenueFlow.options.scales.y.ticks.color = "black";
                    revenueFlow.options.scales.x.ticks.color = "black";
                    revenueFlow.options.scales.x.grid.color = "rgb(243 ,246, 255 ,1)";
                    revenueFlow.options.scales.y.grid.color = "rgb(243 ,246, 255 ,1)";
                    revenueFlow.update();
                }
            });
        }

        //initial load
        if (
            localStorage.theme === "light" ||
            window.matchMedia("(prefers-color-scheme: dark)").matches
        ) {
            revenueFlow.data.datasets = dataSetsLight;
            revenueFlow.options.scales.y.ticks.color = "black";
            revenueFlow.options.scales.x.ticks.color = "black";
            revenueFlow.options.scales.x.grid.color = "rgb(243 ,246, 255 ,1)";
            revenueFlow.options.scales.y.grid.color = "rgb(243 ,246, 255 ,1)";
        } else {
            revenueFlow.data.datasets = dataSetsLight;
            revenueFlow.options.scales.y.ticks.color = "black";
            revenueFlow.options.scales.x.ticks.color = "black";
            revenueFlow.options.scales.x.grid.color = "rgb(243 ,246, 255 ,1)";
            revenueFlow.options.scales.y.grid.color = "rgb(243 ,246, 255 ,1)";
        }
        revenueFlow.update();
    </script>
@endpush
