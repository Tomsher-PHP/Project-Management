@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] dark:bg-darkblack-700 sm:px-5 xl:px-4" data-project-analytics-root>
        @php
            $hasProjectSelected = !empty($projectAnalyticsSelectedProjectId);
        @endphp

        <!-- Summary Counts Section (Referenced from Dashboard) -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4" data-project-analytics-summary-section data-summary-url="{{ route('project.analytics.summary') }}" data-tile-url="{{ route('project.analytics.tile-details') }}" data-dashboard-summary-section data-dashboard-summary-url="{{ route('project.analytics.summary') }}" data-dashboard-tile-url="{{ route('project.analytics.tile-details') }}">
            <div data-project-counts-wrapper class="{{ $hasProjectSelected ? 'hidden' : '' }}">
                @include('project-analytics.partials.project-counts')
            </div>
            <div data-task-counts-wrapper class="{{ $hasProjectSelected ? 'md:col-span-2' : '' }}">
                @include('project-analytics.partials.task-counts')
            </div>
        </div>

        <!-- 1. Overall Dashboard View (Shown when All Projects is selected) -->
        <div class="{{ $hasProjectSelected ? 'hidden ' : '' }}space-y-4" data-overall-dashboard-view>
            <!-- Doughnut Charts (Flow, Status, Stage) -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @include('project-analytics.partials.projects-by-flow')
                @include('project-analytics.partials.projects-by-status')
                @include('project-analytics.partials.projects-by-stage')
            </div>

            <!-- Projects by Service Type & Upcoming Timeline Expirations -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="xl:col-span-5">
                    @include('project-analytics.partials.projects-by-service-type')
                </div>
                <div class="xl:col-span-7">
                    @include('project-analytics.partials.upcoming-timelines')
                </div>
            </div>

            <!-- Server & Hosting Expire Soon & Expired Hosting (Permission: project_hosting.view) -->
            @can('project_hosting.view')
                <div>
                    @include('project-analytics.partials.expiring-hosting')
                </div>
                <div>
                    @include('project-analytics.partials.expired-hosting')
                </div>
            @endcan

            <!-- Parent Projects Overview -->
            <div>
                @include('project-analytics.partials.parent-projects-overview')
            </div>
        </div>

        <!-- 2. Single Project Analytics View (Shown when a specific project is selected) -->
        <div class="{{ !$hasProjectSelected ? 'hidden ' : '' }}space-y-4" data-single-project-dashboard-view data-overview-url="{{ route('project.analytics.project-overview') }}">
            <!-- Project Metadata Card -->
            @include('project-analytics.partials.single-project-summary')

            <!-- Project Time Progress -->
            @include('project-analytics.partials.single-project-time-progress')

            <!-- Milestone Journey Burn-up Chart (Agile only) -->
            @include('project-analytics.partials.single-project-burnup-chart')

            <!-- Single Project Charts: Task Status Breakdown, User Wise Section -->
            @include('project-analytics.partials.single-project-charts')

        </div>
    </main>

    <!-- Dashboard Tile Modal -->
    <div id="dashboard-tile-modal" class="fixed inset-0 z-[70] hidden items-center justify-center overflow-hidden px-4 py-4 sm:py-6">
        <div data-dashboard-tile-overlay class="fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-60 cursor-pointer"></div>

        <div class="relative flex min-h-[420px] max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:border dark:border-darkblack-400 dark:bg-darkblack-600 sm:max-h-[calc(100vh-3rem)]">
            <div id="dashboard-tile-modal-content" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <!-- AJAX content injected here -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.translations = window.translations || {};
        window.translations.labels = window.translations.labels || {
            spent_hours: @json(__('label.project.spent_hours')),
            spent: @json(__('label.project.spent')),
            estimated_hours: @json(__('label.project.estimated_hours')),
            estimated: @json(__('label.project.estimated')),
            exceeded: @json(__('label.project.exceeded')),
        };
    </script>
    @vite(['resources/js/modules/analytics/project-analytics-filters.js', 'resources/js/modules/analytics/project-analytics-dashboard.js'])
@endpush
