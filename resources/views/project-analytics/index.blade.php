@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] dark:bg-darkblack-700 sm:px-5 xl:px-4" data-project-analytics-root>
        @php
            $hasProjectSelected = ! empty($projectAnalyticsSelectedProjectId);
        @endphp

        <!-- 1. Overall Dashboard View (Shown when All Projects is selected) -->
        <div class="{{ $hasProjectSelected ? 'hidden ' : '' }}space-y-4" data-overall-dashboard-view>
            <!-- Summary Tiles -->
            @include('project-analytics.partials.summary-tiles')

            <!-- Doughnut Charts (Flow, Status, Stage) -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @include('project-analytics.partials.projects-by-flow')
                @include('project-analytics.partials.projects-by-status')
                @include('project-analytics.partials.projects-by-stage')
            </div>

            <!-- Projects by Service Type & Upcoming Contract Expirations -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="xl:col-span-5">
                    @include('project-analytics.partials.projects-by-service-type')
                </div>
                <div class="xl:col-span-7">
                    @include('project-analytics.partials.upcoming-contracts')
                </div>
            </div>
        </div>

        <!-- 2. Single Project Analytics View (Shown when a specific project is selected) -->
        <div class="{{ ! $hasProjectSelected ? 'hidden ' : '' }}space-y-4" data-single-project-dashboard-view>
            <!-- Project Metadata & Task Summary Tiles -->
            @include('project-analytics.partials.single-project-summary')

            <!-- Single Project Charts: Task Status, Task Priority, Time Comparison -->
            @include('project-analytics.partials.single-project-charts')
        </div>
    </main>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/analytics/project-analytics-filters.js',
        'resources/js/modules/analytics/project-analytics-dashboard.js'
    ])
@endpush
