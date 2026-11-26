@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'connections',
            'label' => 'Connections',
            'url' => route('settings.data-sync.outbound.connections'),
            'permission' => 'data_sync_settings.view',
        ],
        [
            'key' => 'history',
            'label' => 'History',
            'url' => route('settings.data-sync.outbound.history'),
            'permission' => 'data_sync_settings.view',
        ],
    ];
@endphp

@section('page-content')
    <div class="space-y-6" data-data-sync-settings>
        <div class="grid grid-cols-12 gap-6">
            @include('settings.data-sync.aside', ['currentSection' => 'outbound'])

            <!-- RIGHT SETTINGS PANEL -->
            <div class="col-span-9 lg:col-span-9 min-w-0">
                <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                    <!-- TABS & ACTIONS -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach ($tabs as $tab)
                                @can($tab['permission'])
                                    @php
                                        $isActiveTab = ($currentTab ?? 'connections') === $tab['key'];
                                    @endphp
                                    <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                        {{ $tab['label'] }}
                                    </a>
                                @endcan
                            @endforeach
                        </div>

                        <div class="flex items-center gap-3">
                            @if (($currentTab ?? 'connections') === 'connections')
                                @can('data_sync_settings.create')
                                    <x-button.create-button type="button" id="open-create-connection-btn" label="Connection" />
                                @endcan
                                <x-filters.button />
                            @elseif ($currentTab === 'history')
                                <x-filters.button />
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col space-y-5">
                        @if (($currentTab ?? 'connections') === 'connections')
                            @include('settings.data-sync.outbound.partials.connections-tab')
                        @elseif ($currentTab === 'history')
                            @include('settings.data-sync.outbound.partials.history-tab')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($currentTab ?? 'connections') === 'connections')
        @include('settings.data-sync.outbound.modals.create-connection-modal')

        <x-filters.drawer>
            <x-filters.input-search name="search" label="Connection Name or Base URL" />
            <x-filters.select name="status" label="Status" :options="[
                'active' => 'Active',
                'inactive' => 'Inactive',
            ]" />
        </x-filters.drawer>
    @elseif ($currentTab === 'history')
        @include('settings.data-sync.outbound.modals.history-details-modal')

        <x-filters.drawer>
            <x-filters.select name="connection_id" label="Connection" :options="isset($connections) ? $connections->pluck('name', 'id')->toArray() : []" />
            <x-filters.select name="module" label="Module" :options="$modules ?? []" />
            <x-filters.select name="operation" label="Operation" :options="$operations ?? []" />
            <x-filters.select name="status" label="Status" :options="$statuses ?? []" />
            <x-filters.date-range label="Date Range" startName="date_from" endName="date_to" />
        </x-filters.drawer>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/settings/data-sync/data-sync-settings.js')
    @vite('resources/js/settings/data-sync/outbound-tab.js')
@endpush
