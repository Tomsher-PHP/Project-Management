@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">
        <!-- write your code here-->
        <div class="grid gap-3 lg:grid-cols-2 lg:gap-4 xl:grid-cols-3 xl:gap-6 2xl:grid-cols-4">

            @can('department.view')
                <a href="{{ route('settings.departments.index') }}" class="block group transition duration-300">

                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">

                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="stroke-bgray-50 group-hover:stroke-orange-500 transition" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>

                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg 
                            bg-orange-100 dark:bg-orange-900 
                            group-hover:bg-orange-200 transition">
                                    <svg class="h-8 w-8 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h2m-2 4h2m4-4h2m-2 4h2M10 21v-4h4v4" />
                                    </svg>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white 
                           group-hover:text-orange-600 transition">
                                    Departments
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">
                                    Setup Departments
                                </span>
                            </div>
                        </div>

                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Organize your company into structured departments for better management and reporting
                        </p>

                    </div>
                </a>
            @endcan

            @can('designation.view')
                <a href="{{ route('settings.designations.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Designations
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Designations</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Create and organize job roles for structured workforce management.
                        </p>
                    </div>
                </a>
            @endcan

            @can('shift.view')
                <a href="{{ route('settings.shifts.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Shifts
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Shifts</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Shift management for the company.
                        </p>
                    </div>
                </a>
            @endcan

            @can('technology.view')
                <a href="{{ route('settings.technologies.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Technologies
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Technologies</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Technologies management for the company projects.
                        </p>
                    </div>
                </a>
            @endcan

            @can('project_category.view')
                <a href="{{ route('settings.project-categories.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Project Categories
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Project Categories</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Project Categories management for the company projects.
                        </p>
                    </div>
                </a>
            @endcan

            @can('industry.view')
                <a href="{{ route('settings.industries.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Industries
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Industries</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Industries management for the company projects.
                        </p>
                    </div>
                </a>
            @endcan

            @can('project_status.view')
                <a href="{{ route('settings.project-statuses.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Project Statuses
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Project Statuses</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Project Statuses management for the company projects.
                        </p>
                    </div>
                </a>
            @endcan

            @can('project_stage.view')
                <a href="{{ route('settings.project-stages.index') }}" class="block group transition duration-300">
                    <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <span class="absolute right-6 top-6">
                            <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-200" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                            </svg>
                        </span>
                        <div class="flex space-x-5">
                            <div class="shrink-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <!-- User -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5z" />
                                        <!-- Shoulders -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 22c0-4 4-6 8-6s8 2 8 6" />
                                        <!-- Badge Line -->
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                    Project Stages
                                </h3>
                                <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Project Stages</span>
                            </div>
                        </div>
                        <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                            Project Stages management for the company projects.
                        </p>
                    </div>
                </a>
            @endcan

        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->
@endsection
