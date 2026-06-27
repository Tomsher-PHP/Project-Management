@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[80px] sm:pt-[70px] xl:px-8 xl:pb-8')

@section('page-content')


    <!-- Main Outer Wrapper: space-y-6 -->
    <div class="space-y-4" data-dashboard-summary-section data-dashboard-summary-url="{{ route('dashboard.summary') }}" data-dashboard-tile-url="{{ route('dashboard.tile-details') }}">

        <!-- 1. PROJECTS OVERVIEW KPI SECTION -->
        @include('dashboard.partials.project-counts')

        <!-- 2. TASKS OVERVIEW KPI SECTION -->
        @include('dashboard.partials.task-counts')

        <!-- Columns container: flex flex-col xl:flex-row gap-6 -->
        <div class="flex flex-col xl:flex-row gap-6">

            <!-- Left/Main content (Charts): flex-1 xl:flex-[4.8] space-y-6 -->
            <div class="flex-1 xl:flex-[4.8] space-y-6">

                @include('dashboard.partials.daily-time')

                @include('dashboard.partials.running-tasks')

            </div>

            <!-- Right Sidebar: w-full xl:w-auto xl:flex-[0.4] shrink-0 -->
            <div class="w-full xl:w-auto xl:flex-[0.4] shrink-0">

                @include('dashboard.partials.requests')

            </div>

        </div>

    </div>

    <!-- Dashboard Tile Modal -->
    <div id="dashboard-tile-modal" class="fixed inset-0 z-[70] hidden items-center justify-center overflow-hidden px-4 py-4 sm:py-6">
        <div data-dashboard-tile-overlay class="fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-60 cursor-pointer"></div>

        <div class="relative flex min-h-[420px] max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:border dark:border-darkblack-400 dark:bg-darkblack-600 sm:max-h-[calc(100vh-3rem)]">
            <div id="dashboard-tile-modal-content" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <!-- AJAX content injected here -->
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
