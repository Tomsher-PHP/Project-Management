@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-12 xl:pb-12')

@section('page-content')
@php
    // Project KPIs
    $totalProjectsCount = $totalProjectsCount ?? 110;
    $activeProjectsCount = $activeProjectsCount ?? 20;
    $onHoldProjectsCount = $onHoldProjectsCount ?? 50;
    $completedProjectsCount = $completedProjectsCount ?? 40;

    // Task KPIs
    $totalTasksCount = $totalTasksCount ?? 350;
    $activeTasksCount = $activeTasksCount ?? 120;
    $onHoldTasksCount = $onHoldTasksCount ?? 80;
    $completedTasksCount = $completedTasksCount ?? 150;

    // Request counts
    $pendingTaskRequests = $pendingTaskRequests ?? 12;
    $pendingTimeLogRequests = $pendingTimeLogRequests ?? 8;
    $pendingHandoffRequests = $pendingHandoffRequests ?? 3;
    $pendingBreakRequests = $pendingBreakRequests ?? 5;
@endphp

<!-- Main Outer Wrapper: space-y-6 -->
<div class="space-y-6">
        
        <!-- 1. PROJECTS OVERVIEW KPI SECTION -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Projects Overview</h2>
                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50 bg-bgray-100 dark:bg-darkblack-500 px-2.5 py-1 rounded-full">Dynamic Statuses</span>
            </div>
            <!-- KPI cards grid: grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Total Projects Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Total Projects</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $totalProjectsCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-bgray-400"></div>
                </div>

                <!-- Active Projects Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Active</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $activeProjectsCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-950/30 dark:text-blue-400">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500"></div>
                </div>

                <!-- On Hold Projects Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">On Hold</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $onHoldProjectsCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-50 text-orange dark:bg-amber-950/30 dark:text-orange">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-orange"></div>
                </div>

                <!-- Completed Projects Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Completed</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $completedProjectsCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 text-success-300 dark:bg-emerald-950/30 dark:text-success-300">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-success-300"></div>
                </div>
            </div>
        </div>

        <!-- 2. TASKS OVERVIEW KPI SECTION -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Tasks Overview</h2>
                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50 bg-bgray-100 dark:bg-darkblack-500 px-2.5 py-1 rounded-full">Dynamic Statuses</span>
            </div>
            <!-- KPI cards grid: grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Total Tasks Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Total Tasks</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $totalTasksCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-bgray-400"></div>
                </div>

                <!-- Active Tasks Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Active Tasks</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $activeTasksCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-950/30 dark:text-blue-400">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500"></div>
                </div>

                <!-- On Hold Tasks Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">On Hold Tasks</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $onHoldTasksCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-50 text-orange dark:bg-amber-950/30 dark:text-orange">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-orange"></div>
                </div>

                <!-- Completed Tasks Card -->
                <div class="group relative overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Completed Tasks</span>
                            <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white">{{ $completedTasksCount }}</h3>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 text-success-300 dark:bg-emerald-950/30 dark:text-success-300">
                            <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-success-300"></div>
                </div>
            </div>
        </div>

        <!-- Columns container: flex flex-col xl:flex-row gap-6 -->
        <div class="flex flex-col xl:flex-row gap-6">
            
            <!-- Left/Main content (Charts): flex-1 xl:flex-[3.2] space-y-6 -->
            <div class="flex-1 xl:flex-[3.2] space-y-6">
                
                <!-- 3. ANALYTICS PLACEHOLDERS SECTION -->
                <div class="space-y-6">
            <!-- Two Column Charts Layout -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                
                <!-- Left: Project Status Distribution Chart Placeholder -->
                <div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Project Status Distribution</h3>
                        <span class="text-xs text-bgray-600 dark:text-bgray-50 bg-bgray-100 dark:bg-darkblack-500 px-2 py-1 rounded">Donut Chart</span>
                    </div>
                    <!-- High Fidelity Visual Skeleton -->
                    <div class="flex h-[250px] w-full flex-col items-center justify-center relative">
                        <!-- Visual Ring Skeleton (Donut Chart Shape) -->
                        <div class="relative flex h-40 w-40 items-center justify-center rounded-full border-[18px] border-slate-100 dark:border-darkblack-500">
                            <!-- Ring Highlights -->
                            <div class="absolute inset-[-18px] rounded-full border-[18px] border-transparent border-t-blue-500 border-r-success-300 rotate-45"></div>
                            <div class="text-center">
                                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">Total</span>
                                <p class="text-xl font-extrabold text-bgray-900 dark:text-white">{{ $totalProjectsCount }}</p>
                            </div>
                        </div>
                        
                        <!-- Legend -->
                        <div class="mt-6 flex items-center space-x-4">
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">Active</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-orange"></span>
                                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">On Hold</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-success-300"></span>
                                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">Completed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Task Progress Chart Placeholder -->
                <div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
                    <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Task Progress Overview</h3>
                        <span class="text-xs text-bgray-600 dark:text-bgray-50 bg-bgray-100 dark:bg-darkblack-500 px-2 py-1 rounded">Line Chart</span>
                    </div>
                    <!-- High Fidelity Visual Skeleton -->
                    <div class="flex h-[250px] w-full flex-col justify-between relative p-2">
                        <!-- Mock Grid Lines -->
                        <div class="flex-1 space-y-8 mt-2 w-full">
                            <div class="border-b border-dashed border-slate-100 dark:border-darkblack-500 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-100 dark:border-darkblack-500 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-100 dark:border-darkblack-500 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-100 dark:border-darkblack-500 w-full h-0"></div>
                        </div>
                        
                        <!-- Absolute Placeholder Mock Line Path -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center bg-white/95 dark:bg-darkblack-600/95 px-4 py-2 rounded-lg shadow-sm border border-slate-100 dark:border-darkblack-500 z-10">
                                <svg class="h-6 w-6 text-blue-500 mx-auto mb-1" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">Line Visualization Placeholder</span>
                            </div>
                        </div>
                        
                        <!-- X Axis Labels -->
                        <div class="flex justify-between text-[10px] font-bold text-bgray-600 dark:text-bgray-50 mt-2 px-1">
                            <span>Week 1</span>
                            <span>Week 2</span>
                            <span>Week 3</span>
                            <span>Week 4</span>
                            <span>Week 5</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Full Width Chart Layout (Pending Requests Breakdown) -->
            <div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
                <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                    <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Pending Requests Breakdown</h3>
                    <span class="text-xs text-bgray-600 dark:text-bgray-50 bg-bgray-100 dark:bg-darkblack-500 px-2 py-1 rounded">Bar Chart</span>
                </div>
                <!-- High Fidelity Visual Skeleton -->
                <div class="flex h-[250px] w-full flex-col justify-between relative p-2">
                    <!-- Mock Bars -->
                    <div class="flex-1 flex items-end justify-around w-full px-6">
                        <!-- Bar 1: Task Requests -->
                        <div class="w-16 bg-purple-500/20 dark:bg-purple-500/10 border-t-2 border-purple-500 rounded-t-md h-[40%] flex items-center justify-center group hover:bg-purple-500/30 transition-all">
                            <span class="text-xs font-extrabold text-purple-700 dark:text-purple-300">{{ $pendingTaskRequests }}</span>
                        </div>
                        <!-- Bar 2: Time Log Requests -->
                        <div class="w-16 bg-indigo-500/20 dark:bg-indigo-500/10 border-t-2 border-indigo-500 rounded-t-md h-[30%] flex items-center justify-center group hover:bg-indigo-500/30 transition-all">
                            <span class="text-xs font-extrabold text-indigo-700 dark:text-indigo-300">{{ $pendingTimeLogRequests }}</span>
                        </div>
                        <!-- Bar 3: Handoff Requests -->
                        <div class="w-16 bg-blue-500/20 dark:bg-blue-500/10 border-t-2 border-blue-500 rounded-t-md h-[15%] flex items-center justify-center group hover:bg-blue-500/30 transition-all">
                            <span class="text-xs font-extrabold text-blue-700 dark:text-blue-300">{{ $pendingHandoffRequests }}</span>
                        </div>
                        <!-- Bar 4: Break Requests -->
                        <div class="w-16 bg-rose-500/20 dark:bg-rose-500/10 border-t-2 border-rose-500 rounded-t-md h-[20%] flex items-center justify-center group hover:bg-rose-500/30 transition-all">
                            <span class="text-xs font-extrabold text-rose-700 dark:text-rose-300">{{ $pendingBreakRequests }}</span>
                        </div>
                    </div>
                    
                    <!-- Axis Labels -->
                    <div class="flex justify-around text-xs font-bold text-bgray-600 dark:text-bgray-50 mt-4 border-t border-slate-100 dark:border-darkblack-500 pt-2">
                        <span class="w-16 text-center truncate">Task</span>
                        <span class="w-16 text-center truncate">Time Log</span>
                        <span class="w-16 text-center truncate">Handoff</span>
                        <span class="w-16 text-center truncate">Break</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Sidebar: w-full xl:w-auto xl:flex-[1] shrink-0 -->
    <aside class="w-full xl:w-auto xl:flex-[1] shrink-0">
        
        <!-- Sticky Notification Card -->
        <div class="rounded-xl border border-bgray-100 bg-white p-6 xl:p-4 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
            
            <!-- Sidebar Header -->
            <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                <div class="flex items-center space-x-2">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notifications</h3>
                </div>
                <span class="inline-flex h-5 items-center justify-center rounded-full bg-rose-50 px-2 text-xs font-bold text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                    3 New
                </span>
            </div>

            <!-- Scrollable Notifications List Feed (UI Only) -->
            <div class="max-h-[500px] overflow-y-auto pr-1 space-y-4">
                
                <!-- Notification 1 -->
                <a href="{{ route('tasks.requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-purple-200 bg-slate-50/50 hover:bg-purple-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-purple-900/50 dark:bg-darkblack-500/20 dark:hover:bg-purple-950/10">
                    <div class="flex space-x-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-purple-500 dark:bg-purple-950/40 dark:text-purple-400">
                            <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">Task Approvals</span>
                                <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                            </div>
                            <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $pendingTaskRequests }} new pending task requests</p>
                            <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">Just now</span>
                        </div>
                    </div>
                </a>

                <!-- Notification 2 -->
                <a href="{{ route('handoff_requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-blue-200 bg-slate-50/50 hover:bg-blue-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-blue-900/50 dark:bg-darkblack-500/20 dark:hover:bg-blue-950/10">
                    <div class="flex space-x-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-500 dark:bg-blue-950/40 dark:text-blue-400">
                            <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Handoff</span>
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            </div>
                            <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">New Handoff request received</p>
                            <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">2 hours ago</span>
                        </div>
                    </div>
                </a>

                <!-- Notification 3 -->
                <a href="{{ route('break-requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-rose-200 bg-slate-50/50 hover:bg-rose-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-rose-900/50 dark:bg-darkblack-500/20 dark:hover:bg-rose-950/10">
                    <div class="flex space-x-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-950/40 dark:text-rose-400">
                            <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m11.314 11.314l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                            </svg>
                        </div>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400">Breaks</span>
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            </div>
                            <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $pendingBreakRequests }} break approvals pending</p>
                            <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">Yesterday</span>
                        </div>
                    </div>
                </a>

            </div>
        </div>

    </aside>

</div>

</div>
@endsection
