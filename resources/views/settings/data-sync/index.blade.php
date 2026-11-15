@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'clients',
            'label' => 'API Clients',
            'url' => route('settings.data-sync.clients'),
            'permission' => 'data_sync_settings.view',
        ],
        [
            'key' => 'explorer',
            'label' => 'API Explorer',
            'url' => route('settings.data-sync.explorer'),
            'permission' => 'data_sync_settings.view',
        ],
        [
            'key' => 'mappings',
            'label' => 'Record Mappings',
            'url' => route('settings.data-sync.mappings'),
            'permission' => 'data_sync_settings.view',
        ],
    ];
@endphp

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @if ($currentTab === 'clients')
            @can('data_sync_settings.create')
                <x-button.create-button type="button" id="open-create-client-btn" label="API Client" />
            @endcan
            <x-filters.button />
        @elseif ($currentTab === 'mappings')
            <x-filters.button />
        @endif
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                    @foreach ($tabs as $tab)
                        @can($tab['permission'])
                            @php
                                $isActiveTab = $currentTab === $tab['key'];
                            @endphp
                            <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                {{ $tab['label'] }}
                            </a>
                        @endcan
                    @endforeach
                </div>

                <div class="flex flex-col space-y-5">
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

    @if ($currentTab === 'clients')
        <x-filters.drawer>
            <x-filters.input-search name="search" label="Application Name or Key ID" />
            <x-filters.select name="status" label="Status" :options="[
                'active' => 'Active',
                'inactive' => 'Inactive / Revoked',
                'expired' => 'Expired',
            ]" />
        </x-filters.drawer>
    @elseif ($currentTab === 'mappings')
        <x-filters.drawer>
            <x-filters.input-search name="search" label="External Code, ID, or PMS Code" />
            @php
                $clientFilterOptions = ($apiClients ?? collect())->mapWithKeys(function($c) {
                    return [$c->id => $c->name . ($c->trashed() ? ' (Deleted)' : (!$c->is_active ? ' (Inactive)' : ''))];
                })->all();
            @endphp
            <x-filters.select name="api_client_id" label="API Client" :options="$clientFilterOptions" />
            <x-filters.select name="entity_type" label="Entity Type" :options="[
                'customer' => 'Customer',
                'project' => 'Project',
            ]" />
        </x-filters.drawer>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        function closeOneTimeKeyModal() {
            var input = document.getElementById('one-time-key-value');
            if (input) input.value = '';
            var modal = document.getElementById('one-time-key-modal');
            if (modal) modal.classList.add('hidden');
            window.location.reload();
        }

        // Close on Outside Click
        document.addEventListener('click', function (e) {
            var target = e.target;

            // One-Time Key Modal Outside Click
            var oneTimeModal = document.getElementById('one-time-key-modal');
            if (oneTimeModal && !oneTimeModal.classList.contains('hidden')) {
                if (!target.closest('.modal-content') && (target.closest('#one-time-key-modal') || target.classList.contains('modal-overlay'))) {
                    closeOneTimeKeyModal();
                    return;
                }
            }

            // Create Client Modal Outside Click
            var createModal = document.getElementById('create-client-modal');
            if (createModal && !createModal.classList.contains('hidden')) {
                if (!target.closest('.modal-content') && (target.closest('#create-client-modal') || target.classList.contains('modal-overlay'))) {
                    createModal.classList.add('hidden');
                    return;
                }
            }

            // Edit Client Modal Outside Click
            var editModal = document.getElementById('edit-client-modal');
            if (editModal && !editModal.classList.contains('hidden')) {
                if (!target.closest('.modal-content') && (target.closest('#edit-client-modal') || target.classList.contains('modal-overlay'))) {
                    editModal.classList.add('hidden');
                    return;
                }
            }

            // Modal Close Buttons (Cancel / ✕ buttons)
            var closeBtn = target.closest('.modal-close');
            if (closeBtn) {
                var parentModal = closeBtn.closest('.modal');
                if (parentModal) {
                    if (parentModal.id === 'one-time-key-modal') {
                        closeOneTimeKeyModal();
                    } else {
                        parentModal.classList.add('hidden');
                    }
                }
                return;
            }

            // Done Button on One-Time Key Modal
            if (target.closest('#one-time-key-done-btn')) {
                closeOneTimeKeyModal();
            }
        });

        // Close on Escape Key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                var oneTimeModal = document.getElementById('one-time-key-modal');
                if (oneTimeModal && !oneTimeModal.classList.contains('hidden')) {
                    closeOneTimeKeyModal();
                    return;
                }
                var createModal = document.getElementById('create-client-modal');
                if (createModal && !createModal.classList.contains('hidden')) {
                    createModal.classList.add('hidden');
                    return;
                }
                var editModal = document.getElementById('edit-client-modal');
                if (editModal && !editModal.classList.contains('hidden')) {
                    editModal.classList.add('hidden');
                }
            }
        });
    })();
</script>
@endpush
