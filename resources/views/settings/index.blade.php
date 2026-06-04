@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->

        <!-- write your code here-->
        @if ($hasSettingsAccess)
            <div class="grid gap-3 lg:grid-cols-2 lg:gap-4 xl:grid-cols-3 xl:gap-6 2xl:grid-cols-4">

                @can('department.view')
                    <a href="{{ route('settings.departments.index') }}" class="block group transition duration-300">

                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">

                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>

                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 20V8h10v12" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 12h4M10 16h4" />
                                        </svg>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Departments
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">
                                        Setup Departments
                                    </span>
                                </div>
                            </div>

                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Group employees into clear teams for easier structure and access.
                            </p>

                        </div>
                    </a>
                @endcan

                @can('designation.view')
                    <a href="{{ route('settings.designations.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h.01M4 12h.01M4 17h.01" />
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
                                Define role titles so teams and reporting lines stay consistent.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('shift.view')
                    <a href="{{ route('settings.shifts.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2" />
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
                                Set working hours, rotations, and availability windows.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('technology.view')
                    <a href="{{ route('settings.technologies.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10v10H7z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 10h4M10 14h4" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h1M4 14h1M19 10h1M19 14h1" />
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
                                Maintain the tech stack options used across your projects.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('project_category.view')
                    <a href="{{ route('settings.project-categories.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h10" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l3 3-3 3" />
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
                                Classify projects by service line, delivery type, or business area.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('industry.view')
                    <a href="{{ route('settings.industries.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a12 12 0 010 16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a12 12 0 000 16" />
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
                                Track customer industries for segmentation and reporting.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('project_status.view')
                    <a href="{{ route('settings.project-statuses.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z" />
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
                                Define the status options used to track project progress.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('project_stage.view')
                    <a href="{{ route('settings.project-stages.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18h12" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h4" />
                                            <circle cx="18" cy="18" r="1.5" />
                                            <circle cx="14" cy="12" r="1.5" />
                                            <circle cx="10" cy="6" r="1.5" />
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
                                Set milestone stages to map delivery progress from start to finish.
                            </p>
                        </div>
                    </a>
                @endcan

                @canany(['agile_milestone.view', 'agile_sprint.view'])
                    <a href="{{ auth()->user()->can('agile_milestone.view') ? route('settings.agile-milestones.index') : route('settings.agile-sprints.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h6l2 3h8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h6l2-3h8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 7v10" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Project Agile Flow
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Agile Flow</span>
                                </div>
                            </div>
                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Manage reusable agile milestones and sprint templates for planning.
                            </p>
                        </div>
                    </a>
                @endcanany

                @can('task_settings.view')
                    <a href="{{ route('settings.task-statuses.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6h10M10 12h10M10 18h3" />
                                            <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="4" width="4" height="4" rx="1" />
                                            <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="10" width="4" height="4" rx="1" />
                                            <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="16" width="4" height="4" rx="1" />
                                            <circle stroke-linecap="round" stroke-linejoin="round" cx="18" cy="18" r="3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 13.5v1.5M18 21v1.5M13.5 18h1.5M21 18h1.5M14.8 14.8l1 1M21.2 21.2l-1-1M21.2 14.8l-1 1M14.8 21.2l1-1" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Task Settings
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">Setup Task Settings</span>
                                </div>
                            </div>
                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Setup task settings to map task progress from start to finish.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('kpi.view')
                    <a href="{{ route('settings.kpis.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 7l.7-.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 17.7L7 17" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l.7.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 6.3L7 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        KPIs
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">
                                        Setup KPIs
                                    </span>
                                </div>
                            </div>
                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Define key performance indicators to track success metrics.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('checklist_template.view')
                    <a href="{{ route('settings.checklists.index') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 7l.7-.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 17.7L7 17" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l.7.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 6.3L7 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Checklists
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">
                                        Setup Checklists
                                    </span>
                                </div>
                            </div>
                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Create reusable checklist templates for consistent task execution.
                            </p>
                        </div>
                    </a>
                @endcan

                @can('configuration.view')
                    <a href="{{ route('settings.configurations.edit') }}" class="block group transition duration-300">
                        <div class="relative rounded-lg bg-white p-6 dark:bg-darkblack-600 hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                            <span class="absolute right-6 top-6">
                                <svg width="24" height="25" class="text-bgray-400 dark:text-bgray-300" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 14.3066C10.8954 14.3066 10 13.4144 10 12.3137C10 11.2131 10.8954 10.3208 12 10.3208C13.1046 10.3208 14 11.2131 14 12.3137C14 13.4144 13.1046 14.3066 12 14.3066Z" stroke-width="1.5" />
                                    <path d="M20 14.3066C18.8954 14.3066 18 13.4144 18 12.3137C18 11.2131 18.8954 10.3208 20 10.3208C21.1046 10.3208 22 11.2131 22 12.3137C22 13.4144 21.1046 14.3066 20 14.3066Z" stroke-width="1.5" />
                                    <path d="M4 14.3066C2.89543 14.3066 2 13.4144 2 12.3137C2 11.2131 2.89543 10.3208 4 10.3208C5.10457 10.3208 6 11.2131 6 12.3137C6 13.4144 5.10457 14.3066 4 14.3066Z" stroke-width="1.5" />
                                </svg>
                            </span>
                            <div class="flex space-x-5">
                                <div class="shrink-0">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19v1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 7l.7-.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 17.7L7 17" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l.7.7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.3 6.3L7 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Configurations
                                    </h3>
                                    <span class="text-lg text-bgray-600 dark:text-bgray-50">Company Configurations</span>
                                </div>
                            </div>
                            <p class="pb-8 pt-5 text-lg text-bgray-600 dark:text-bgray-50">
                                Control company-wide defaults, branding, and system behavior.
                            </p>
                        </div>
                    </a>
                @endcan

            </div>
        @else
            <div class="rounded-xl border border-dashed border-bgray-300 bg-white px-6 py-10 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
                <h3 class="text-xl font-semibold text-red-500 dark:text-red-400">No Settings Access</h3>
                <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                    You do not have permission to view any settings sections. Please contact your administrator if you need access.
                </p>
            </div>
        @endif
        <!-- write your code here-->
    <!-- Page ends -->
@endsection
