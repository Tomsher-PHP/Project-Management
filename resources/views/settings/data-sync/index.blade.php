@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'clients',
            'label' => 'API Clients',
            'url' => route('settings.data-sync.clients'),
        ],
        [
            'key' => 'explorer',
            'label' => 'API Explorer',
            'url' => route('settings.data-sync.explorer'),
        ],
        [
            'key' => 'mappings',
            'label' => 'Record Mappings',
            'url' => route('settings.data-sync.mappings'),
        ],
    ];
@endphp

@section('page-content')
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-bgray-300 pb-5 dark:border-darkblack-400">
                    <div class="flex items-center gap-3">
                        <x-back-button :url="route('settings.index')" label="Back" />
                        <div>
                            <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                Data Sync
                            </h3>
                            <p class="text-sm text-bgray-600 dark:text-bgray-300">
                                Manage API clients, explore endpoints, and view external record mappings.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                    @foreach ($tabs as $tab)
                        @php
                            $isActiveTab = $currentTab === $tab['key'];
                        @endphp
                        <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="w-full">
                    @if ($currentTab === 'clients')
                        @include('settings.data-sync.partials.clients-tab')
                    @elseif ($currentTab === 'explorer')
                        @include('settings.data-sync.partials.explorer-tab')
                    @elseif ($currentTab === 'mappings')
                        @include('settings.data-sync.partials.mappings-tab')
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

