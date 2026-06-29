@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->

    <!-- write your code here-->
    @if ($hasSettingsAccess)
        <div class="flex flex-wrap items-center gap-5 dark:bg-darkblack-700">

            @can('department.view')
                <a href="{{ route('settings.departments.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 20V8h10v12" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 12h4M10 16h4" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Departments
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('designation.view')
                <a href="{{ route('settings.designations.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h.01M4 12h.01M4 17h.01" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Designations
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('shift.view')
                <a href="{{ route('settings.shifts.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Shifts
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('technology.view')
                <a href="{{ route('settings.technologies.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10v10H7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 10h4M10 14h4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h1M4 14h1M19 10h1M19 14h1" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Technologies
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('project_category.view')
                <a href="{{ route('settings.project-categories.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h10" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h7" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l3 3-3 3" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Project Categories
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('industry.view')
                <a href="{{ route('settings.industries.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a12 12 0 010 16" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4a12 12 0 000 16" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Industries
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('project_status.view')
                <a href="{{ route('settings.project-statuses.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12l4 4 8-8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Project Statuses
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('project_stage.view')
                <a href="{{ route('settings.project-stages.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18h12" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h4" />
                                    <circle cx="18" cy="18" r="1.5" />
                                    <circle cx="14" cy="12" r="1.5" />
                                    <circle cx="10" cy="6" r="1.5" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Project Stages
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @canany(['agile_milestone.view', 'agile_sprint.view'])
                <a href="{{ auth()->user()->can('agile_milestone.view') ? route('settings.agile-milestones.index') : route('settings.agile-sprints.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h6l2 3h8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h6l2-3h8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 7v10" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Project Agile Flow
                            </h3>
                        </div>
                    </div>
                </a>
            @endcanany

            @can('task_settings.view')
                <a href="{{ route('settings.task-statuses.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6h10M10 12h10M10 18h3" />
                                    <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="4" width="4" height="4" rx="1" />
                                    <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="10" width="4" height="4" rx="1" />
                                    <rect stroke-linecap="round" stroke-linejoin="round" x="3" y="16" width="4" height="4" rx="1" />
                                    <circle stroke-linecap="round" stroke-linejoin="round" cx="18" cy="18" r="3" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 13.5v1.5M18 21v1.5M13.5 18h1.5M21 18h1.5M14.8 14.8l1 1M21.2 21.2l-1-1M21.2 14.8l-1 1M14.8 21.2l1-1" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Task Settings
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('kpi.view')
                <a href="{{ route('settings.kpis.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
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
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                KPIs
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('checklist_template.view')
                <a href="{{ route('settings.checklists.index') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Checklists
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

            @can('configuration.view')
                <a href="{{ route('settings.configurations.edit') }}" class="block group transition duration-300">
                    <div class="mx-auto max-w-[200px] min-w-[200px] rounded-lg bg-white dark:bg-darkblack-600 p-3 aspect-square hover:shadow-lg hover:-translate-y-1 transition duration-300 cursor-pointer">
                        <div class="flex flex-col items-center justify-center text-center h-full">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900 shrink-0">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-md font-semibold text-bgray-900 dark:text-white">
                                Configurations
                            </h3>
                        </div>
                    </div>
                </a>
            @endcan

        </div>
    @else
        <div class="rounded-xl border border-dashed border-bgray-300 bg-white px-6 py-10 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <h3 class="text-xl font-semibold text-red-500 dark:text-red-400">No Settings Access</h3>
            <p class="mt-2 text-md text-bgray-600 dark:text-bgray-300">
                You do not have permission to view any settings sections. Please contact your administrator if you need access.
            </p>
        </div>
    @endif
    <!-- write your code here-->
    <!-- Page ends -->
@endsection
