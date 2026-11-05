@extends('layouts.master')

@section('page-content')
    <div class="w-full" data-bulletins-root>

        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('bulletin.create')
                <x-button.create-button type="button" data-bulletin-create data-url="{{ route('bulletins.create') }}" label="Bulletin" />
            @endcan

            <x-filters.button />
            <x-filters.list-search placeholder="Search bulletins..." />
        </div>

        <section>
            @include('bulletins.partials.bulletin-table')
            <x-pagination :paginator="$bulletins" :per-page="$perPage" />
        </section>

        <div class="fixed inset-0 z-[80] hidden overflow-y-auto" data-bulletin-modal aria-hidden="true">
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-bulletin-modal-close></div>
            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 hidden w-full max-w-5xl" data-bulletin-modal-content></div>
                <div class="relative z-10 rounded-xl bg-white px-8 py-7 shadow-xl dark:bg-darkblack-600" data-bulletin-modal-loading>
                    <div class="flex items-center gap-3 text-sm font-medium text-bgray-700 dark:text-bgray-300">
                        <svg class="h-5 w-5 animate-spin text-success-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4z"></path>
                        </svg>
                        Loading bulletin...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Bulletin title" />
        <x-filters.multi-select name="bulletin_category_id" label="Category" :options="$categories" />
        <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
        <x-filters.multi-select name="audience_type" label="Audience" :options="$audienceOptions" />
        <x-filters.multi-select name="status" label="Status" :options="$statusOptions" />
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/bulletins/bulletins-management.js')
@endpush
