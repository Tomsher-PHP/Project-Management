@extends('layouts.master')
@section('without-main', true)

@push('styles')
    @vite(['resources/css/modules/user-timeline.css', 'resources/css/modules/kanban.css'])
    <style>
        #kanban-container .kanban-board {
            height: auto;
        }
    </style>
@endpush

@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] dark:bg-darkblack-700 sm:px-5 xl:px-4" data-user-workspace data-task-create-root>
        <div class="space-y-2.5">

            <!-- Summary Section -->
            @include('analytics.partials.summary-tiles')

            <!-- Analytics Insights Charts -->
            @include('analytics.partials.insights-charts')

        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/workspace/workspace-user-selector.js')
    @vite('resources/js/modules/analytics/summary.js')
@endpush
