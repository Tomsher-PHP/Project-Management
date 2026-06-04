@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[80px] sm:pt-[70px] xl:px-8 xl:pb-8')

@section('page-content')


    <!-- Main Outer Wrapper: space-y-6 -->
    <div class="space-y-4" data-dashboard-summary-section data-dashboard-summary-url="{{ route('dashboard.summary') }}">

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
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
