@extends('layouts.master')

@section('page-content')
    <div class="space-y-6">
        @php
            $availableTabs = [
                'general' => 'Report Configurations',
                'recipients' => 'Recipients',
                'history' => 'History',
            ];

            $reportTabs = $currentReport['tabs'] ?? array_keys($availableTabs);
            $tabs = array_intersect_key($availableTabs, array_flip($reportTabs));
            $reportPermission = $currentReport['permission'] ?? 'report_settings.manage';
        @endphp

        <div class="grid grid-cols-12 gap-6">
            @include('settings.report-settings.aside')

            <!-- RIGHT SETTINGS PANEL -->
            <div class="col-span-9 lg:col-span-9 min-w-0">
                <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                    <!-- TABS -->
                    <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 p-4 dark:border-darkblack-400">
                        @foreach ($tabs as $key => $label)
                            <button type="button" data-report-tab="{{ $key }}" aria-controls="report-panel-{{ $key }}" aria-selected="false" class="report-tab {{ $key === 'general' ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <!-- GENERAL SETTINGS / CONFIGURATIONS LIST TAB -->
                    @include('settings.report-settings.project-reports.partials.report-configurations')

                    <!-- RECIPIENTS TAB -->
                    @include('settings.report-settings.project-reports.partials.recipients')

                    <!-- HISTORY TAB -->
                    @include('settings.report-settings.project-reports.partials.history')
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    @include('settings.report-settings.project-reports.partials.report-modal')
@endsection

@push('scripts')
    @vite('resources/js/settings/data-sync/report-settings/project-report.js')
@endpush
