@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">
        <!-- write your code here-->
        <div class="grid grid-cols-1 rounded-xl bg-white dark:bg-darkblack-600 xl:grid-cols-12">
            <!-- Sidebar -->
            <aside class="col-span-3 border-r border-bgray-200 dark:border-darkblack-400">
                <!-- Sidebar Tabs -->

                <div class="px-4 py-6">

                    {{-- Departments --}}
                    <div data-tab="tab3" class="active tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 6H6M2 12H6M2 18H6M18 6L10 6M14 10L10 10M8 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2H8C5.79086 2 4 3.79086 4 6V18C4 20.2091 5.79086 22 8 22Z" stroke-width="1.5" stroke-linecap="round" stroke="currentColor" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Departments
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div>

                    {{-- Designations --}}
                    <div data-tab="tab4" class="tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 6H6M2 12H6M2 18H6M18 6L10 6M14 10L10 10M8 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2H8C5.79086 2 4 3.79086 4 6V18C4 20.2091 5.79086 22 8 22Z" stroke-width="1.5" stroke-linecap="round" stroke="currentColor" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Designations
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div>

                    {{-- <div class="tab flex gap-x-4 rounded-lg p-4 transition-all" data-tab="tab2">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.0717 4.06949C8.26334 4.49348 6.01734 6.81294 5.67964 9.79403L5.33476 12.8385C5.24906 13.595 4.94246 14.3069 4.45549 14.88C3.42209 16.0964 4.26081 18 5.83014 18H18.1699C19.7392 18 20.5779 16.0964 19.5445 14.88C19.0575 14.3069 18.7509 13.595 18.6652 12.8385L18.4373 10.8267M15 20C14.5633 21.1652 13.385 22 12 22C10.615 22 9.43668 21.1652 9 20M20 5C20 6.65685 18.6569 8 17 8C15.3431 8 14 6.65685 14 5C14 3.34315 15.3431 2 17 2C18.6569 2 20 3.34315 20 5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Notification Setting
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div> --}}

                    {{-- <div data-tab="tab4" class="tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 15H12V7H18M18 15C19.1046 15 20 14.1046 20 13V9C20 7.89543 19.1046 7 18 7M18 15V20C18 21.1046 17.1046 22 16 22H8C6.89543 22 6 21.1046 6 20V4C6 2.89543 6.89543 2 8 2H16C17.1046 2 18 2.89543 18 4V7" stroke-width="1.5" stroke="currentColor" stroke-linejoin="round" />
                                <path d="M13 19C13 19.5523 12.5523 20 12 20C11.4477 20 11 19.5523 11 19C11 18.4477 11.4477 18 12 18C12.5523 18 13 18.4477 13 19Z" fill="currentColor" />
                                <path stroke="currentColor" d="M20 10L12 10" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Payment Method
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div> --}}

                    {{-- <div data-tab="tab5" class="tab flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor"
                                    d="M13.6831 10.0808L14.3138 10.4866L13.6831 10.0808ZM9.25 9C9.25 9.41421 9.58579 9.75 10 9.75C10.4142 9.75 10.75 9.41421 10.75 9H9.25ZM11.25 13.5C11.25 13.9142 11.5858 14.25 12 14.25C12.4142 14.25 12.75 13.9142 12.75 13.5H11.25ZM12.75 16C12.75 15.5858 12.4142 15.25 12 15.25C11.5858 15.25 11.25 15.5858 11.25 16H12.75ZM11.25 17C11.25 17.4142 11.5858 17.75 12 17.75C12.4142 17.75 12.75 17.4142 12.75 17H11.25ZM21.25 12C21.25 17.1086 17.1086 21.25 12 21.25V22.75C17.9371 22.75 22.75 17.9371 22.75 12H21.25ZM12 21.25C6.89137 21.25 2.75 17.1086 2.75 12H1.25C1.25 17.9371 6.06294 22.75 12 22.75V21.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75V1.25C6.06294 1.25 1.25 6.06294 1.25 12H2.75ZM12 2.75C17.1086 2.75 21.25 6.89137 21.25 12H22.75C22.75 6.06294 17.9371 1.25 12 1.25V2.75ZM13.25 9C13.25 9.24996 13.1774 9.48068 13.0524 9.67495L14.3138 10.4866C14.5899 10.0576 14.75 9.54634 14.75 9H13.25ZM10.75 9C10.75 8.30964 11.3096 7.75 12 7.75V6.25C10.4812 6.25 9.25 7.48122 9.25 9H10.75ZM12 7.75C12.6904 7.75 13.25 8.30964 13.25 9H14.75C14.75 7.48122 13.5188 6.25 12 6.25V7.75ZM11.25 13V13.5H12.75V13H11.25ZM13.0524 9.67495C12.9265 9.87065 12.7688 10.0731 12.5836 10.3033C12.4063 10.5237 12.1979 10.7764 12.011 11.0333C11.6424 11.5398 11.25 12.2007 11.25 13H12.75C12.75 12.6947 12.9003 12.3605 13.2239 11.9158C13.383 11.697 13.558 11.4851 13.7523 11.2436C13.9387 11.0119 14.1409 10.7554 14.3138 10.4866L13.0524 9.67495ZM11.25 16V17H12.75V16H11.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                FAQ
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div> --}}

                    {{-- <div data-tab="tab6" class="tab group flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 8H8M16 8C18.2091 8 20 9.79086 20 12V18C20 20.2091 18.2091 22 16 22H8C5.79086 22 4 20.2091 4 18V12C4 9.79086 5.79086 8 8 8M16 8V6C16 3.79086 14.2091 2 12 2C9.79086 2 8 3.79086 8 6V8M14 15C14 16.1046 13.1046 17 12 17C10.8954 17 10 16.1046 10 15C10 13.8954 10.8954 13 12 13C13.1046 13 14 13.8954 14 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Security
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div> --}}

                    {{-- <div data-tab="tab7" class="tab flex gap-x-4 rounded-lg p-4 transition-all">
                        <div class="tab-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bgray-100 transition-all">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 14L10.7528 15.4023C11.1707 15.7366 11.7777 15.6826 12.1301 15.2799L15 12M16 4H17C19.2091 4 21 5.79086 21 8V18C21 20.2091 19.2091 22 17 22H7C4.79086 22 3 20.2091 3 18V8C3 5.79086 4.79086 4 7 4H8M16 4C16 5.10457 15.1046 6 14 6H10C8.89543 6 8 5.10457 8 4M16 4C16 2.89543 15.1046 2 14 2H10C8.89543 2 8 2.89543 8 4" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" />
                            </svg>
                        </div>

                        <div>
                            <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                Terms & Conditions
                            </h4>
                            <p class="mt-0.5 text-sm font-medium text-bgray-700 dark:text-darkblack-300">
                                Est arcu pharetra proin pellentesque
                            </p>
                        </div>
                    </div> --}}
                </div>
                <!-- Progressbar -->
                {{-- <div class="px-8">
                    <div class="rounded-xl bg-bgray-200 p-7 dark:bg-darkblack-500">
                        <div class="flex flex-row items-center space-x-6 md:flex-col md:space-x-0 2xl:flex-row 2xl:space-x-6">
                            <div class="progess-bar mb-0 flex justify-center md:mb-[13px] xl:mb-0">
                                <div class="bonus-per relative">
                                    <div class="bonus-outer">
                                        <div class="bonus-inner">
                                            <div class="number">
                                                <span class="text-sm font-medium text-bgray-900">64%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="80px" height="80px">
                                        <circle style="
                                stroke-dashoffset: calc(215 - 215 * (64 / 100));
                              " cx="40" cy="40" r="35" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex flex-col items-start md:items-center xl:items-start">
                                <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                    Complete profile
                                </h4>
                                <span class="text-xs font-medium text-bgray-700 dark:text-darkblack-300">Complete your profile to
                                    unlock all features</span>
                            </div>
                        </div>
                        <button class="mt-4 w-full rounded-lg bg-success-300 py-3 text-xs font-bold text-white transition-all hover:bg-success-400">
                            Verify identify
                        </button>
                    </div>
                </div> --}}
            </aside>
            <!--Tab Content -->
            <div class="tab-content col-span-9 px-10 py-8">
                <!-- Personal Information -->
                {{-- <div id="tab1" class="tab-pane active">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="xl:col-span-7 2xl:col-span-8">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Personal Information's
                            </h3>
                            <div class="mt-8">
                                <form action="">
                                    <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="fname" class="text-base font-medium text-bgray-600 dark:text-bgray-50">First
                                                Name</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="fname" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="lname" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Last
                                                Name</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="lname" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="email" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="email" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="phone" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone
                                                Number (Optional)</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="phone" />
                                        </div>
                                    </div>
                                    <h4 class="pb-6 pt-8 text-xl font-bold text-bgray-900 dark:text-white">
                                        Personal Address
                                    </h4>
                                    <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="country" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Country
                                                or Region</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="country" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="country" class="text-base font-medium text-bgray-600 dark:text-bgray-50">City</label>
                                            <input type="text" placeholder="Washington" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="country" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="country" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Address</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="address" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="postcode" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Postal
                                                Code</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="postcode" />
                                        </div>
                                    </div>
                                    <h4 class="pb-6 pt-8 text-xl font-bold text-bgray-900 dark:text-white">
                                        Social Information
                                    </h4>
                                    <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="country" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Facebook</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="fbook" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="twitter" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Twitter</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="twitter" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="linkedin" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Linkedin</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="linkedin" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="youtube" class="text-base font-medium text-bgray-600 dark:text-bgray-50">Youtube</label>
                                            <input type="text" class="h-14 rounded-lg border-0 bg-bgray-50 p-4 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" name="youtube" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="mt-10 rounded-lg bg-success-300 px-4 py-3.5 font-semibold text-white">
                                            Save Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="xl:col-span-5 2xl:col-span-4 2xl:mt-24">
                            <header class="mb-8">
                                <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                    Update Profile
                                </h4>
                                <p class="mb-4 text-bgray-500">
                                    Profile of at least Size
                                    <span class="text-bgray-900 dark:text-darkblack-300">300x300.</span>
                                    Gifs work too.
                                    <span class="text-bgray-900">Max 5mb.</span>
                                </p>
                                <div class="relative m-auto h-40 w-40 text-center">
                                    <img src="assets/images/avatar/profile.png" alt="" />
                                    <button class="absolute bottom-1 right-4">
                                        <svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="14.2414" cy="14.2414" r="14.2414" fill="#22C55E" />
                                            <path
                                                d="M14.6994 10.2363C15.7798 11.3167 16.8434 12.3803 17.9171 13.454C17.7837 13.584 17.6403 13.7174 17.5036 13.8574C15.5497 15.8114 13.5924 17.7653 11.6385 19.7192C11.5118 19.8459 11.3884 19.9726 11.2617 20.0927C11.2317 20.1193 11.185 20.1427 11.145 20.1427C10.1281 20.146 9.11108 20.1427 8.0941 20.146C8.02408 20.146 8.01074 20.1193 8.01074 20.0593C8.01074 19.049 8.01074 18.0354 8.01408 17.0251C8.01408 16.9784 8.03742 16.9217 8.06743 16.8917C9.26779 15.688 10.4682 14.4876 11.6685 13.2873C12.6655 12.2903 13.6591 11.2967 14.6561 10.2997C14.6761 10.2797 14.6861 10.253 14.6994 10.2363Z"
                                                fill="white" />
                                            <path d="M18.6467 12.7197C17.573 11.646 16.506 10.579 15.4424 9.51537C15.6324 9.31864 15.8292 9.11858 16.0259 8.91852C16.256 8.68845 16.4894 8.45838 16.7228 8.22831C17.0162 7.93822 17.4197 7.93822 17.7097 8.22831C18.4466 8.9552 19.1802 9.68542 19.9171 10.4123C20.2038 10.6957 20.2138 11.0992 19.9371 11.3859C19.5136 11.8261 19.0868 12.2629 18.6634 12.703C18.66 12.7097 18.65 12.7163 18.6467 12.7197Z" fill="white" />
                                        </svg>
                                    </button>
                                </div>
                            </header>
                            <div>
                                <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                    Update Cover
                                </h4>
                                <p class="mb-4 text-bgray-500 dark:text-bgray-50">
                                    Cover of at least Size
                                    <span class="text-bgray-900">1170x920 </span>
                                </p>
                                <div class="relative w-full">
                                    <img src="assets/images/others/cover.jpg" class="w-full" alt="" />
                                    <button class="absolute bottom-2 right-2">
                                        <svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="14.2414" cy="14.2414" r="14.2414" fill="#22C55E" />
                                            <path
                                                d="M14.6994 10.2363C15.7798 11.3167 16.8434 12.3803 17.9171 13.454C17.7837 13.584 17.6403 13.7174 17.5036 13.8574C15.5497 15.8114 13.5924 17.7653 11.6385 19.7192C11.5118 19.8459 11.3884 19.9726 11.2617 20.0927C11.2317 20.1193 11.185 20.1427 11.145 20.1427C10.1281 20.146 9.11108 20.1427 8.0941 20.146C8.02408 20.146 8.01074 20.1193 8.01074 20.0593C8.01074 19.049 8.01074 18.0354 8.01408 17.0251C8.01408 16.9784 8.03742 16.9217 8.06743 16.8917C9.26779 15.688 10.4682 14.4876 11.6685 13.2873C12.6655 12.2903 13.6591 11.2967 14.6561 10.2997C14.6761 10.2797 14.6861 10.253 14.6994 10.2363Z"
                                                fill="white" />
                                            <path d="M18.6467 12.7197C17.573 11.646 16.506 10.579 15.4424 9.51537C15.6324 9.31864 15.8292 9.11858 16.0259 8.91852C16.256 8.68845 16.4894 8.45838 16.7228 8.22831C17.0162 7.93822 17.4197 7.93822 17.7097 8.22831C18.4466 8.9552 19.1802 9.68542 19.9171 10.4123C20.2038 10.6957 20.2138 11.0992 19.9371 11.3859C19.5136 11.8261 19.0868 12.2629 18.6634 12.703C18.66 12.7097 18.65 12.7163 18.6467 12.7197Z" fill="white" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <!-- Notification -->
                <div id="tab2" class="tab-pane">
                    <h3 class="mb-5 text-2xl font-bold text-bgray-900 dark:text-white">
                        Notification
                    </h3>
                    <div class="space-y-5">
                        <div class="flex flex-col items-end justify-between border-b border-bgray-300 pb-5 dark:border-darkblack-400 sm:flex-row sm:items-center">
                            <div class="flex gap-x-4">
                                <span><svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="30" cy="30" r="30" fill="#22C55E" />
                                        <path
                                            d="M29.9703 43.7392C27.4494 43.7392 24.9286 43.7512 22.4077 43.7352C20.2721 43.7232 19.0338 42.51 19.0197 40.3809C18.9955 37.0226 18.9915 33.6643 19.0197 30.306C19.0378 28.2088 20.3265 26.9736 22.4481 26.9696C27.4212 26.9597 32.3964 26.9617 37.3695 26.9696C39.604 26.9736 40.8564 28.2208 40.8644 30.4457C40.8745 33.7381 40.8786 37.0306 40.8644 40.323C40.8544 42.506 39.6242 43.7252 37.43 43.7372C34.9434 43.7492 32.4569 43.7392 29.9703 43.7392ZM27.5321 33.7242C27.6108 33.9217 27.6592 34.5403 27.9919 34.7977C28.8954 35.4981 28.7542 36.404 28.7563 37.3159C28.7563 37.7469 28.6272 38.2538 28.8107 38.589C29.0467 39.018 29.5367 39.5827 29.9219 39.5867C30.3131 39.5907 30.8496 39.0499 31.0613 38.6229C31.261 38.2179 31.1319 37.6492 31.1319 37.1543C31.1319 36.3022 31.0936 35.522 31.868 34.8216C32.6041 34.1552 32.4871 32.8881 31.8841 32.062C31.2811 31.2359 30.2002 30.8807 29.2019 31.18C28.2521 31.4653 27.5483 32.4152 27.5321 33.7242Z"
                                            fill="white" />
                                        <path d="M37.5315 26.0269C36.3356 26.0269 35.2466 26.0269 34.0345 26.0269C34.0345 25.1629 34.0446 24.3447 34.0325 23.5246C33.9982 21.0822 32.2397 19.2345 29.9467 19.2225C27.6497 19.2105 25.8851 21.0423 25.8448 23.4967C25.8306 24.3188 25.8427 25.1429 25.8427 26.0249C24.6448 26.0249 23.5558 26.0249 22.4729 26.0249C21.7126 21.0942 23.5054 17.4326 27.7546 16.1136C32.5603 14.621 37.3984 18.127 37.5274 23.1096C37.5516 24.0634 37.5315 25.0192 37.5315 26.0269Z" fill="white" />
                                    </svg>
                                </span>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white" id="availability-label">
                                        All Notifcation update off
                                    </h4>
                                    <p class="text-base text-bgray-500 dark:text-darkblack-300" id="availability-description">
                                        Unlockable content, only revealed by the owner of
                                        the item.
                                    </p>
                                </div>
                            </div>
                            <!-- Enabled: "bg-success-300", Not Enabled: "bg-[#9AA2B1]" -->
                            <button type="button" class="switch-btn active relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false" aria-labelledby="availability-label" aria-describedby="availability-description">
                                <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                        <div class="flex flex-col items-end justify-between border-b border-bgray-300 pb-5 dark:border-darkblack-400 sm:flex-row sm:items-center">
                            <div class="flex gap-x-4">
                                <div class="w-[60px]">
                                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="30" cy="30" r="30" fill="#FFC837" />
                                        <path
                                            d="M29.9407 27.7853C29.3978 27.6062 28.8874 27.5042 28.4342 27.2802C24.4201 25.2948 20.4135 23.2895 16.4044 21.2942C16.1106 21.1474 15.884 20.9583 15.879 20.6075C15.874 20.2418 16.1006 20.0378 16.4094 19.8835C20.3911 17.9006 24.3728 15.9127 28.352 13.9248C29.4974 13.3526 30.6255 13.4173 31.756 13.987C34.3133 15.2733 36.8806 16.5471 39.443 17.826C40.7503 18.4778 42.0551 19.1321 43.3599 19.7865C43.4346 19.8238 43.5093 19.8586 43.5865 19.896C43.8853 20.0353 44.092 20.2517 44.092 20.5976C44.092 20.9484 43.8704 21.1449 43.5766 21.2892C41.7214 22.2123 39.8688 23.1403 38.0136 24.0633C35.8721 25.1306 33.7406 26.2129 31.5866 27.2529C31.0737 27.5017 30.4935 27.6111 29.9407 27.7853Z"
                                            fill="white" />
                                        <path d="M28.9083 38.2601C28.9083 40.4844 28.9108 42.7086 28.9058 44.9329C28.9033 45.724 28.4576 46.0027 27.728 45.6668C24.0775 43.9874 20.4245 42.313 16.7814 40.6212C15.6086 40.0763 15.0109 39.1235 15.006 37.8347C14.996 33.5952 15.001 29.3557 15.0035 25.1162C15.0035 24.2976 15.4641 23.9941 16.1962 24.3325C19.9713 26.0716 23.7438 27.8206 27.5213 29.5522C28.48 29.9926 28.9232 30.7091 28.9158 31.7566C28.9008 33.9261 28.9108 36.0931 28.9108 38.2626C28.9108 38.2601 28.9108 38.2601 28.9083 38.2601Z" fill="white" />
                                        <path d="M31.0591 38.198C31.0591 36.016 31.0641 33.8341 31.0567 31.6521C31.0542 30.6669 31.5049 29.9877 32.3889 29.5797C36.1888 27.8281 39.9912 26.0816 43.7911 24.3325C44.4834 24.0141 44.959 24.3002 44.959 25.054C44.964 29.3358 44.9714 33.6151 44.954 37.8969C44.949 39.2081 44.2767 40.1237 43.0939 40.671C40.1406 42.0344 37.1848 43.3954 34.2316 44.7563C33.5991 45.0474 32.9691 45.3385 32.3391 45.6295C31.4825 46.0226 31.0567 45.7564 31.0567 44.8259C31.0567 42.6166 31.0567 40.4048 31.0567 38.1955C31.0591 38.198 31.0591 38.198 31.0591 38.198Z" fill="white" />
                                    </svg>
                                </div>

                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white" id="availability-label">
                                        When you upload products
                                    </h4>
                                    <p class="text-base text-bgray-500 dark:text-darkblack-300" id="availability-description">
                                        Evey new products upload seccessfullly doen you can
                                        get notifcation
                                    </p>
                                </div>
                            </div>
                            <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false" aria-labelledby="availability-label" aria-describedby="availability-description">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                        <div class="flex flex-col items-end justify-between border-b border-bgray-300 pb-5 dark:border-darkblack-400 sm:flex-row sm:items-center">
                            <div class="flex gap-x-4">
                                <div class="w-[60px]">
                                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="30" cy="30" r="30" fill="#2DD4BF" />
                                        <path d="M41.8283 27.5958C41.4072 29.0508 41.0641 30.4376 40.5806 31.7718C40.4617 32.101 39.8846 32.4672 39.5142 32.4711C35.1806 32.5217 30.8469 32.5042 26.5133 32.5061C25.8719 32.5061 25.4625 32.1906 25.3163 31.577C24.6301 28.7099 23.9381 25.8428 23.283 22.9698C23.0842 22.1011 23.4799 21.5654 24.3182 21.5596C27.8506 21.5362 31.383 21.5499 35.0032 21.5499C34.9603 23.4236 35.4711 25.0617 36.8922 26.3102C38.2822 27.5393 39.9256 27.9055 41.8283 27.5958Z" fill="white" />
                                        <path
                                            d="M24.4176 33.5096C27.334 33.5096 30.1997 33.5096 33.0654 33.5096C34.9408 33.5096 36.8162 33.5057 38.6915 33.5116C39.5649 33.5135 40.0601 33.905 40.0835 34.5809C40.1069 35.2918 39.5961 35.7242 38.6877 35.7242C33.7224 35.7281 28.759 35.7281 23.7938 35.7242C22.7878 35.7242 22.5091 35.4788 22.2888 34.464C21.3452 30.1049 20.3939 25.7477 19.4679 21.3847C19.3587 20.8724 19.1579 20.5841 18.6296 20.4225C17.419 20.0524 16.2318 19.6044 15.0368 19.1837C14.0328 18.8292 13.6605 18.2877 13.9334 17.5924C14.2024 16.9048 14.7853 16.7295 15.7386 17.0606C17.2221 17.5748 18.692 18.128 20.1912 18.5955C20.9476 18.8311 21.3082 19.2577 21.4681 20.0212C22.3824 24.3589 23.3317 28.6889 24.2733 33.0207C24.3006 33.1493 24.3474 33.2778 24.4176 33.5096Z"
                                            fill="white" />
                                        <path d="M35.6934 22.2151C35.6798 19.5116 37.8729 17.3145 40.5788 17.3243C43.23 17.332 45.4134 19.533 45.4095 22.1957C45.4056 24.8505 43.2144 27.0456 40.5573 27.0515C37.9022 27.0573 35.7071 24.8758 35.6934 22.2151ZM42.6257 20.057C41.7465 20.9978 40.8848 21.921 39.8614 23.0176C39.3721 22.3534 38.9763 21.8197 38.524 21.2081C38.0698 21.6834 37.7696 21.997 37.5747 22.2034C38.4051 23.0605 39.1986 23.8766 39.9316 24.6343C41.1207 23.4422 42.345 22.2171 43.6726 20.8867C43.4562 20.7153 43.1969 20.5089 42.9357 20.3024C42.8538 20.2342 42.77 20.1719 42.6257 20.057Z"
                                            fill="white" />
                                        <path d="M37.4854 36.6242C39.2068 36.6086 40.6104 37.9603 40.6436 39.6666C40.6767 41.4021 39.2653 42.8259 37.5147 42.8259C35.805 42.8259 34.4072 41.4371 34.3994 39.7367C34.3936 38.0285 35.7699 36.6417 37.4854 36.6242ZM38.8929 39.7153C38.891 38.9615 38.2984 38.3499 37.5537 38.3343C36.7817 38.3168 36.1384 38.9537 36.1423 39.7309C36.1462 40.4769 36.7485 41.0963 37.4854 41.1157C38.2574 41.1352 38.8968 40.5002 38.8929 39.7153Z" fill="white" />
                                        <path d="M24.8751 42.825C23.1556 42.8113 21.7754 41.4304 21.7793 39.7261C21.7852 37.9672 23.179 36.6018 24.9491 36.6213C26.6569 36.6408 28.0273 38.0471 28.0078 39.7592C27.9864 41.4713 26.5906 42.8367 24.8751 42.825ZM26.2709 39.728C26.2728 38.945 25.6334 38.3159 24.8556 38.3353C24.1226 38.3548 23.4968 38.9879 23.4968 39.7163C23.4948 40.4467 24.1148 41.0876 24.8439 41.1129C25.6178 41.1401 26.2689 40.5071 26.2709 39.728Z" fill="white" />
                                    </svg>
                                </div>

                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white" id="availability-label">
                                        You got sell your prodcuts
                                    </h4>
                                    <p class="text-base text-bgray-500 dark:text-darkblack-300" id="availability-description">
                                        Evey new prodcuts sell you can get notifcation
                                    </p>
                                </div>
                            </div>
                            <button type="button" class="switch-btn active relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false" aria-labelledby="availability-label" aria-describedby="availability-description">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                        <div class="flex flex-col items-end justify-between border-b border-bgray-300 pb-5 dark:border-darkblack-400 sm:flex-row sm:items-center">
                            <div class="flex gap-x-4">
                                <div class="w-[60px]">
                                    <img src="assets/images/icons/follower.svg" alt="" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white" id="availability-label">
                                        Got new follower
                                    </h4>
                                    <p class="text-base text-bgray-500 dark:text-darkblack-300" id="availability-description">
                                        Evey new follower you can get notifcation
                                    </p>
                                </div>
                            </div>
                            <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false" aria-labelledby="availability-label" aria-describedby="availability-description">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                        <div class="flex flex-col items-end justify-between border-b border-bgray-300 pb-5 dark:border-darkblack-400 sm:flex-row sm:items-center">
                            <div class="flex gap-x-4">
                                <div class="w-[60px]">
                                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="30" cy="30" r="30" fill="#936DFF" />
                                        <path
                                            d="M37.9128 13.2706C38.2013 13.4189 38.2864 13.6656 38.2814 13.9739C38.2714 14.5905 38.2781 15.2088 38.2781 15.8655C38.4182 15.8655 38.5299 15.8655 38.6417 15.8655C40.1145 15.8671 41.5857 15.8671 43.0586 15.8688C43.9126 15.8705 44.2879 16.2605 44.2696 17.1121C44.2229 19.197 44.0077 21.2602 43.4005 23.2651C42.9368 24.8 42.283 26.2332 41.0787 27.3482C40.3331 28.0381 39.4523 28.4581 38.4415 28.5831C38.2514 28.6064 38.1863 28.6747 38.1463 28.8631C37.4691 32.0529 35.5742 34.1344 32.4467 35.0877C32.3116 35.1294 32.2032 35.161 32.2032 35.3494C32.2099 37.3242 32.2099 39.2991 32.2115 41.274C32.2115 41.304 32.2249 41.334 32.2399 41.394C32.345 41.399 32.4567 41.409 32.5685 41.409C33.2307 41.4107 33.8929 41.414 34.5551 41.4073C34.842 41.404 35.0538 41.5057 35.2206 41.7473C35.8878 42.7156 36.565 43.6789 37.2389 44.6422C37.3773 44.8405 37.5125 45.0721 37.3306 45.2721C37.2005 45.4138 36.9637 45.5304 36.7735 45.5321C32.4 45.5471 28.0248 45.5421 23.6513 45.5454C23.4178 45.5454 23.2109 45.5054 23.0858 45.2821C22.9524 45.0421 23.0491 44.8422 23.1892 44.6422C23.8581 43.6872 24.5287 42.7339 25.1875 41.7723C25.3677 41.5107 25.5945 41.404 25.9031 41.4073C26.6587 41.4157 27.4143 41.409 28.2033 41.409C28.21 41.274 28.22 41.1624 28.22 41.0507C28.2216 39.1908 28.2166 37.3326 28.225 35.4727C28.2266 35.2377 28.1633 35.1394 27.9214 35.0644C24.8823 34.1178 22.9974 32.0945 22.3119 28.9831C22.2502 28.7031 22.1551 28.6081 21.8598 28.5614C20.407 28.3364 19.3128 27.5315 18.4738 26.3532C17.5047 24.9916 16.9993 23.44 16.659 21.8285C16.3287 20.2636 16.1769 18.6753 16.1536 17.0754C16.1419 16.2388 16.5255 15.8655 17.3679 15.8638C18.8291 15.8621 20.2919 15.8605 21.7531 15.8605C21.8682 15.8605 21.9833 15.8605 22.1484 15.8605C22.1484 15.5655 22.1484 15.2872 22.1484 15.0089C22.1484 14.6622 22.1567 14.3156 22.1467 13.9689C22.1367 13.6606 22.2268 13.4123 22.5154 13.2656C27.6478 13.2706 32.7803 13.2706 37.9128 13.2706ZM30.2132 18.5787C29.8947 19.222 29.561 19.7753 29.3425 20.3719C29.109 21.0102 28.7154 21.2668 28.0482 21.2935C27.4177 21.3202 26.7922 21.4652 26.1116 21.5652C26.2217 21.6818 26.2901 21.7568 26.3635 21.8285C26.8972 22.3501 27.4393 22.8634 27.9581 23.3984C28.0548 23.4984 28.1165 23.69 28.1015 23.8284C28.0565 24.245 27.9631 24.655 27.8914 25.0683C27.8196 25.4933 27.7513 25.9199 27.6729 26.3999C28.4652 25.9832 29.2041 25.6083 29.9297 25.2083C30.1415 25.0916 30.3 25.1033 30.5051 25.2149C31.2307 25.6116 31.968 25.9882 32.7236 26.3832C32.7236 26.2899 32.7319 26.2282 32.7219 26.1716C32.5902 25.4066 32.4667 24.64 32.3183 23.8784C32.2749 23.6534 32.3233 23.5084 32.4884 23.355C32.9338 22.9401 33.3624 22.5051 33.8011 22.0818C33.9629 21.9251 34.1331 21.7768 34.3649 21.5668C33.4609 21.4368 32.6652 21.3135 31.8662 21.2102C31.6077 21.1768 31.4409 21.0968 31.3225 20.8335C30.9872 20.0969 30.6086 19.3736 30.2132 18.5787ZM42.2813 17.892C40.9219 17.892 39.6075 17.892 38.3014 17.892C38.3014 20.8019 38.3014 23.6834 38.3014 26.6449C38.6517 26.4965 38.9753 26.3999 39.2555 26.2315C40.1512 25.6932 40.6617 24.84 41.057 23.91C41.5991 22.6368 41.8943 21.2968 42.0745 19.9319C42.1612 19.2569 42.2112 18.5803 42.2813 17.892ZM22.1301 26.5865C22.1301 23.6717 22.1301 20.7819 22.1301 17.8854C20.8023 17.8854 19.4963 17.8854 18.1819 17.8854C18.1785 17.947 18.1719 17.987 18.1735 18.027C18.2753 19.6969 18.4988 21.3468 19.0142 22.9434C19.3278 23.9133 19.7198 24.855 20.4287 25.6199C20.8874 26.1166 21.4262 26.4749 22.1301 26.5865Z"
                                            fill="white" />
                                    </svg>
                                </div>

                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white" id="availability-label">
                                        Auther leavel up
                                    </h4>
                                    <p class="text-base text-bgray-500 dark:text-darkblack-300" id="availability-description">
                                        Evey new prodcuts sell you can get notifcation
                                    </p>
                                </div>
                            </div>

                            <button type="button" class="switch-btn toggle-switch relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-300 text-center transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false" aria-labelledby="availability-label" aria-describedby="availability-description">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Departments -->
                <div id="tab3" class="tab-pane active">
                    <div class="w-full">
                        <div class="w-full">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Departments
                            </h3>
                            <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm">

                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>

                                <span>New Department</span>
                            </a>

                            <div class="mt-8 w-full">
                                @include('settings.departments.index')
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Designations -->
                <div id="tab4" class="tab-pane">
                    <div class="w-full">
                        <div class="w-full">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Designations
                            </h3>
                            <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm">

                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>

                                <span>New Department</span>
                            </a>

                            <div class="mt-8 w-full">
                                @include('settings.departments.index')
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments -->
                {{-- <div id="tab4" class="tab-pane">
                    <!-- Cards -->
                    <div>
                        <h3 class="pb-5 text-2xl font-bold text-bgray-900 dark:text-white">
                            Payment and Billing
                        </h3>
                        <div class="grid grid-cols-1 gap-5 pb-14 sm:grid-cols-2 2xl:grid-cols-3 2xl:gap-10">
                            <div class="relative rounded-lg bg-gray-100 p-6 dark:bg-darkblack-500">
                                <div class="mb-5 flex gap-x-2">
                                    <img src="assets/images/payments/visa.svg" alt="" />
                                    <img src="assets/images/payments/master.svg" alt="" />
                                    <img src="assets/images/payments/ae.svg" alt="" />
                                </div>
                                <h4 class="mb-2 text-base font-bold text-bgray-900 dark:text-white">
                                    Credit or Debit Card
                                </h4>
                                <p class="text-sm dark:text-bgray-50">
                                    Offers payment processing software for e-commerce
                                    websites and mobile applications.
                                </p>
                                <button class="absolute right-5 top-5">
                                    <svg class="stroke-bgray-600 dark:stroke-bgray-50" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 14.2037C10.8954 14.2037 10 13.3074 10 12.2019C10 11.0964 10.8954 10.2002 12 10.2002C13.1046 10.2002 14 11.0964 14 12.2019C14 13.3074 13.1046 14.2037 12 14.2037Z" stroke-width="1.5" />
                                        <path d="M20 14.2037C18.8954 14.2037 18 13.3074 18 12.2019C18 11.0964 18.8954 10.2002 20 10.2002C21.1046 10.2002 22 11.0964 22 12.2019C22 13.3074 21.1046 14.2037 20 14.2037Z" stroke-width="1.5" />
                                        <path d="M4 14.2037C2.89543 14.2037 2 13.3074 2 12.2019C2 11.0964 2.89543 10.2002 4 10.2002C5.10457 10.2002 6 11.0964 6 12.2019C6 13.3074 5.10457 14.2037 4 14.2037Z" stroke-width="1.5" />
                                    </svg>
                                </button>
                            </div>
                            <div class="relative rounded-lg bg-gray-100 p-6 dark:bg-darkblack-500">
                                <div class="mb-5">
                                    <img src="assets/images/payments/paypal.svg" alt="" />
                                </div>
                                <h4 class="mb-2 text-base font-bold text-bgray-900 dark:text-white">
                                    PayPal
                                </h4>
                                <p class="text-sm dark:text-bgray-50">
                                    Offers payment processing software for e-commerce
                                    websites and mobile applications.
                                </p>
                                <button class="absolute right-5 top-5">
                                    <svg class="stroke-bgray-600 dark:stroke-bgray-50" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 14.2037C10.8954 14.2037 10 13.3074 10 12.2019C10 11.0964 10.8954 10.2002 12 10.2002C13.1046 10.2002 14 11.0964 14 12.2019C14 13.3074 13.1046 14.2037 12 14.2037Z" stroke-width="1.5" />
                                        <path d="M20 14.2037C18.8954 14.2037 18 13.3074 18 12.2019C18 11.0964 18.8954 10.2002 20 10.2002C21.1046 10.2002 22 11.0964 22 12.2019C22 13.3074 21.1046 14.2037 20 14.2037Z" stroke-width="1.5" />
                                        <path d="M4 14.2037C2.89543 14.2037 2 13.3074 2 12.2019C2 11.0964 2.89543 10.2002 4 10.2002C5.10457 10.2002 6 11.0964 6 12.2019C6 13.3074 5.10457 14.2037 4 14.2037Z" stroke-width="1.5" />
                                    </svg>
                                </button>
                            </div>
                            <div class="relative rounded-lg bg-gray-100 p-6 dark:bg-darkblack-500">
                                <div class="mb-5">
                                    <img src="assets/images/payments/ae-l.svg" alt="" />
                                </div>
                                <h4 class="mb-2 text-base font-bold text-bgray-900 dark:text-white">
                                    American Express
                                </h4>
                                <p class="text-sm dark:text-bgray-50">
                                    Offers payment processing software for e-commerce
                                    websites and mobile applications.
                                </p>
                                <button class="absolute right-5 top-5">
                                    <svg class="stroke-bgray-600 dark:stroke-bgray-50" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 14.2037C10.8954 14.2037 10 13.3074 10 12.2019C10 11.0964 10.8954 10.2002 12 10.2002C13.1046 10.2002 14 11.0964 14 12.2019C14 13.3074 13.1046 14.2037 12 14.2037Z" stroke-width="1.5" />
                                        <path d="M20 14.2037C18.8954 14.2037 18 13.3074 18 12.2019C18 11.0964 18.8954 10.2002 20 10.2002C21.1046 10.2002 22 11.0964 22 12.2019C22 13.3074 21.1046 14.2037 20 14.2037Z" stroke-width="1.5" />
                                        <path d="M4 14.2037C2.89543 14.2037 2 13.3074 2 12.2019C2 11.0964 2.89543 10.2002 4 10.2002C5.10457 10.2002 6 11.0964 6 12.2019C6 13.3074 5.10457 14.2037 4 14.2037Z" stroke-width="1.5" />
                                    </svg>
                                </button>
                            </div>
                            <div class="relative rounded-lg bg-gray-100 p-6 dark:bg-darkblack-500">
                                <div class="mb-5">
                                    <img src="assets/images/payments/amazon.svg" class="block dark:hidden" alt="" />
                                    <img src="assets/images/payments/amazon-white.svg" class="hidden dark:block" alt="" />
                                </div>
                                <h4 class="mb-2 text-base font-bold text-bgray-900 dark:text-white">
                                    Amazon
                                </h4>
                                <p class="text-sm dark:text-bgray-50">
                                    Offers payment processing software for e-commerce
                                    websites and mobile applications.
                                </p>
                                <button class="absolute right-5 top-5">
                                    <svg class="stroke-bgray-600 dark:stroke-bgray-50" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 14.2037C10.8954 14.2037 10 13.3074 10 12.2019C10 11.0964 10.8954 10.2002 12 10.2002C13.1046 10.2002 14 11.0964 14 12.2019C14 13.3074 13.1046 14.2037 12 14.2037Z" stroke-width="1.5" />
                                        <path d="M20 14.2037C18.8954 14.2037 18 13.3074 18 12.2019C18 11.0964 18.8954 10.2002 20 10.2002C21.1046 10.2002 22 11.0964 22 12.2019C22 13.3074 21.1046 14.2037 20 14.2037Z" stroke-width="1.5" />
                                        <path d="M4 14.2037C2.89543 14.2037 2 13.3074 2 12.2019C2 11.0964 2.89543 10.2002 4 10.2002C5.10457 10.2002 6 11.0964 6 12.2019C6 13.3074 5.10457 14.2037 4 14.2037Z" stroke-width="1.5" />
                                    </svg>
                                </button>
                            </div>
                            <div class="relative rounded-lg bg-gray-100 p-6 dark:bg-darkblack-500">
                                <div class="mb-5">
                                    <img src="assets/images/payments/payoner.svg" class="block dark:hidden" alt="" />
                                    <img src="assets/images/payments/payoneer-white.svg" class="hidden dark:block" alt="" />
                                </div>
                                <h4 class="mb-2 text-base font-bold text-bgray-900 dark:text-white">
                                    Payoner
                                </h4>
                                <p class="text-sm dark:text-white">
                                    Offers payment processing software for e-commerce
                                    websites and mobile applications.
                                </p>
                                <button class="absolute right-5 top-5">
                                    <svg class="stroke-bgray-600 dark:stroke-bgray-50" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 14.2037C10.8954 14.2037 10 13.3074 10 12.2019C10 11.0964 10.8954 10.2002 12 10.2002C13.1046 10.2002 14 11.0964 14 12.2019C14 13.3074 13.1046 14.2037 12 14.2037Z" stroke-width="1.5" />
                                        <path d="M20 14.2037C18.8954 14.2037 18 13.3074 18 12.2019C18 11.0964 18.8954 10.2002 20 10.2002C21.1046 10.2002 22 11.0964 22 12.2019C22 13.3074 21.1046 14.2037 20 14.2037Z" stroke-width="1.5" />
                                        <path d="M4 14.2037C2.89543 14.2037 2 13.3074 2 12.2019C2 11.0964 2.89543 10.2002 4 10.2002C5.10457 10.2002 6 11.0964 6 12.2019C6 13.3074 5.10457 14.2037 4 14.2037Z" stroke-width="1.5" />
                                    </svg>
                                </button>
                            </div>
                            <div class="modal-open flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-bgray-500 p-6">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.5005 9.25H2.00049C1.59049 9.25 1.25049 8.91 1.25049 8.5C1.25049 8.09 1.59049 7.75 2.00049 7.75H13.5005C13.9105 7.75 14.2505 8.09 14.2505 8.5C14.2505 8.91 13.9105 9.25 13.5005 9.25Z" fill="#718096" />
                                    <path d="M8.00049 17.25H6.00049C5.59049 17.25 5.25049 16.91 5.25049 16.5C5.25049 16.09 5.59049 15.75 6.00049 15.75H8.00049C8.41049 15.75 8.75049 16.09 8.75049 16.5C8.75049 16.91 8.41049 17.25 8.00049 17.25Z" fill="#718096" />
                                    <path d="M14.5005 17.25H10.5005C10.0905 17.25 9.75049 16.91 9.75049 16.5C9.75049 16.09 10.0905 15.75 10.5005 15.75H14.5005C14.9105 15.75 15.2505 16.09 15.2505 16.5C15.2505 16.91 14.9105 17.25 14.5005 17.25Z" fill="#718096" />
                                    <path d="M17.5605 21.25H6.44049C2.46049 21.25 1.25049 20.05 1.25049 16.11V7.89C1.25049 3.95 2.46049 2.75 6.44049 2.75H13.5005C13.9105 2.75 14.2505 3.09 14.2505 3.5C14.2505 3.91 13.9105 4.25 13.5005 4.25H6.44049C3.30049 4.25 2.75049 4.79 2.75049 7.89V16.1C2.75049 19.2 3.30049 19.74 6.44049 19.74H17.5505C20.6905 19.74 21.2405 19.2 21.2405 16.1V12.02C21.2405 11.61 21.5805 11.27 21.9905 11.27C22.4005 11.27 22.7405 11.61 22.7405 12.02V16.1C22.7505 20.05 21.5405 21.25 17.5605 21.25Z" fill="#718096" />
                                    <path d="M22.0005 7H16.5005C16.0905 7 15.7505 6.66 15.7505 6.25C15.7505 5.84 16.0905 5.5 16.5005 5.5H22.0005C22.4105 5.5 22.7505 5.84 22.7505 6.25C22.7505 6.66 22.4105 7 22.0005 7Z" fill="#718096" />
                                    <path d="M19.2505 9.75C18.8405 9.75 18.5005 9.41 18.5005 9V3.5C18.5005 3.09 18.8405 2.75 19.2505 2.75C19.6605 2.75 20.0005 3.09 20.0005 3.5V9C20.0005 9.41 19.6605 9.75 19.2505 9.75Z" fill="#718096" />
                                </svg>
                                <span class="text-lg font-medium text-bgray-600">Add wallet</span>
                            </div>
                        </div>
                    </div>
                    <!-- Slider -->
                    <div class="border-bgray-300 px-0 py-0 dark:border-darkblack-400 lg:border-t lg:px-7 lg:py-10">
                        <div class="items-center justify-between lg:flex">
                            <div class="flex w-full flex-col items-center lg:w-[250px] 2xl:w-[400px]">
                                <div class="mb-4 flex w-full justify-between">
                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                        My Wallet
                                    </h4>
                                    <button>
                                        <svg width="20" height="5" viewBox="0 0 20 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8.90742 2.3381C8.90742 2.95199 9.40507 3.44964 10.019 3.44964C10.6328 3.44964 11.1305 2.95199 11.1305 2.3381C11.1305 1.72422 10.6328 1.22656 10.019 1.22656C9.40507 1.22656 8.90742 1.72422 8.90742 2.3381Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M1.12665 2.3381C1.12665 2.95199 1.62431 3.44964 2.23819 3.44964C2.85208 3.44964 3.34973 2.95199 3.34973 2.3381C3.34973 1.72422 2.85208 1.22656 2.23819 1.22656C1.62431 1.22656 1.12665 1.72422 1.12665 2.3381Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16.6882 2.3381C16.6882 2.95199 17.1858 3.44964 17.7997 3.44964C18.4136 3.44964 18.9113 2.95199 18.9113 2.3381C18.9113 1.72422 18.4136 1.22656 17.7997 1.22656C17.1858 1.22656 16.6882 1.72422 16.6882 2.3381Z" stroke="#CBD5E0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="card-slider relative w-full">
                                    <div class="m-0 w-[280px] md:w-[400px]">
                                        <img src="./assets/images/payments/pa-card.svg" class="w-full" alt="card" />
                                    </div>
                                    <div class="m-0 w-[280px] md:w-[400px]">
                                        <img src="./assets/images/payments/pa-card.svg" class="w-full" alt="card" />
                                    </div>
                                    <div class="m-0 w-[280px] md:w-[400px]">
                                        <img src="./assets/images/payments/pa-card.svg" class="w-full" alt="card" />
                                    </div>
                                </div>
                            </div>
                            <div class="hidden h-[220px] w-[4px] rounded-lg bg-bgray-200 dark:bg-darkblack-400 lg:block"></div>
                            <div class="w-full lg:w-[250px] 2xl:w-[400px]">
                                <div class="w-full">
                                    <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">
                                        Add money
                                    </h3>
                                    <div class="payment-select relative mb-3">
                                        <button onclick="dateFilterAction('#paymentFilter')" type="button" class="flex h-[56px] w-full items-center justify-between overflow-hidden rounded-lg border border-bgray-200 px-5 dark:border-darkblack-400">
                                            <div class="flex items-center space-x-2">
                                                <span>
                                                    <img src="./assets/images/payments/paypal-smsvg.svg" alt="master" />
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-bold text-bgray-900">
                                                    $24,098.00
                                                </span>
                                                <span class="text-sm font-medium text-bgray-900">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M4 6L8 10L12 6" stroke="#A0AEC0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </button>
                                        <div id="paymentFilter" class="absolute right-0 top-full z-10 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg">
                                            <ul>
                                                <li onclick="dateFilterAction('#paymentFilter')" class="text-bgray-90 cursor-pointer px-5 py-2 text-sm font-semibold hover:bg-bgray-100">
                                                    Jan 10 - Jan 16
                                                </li>
                                                <li onclick="dateFilterAction('#paymentFilter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100">
                                                    Jan 10 - Jan 16
                                                </li>
                                                <li onclick="dateFilterAction('#paymentFilter')" class="cursor-pointer px-5 py-2 text-sm font-semibold text-bgray-900 hover:bg-bgray-100">
                                                    Jan 10 - Jan 16
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="flex h-[98px] w-full flex-col justify-between rounded-lg border border-bgray-200 p-4 focus-within:border-success-300 dark:border-darkblack-400">
                                        <p class="text-sm font-medium text-bgray-600 dark:text-darkblack-300">
                                            Enter amount
                                        </p>
                                        <div class="flex h-[35px] w-full items-center justify-between">
                                            <span class="text-2xl font-bold text-bgray-900 dark:text-white">$</span>
                                            <label class="w-full">
                                                <input type="text" class="w-full border-none p-0 text-2xl font-bold text-bgray-900 focus:outline-none focus:ring-0 dark:bg-darkblack-600 dark:text-white" />
                                            </label>
                                            <div>
                                                <img src="./assets/images/avatar/members-3.png" alt="members" />
                                            </div>
                                        </div>
                                    </div>
                                    <button class="mt-7 w-full rounded-lg bg-success-300 py-4 text-base font-medium text-white transition-all hover:bg-success-400">
                                        Confirmed
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <!-- Security Password -->
                {{-- <div id="tab6" class="tab-pane">
                    <div class="flex flex-col gap-10 xl:flex-row xl:items-center">
                        <div class="max-w-[614px] grow">
                            <h3 class="mb-3 text-2xl font-bold text-bgray-900 dark:text-white">
                                Password
                            </h3>
                            <p class="fotn-medium text-sm text-bgray-500 dark:text-bgray-50">
                                Change or view your password.
                            </p>
                            <form action="" class="mt-6">
                                <div class="relative mb-6 flex flex-col">
                                    <label for="old" class="mb-3 block text-sm font-medium text-bgray-500 dark:text-darkblack-300">Old
                                        password</label>
                                    <input type="text" class="h-14 w-full rounded-lg border-0 bg-bgray-50 px-4 py-5 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white" />
                                    <button class="absolute right-4 top-12">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 3L21 21" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10.584 10.5869C10.2087 10.9619 9.99775 11.4707 9.99756 12.0012C9.99737 12.5317 10.2079 13.0406 10.583 13.4159C10.958 13.7912 11.4667 14.0021 11.9973 14.0023C12.5278 14.0025 13.0367 13.7919 13.412 13.4169" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M9.363 5.36506C10.2204 5.11978 11.1082 4.9969 12 5.00006C16 5.00006 19.333 7.33306 22 12.0001C21.222 13.3611 20.388 14.5241 19.497 15.4881M17.357 17.3491C15.726 18.4491 13.942 19.0001 12 19.0001C8 19.0001 4.667 16.6671 2 12.0001C3.369 9.60506 4.913 7.82506 6.632 6.65906" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="relative mb-6 flex flex-col">
                                    <label for="old" class="mb-3 block text-sm font-medium text-bgray-500 dark:text-darkblack-300">New
                                        password</label>
                                    <input type="text" class="h-14 w-full rounded-lg border-0 bg-bgray-50 px-4 py-5 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500" />
                                    <button class="absolute right-4 top-12">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 3L21 21" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10.584 10.5869C10.2087 10.9619 9.99775 11.4707 9.99756 12.0012C9.99737 12.5317 10.2079 13.0406 10.583 13.4159C10.958 13.7912 11.4667 14.0021 11.9973 14.0023C12.5278 14.0025 13.0367 13.7919 13.412 13.4169" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M9.363 5.36506C10.2204 5.11978 11.1082 4.9969 12 5.00006C16 5.00006 19.333 7.33306 22 12.0001C21.222 13.3611 20.388 14.5241 19.497 15.4881M17.357 17.3491C15.726 18.4491 13.942 19.0001 12 19.0001C8 19.0001 4.667 16.6671 2 12.0001C3.369 9.60506 4.913 7.82506 6.632 6.65906" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <small class="mt-1 block text-xs text-bgray-500 dark:text-darkblack-300">Minimum 6
                                        characters</small>
                                </div>
                                <div class="flex justify-end">
                                    <button class="rounded-lg bg-success-300 px-4 py-3 text-sm font-semibold text-white transition-all hover:bg-success-400 hover:bg-opacity-100">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="mx-auto hidden pt-10 xl:block">
                            <img src="assets/images/illustration/reset-password.svg" alt="" />
                        </div>
                    </div>
                </div> --}}
                <!-- Terms & Condition -->
                {{-- <div id="tab7" class="tab-pane">
                    <div>
                        <h3 class="mb-3 text-2xl font-bold text-bgray-900 dark:text-white">
                            Terms and Conditions
                        </h3>
                        <article class="mb-8">
                            <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                1. Definitions
                            </h4>
                            <p class="text-base text-[#9AA2B1] dark:text-white">
                                What you do own when you buy an are the keys to a
                                non-fungible – perhaps unique – token. That token is
                                yours to trade or hold or display in Decentraland. But
                                the digital file associated with an NFT is just as easy
                                to copy and paste and download as any other – the
                                Finally, players lose their NFTs sometimes according to
                                the rules and regulations of the NFT game.
                            </p>
                        </article>
                        <article class="mb-8">
                            <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                2. Acceptance
                            </h4>
                            <p class="text-base text-[#9AA2B1] dark:text-white">
                                Amet minim mollit non deserunt ullamco est sit aliqua
                                dolor do amet sint. Velit officia consequat duis enim
                                velit mollit. Exercitation veniam consequat sunt nostrud
                                amet.Capacity. You confirm that you have the legal
                                capacity to receive and hold and make use of the NFT
                                under French jurisdiction and any other relevant
                                jurisdiction.Acceptance. By participating in the Sale,
                                You accept and agree to these Terms and Conditions
                                without any condition or restriction. If You do not
                                agree to this Contract, You shall not participate in the
                                Sale made by the Company Exercitation veniam consequat
                                sunt nostrud amet.Capacity. You confirm that you have
                                the legal capacity to receive and hold find to
                                end.Contract, You shall not participate in the Sale made
                                by the Company Exercitation venia
                            </p>
                        </article>
                        <blockquote class="mb-8 rounded-lg bg-bgray-100 px-7 py-5 text-lg text-bgray-800 dark:bg-darkblack-500 dark:text-white">
                            In reality, the most important aspect of a great dashboard
                            is the part that gets the least amount of attention: The
                            underlying data. More than any other aspect, the data will
                            make or break a dashboard.Within this definition,
                            successful administration appears to rest on three basic
                            skills, which we will call technical, and conceptual.
                        </blockquote>
                        <article class="mb-8">
                            <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                3. The Sale
                            </h4>
                            <p class="mb-6 text-base text-[#9AA2B1] dark:text-white">
                                The Company offers featuring the Betonyou universe. The
                                holders of one or more NFTs will be able to win cryptos
                                while playing video games. In the future, the Company
                                plans to develop its own games and Metaverse around the
                                Betonyou universe (“Project”).
                            </p>
                            <p class="text-base text-[#9AA2B1] dark:text-white">
                                To release the NFTs and fund the project, the Company
                                offers NFTs from a dedicated website("Sale"). The web
                                address of this website will be given at the time of the
                                mint. The NFT acquisition does not confer any rights on
                                the Company or in the future development.
                            </p>
                        </article>
                        <article>
                            <h4 class="mb-2 text-lg font-bold text-bgray-800 dark:text-white">
                                4. Purchaser’s obligations
                            </h4>
                            <p class="mb-6 text-base text-[#9AA2B1] dark:text-white">
                                To the fullest extent permitted by applicable law, You
                                undertake to indemnify, defend and hold harmless the
                                Company from and against all claims, demands, actions,
                                damages, losses, costs and expenses (including
                                attorneys’ fees) that arise from or relate to (i) your
                                Subscription or use of the NFTs; (ii) your
                                responsibilities or obligations under this Contract; and
                                (iii) your breach of this Contract.
                            </p>
                            <p class="text-base text-[#9AA2B1] dark:text-white">
                                Company undertakes to act with the care normally
                                expected from a professional in his field and to comply
                                with the best practice in force. The best endeavor
                                obligation only binds the Company.
                            </p>
                        </article>
                    </div>
                </div> --}}
            </div>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

    {{-- Modal content start --}}
    @include('settings.departments.create-update-modal')
    {{-- Modal content end --}}
@endsection
