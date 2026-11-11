@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        

        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('feature-settings.logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-bgray-200 bg-white px-4 py-2.5 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                    <i class="fa-solid fa-lock mr-1"></i>
                    Lock Settings
                </button>
            </form>

            <x-button.create-button
                :href="route('feature-settings.create')"
                label="Feature"
            />
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-300 dark:border-success-900/40 dark:bg-darkblack-500">
            {{ session('success') }}
        </div>
    @endif

    <!-- Error Messages -->
    @if($errors->any())
        <div class="mb-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-300 dark:border-error-900/40 dark:bg-darkblack-500">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Feature Announcements -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

                

                <div class="flex flex-col space-y-5">

                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">

                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            #
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Feature
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Type
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Module
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Priority
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Status
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Publish Date
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Actions
                                        </span>
                                    </td>

                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $startNumber = ($features->currentPage() - 1) * $features->perPage();

                                    $typeLabels = [
                                        'new_feature' => 'New Feature',
                                        'improvement' => 'Improvement',
                                        'important_update' => 'Important Update',
                                        'bug_fix' => 'Bug Fix',
                                    ];

                                    $priorityClasses = [
                                        'low' => 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300',
                                        'normal' => 'bg-success-50 text-success-300',
                                        'high' => 'bg-error-50 text-error-300',
                                    ];

                                    $statusClasses = [
                                        'draft' => 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300',
                                        'published' => 'bg-success-50 text-success-300',
                                        'archived' => 'bg-warning-50 text-warning-300',
                                    ];
                                @endphp

                                @forelse($features as $feature)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">

                                        <!-- Number -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                {{ $startNumber + $loop->iteration }}
                                            </span>
                                        </td>

                                        <!-- Feature -->
                                        <td class="min-w-[240px] px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-4">

                                                @if($feature->cover_image)
                                                    <div class="h-[56px] w-[56px] flex-shrink-0">
                                                        <img
                                                            src="{{ asset('storage/' . $feature->cover_image) }}"
                                                            alt="{{ $feature->title }}"
                                                            class="h-full w-full rounded-lg object-cover"
                                                        >
                                                    </div>
                                                @else
                                                    <div class="flex h-[56px] w-[56px] flex-shrink-0 items-center justify-center rounded-lg bg-bgray-50 text-xl text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                                        {{ $feature->icon ?: '✦' }}
                                                    </div>
                                                @endif

                                                <div class="min-w-0 flex-1">
                                                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                                                        {{ $feature->title }}
                                                    </h4>

                                                    <p class="mt-1 text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                                        {{ \Illuminate\Support\Str::limit($feature->short_description, 75) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Type -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="whitespace-nowrap text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                {{ $typeLabels[$feature->feature_type] ?? ucwords(str_replace('_', ' ', $feature->feature_type)) }}
                                            </span>
                                        </td>

                                        <!-- Module -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="whitespace-nowrap text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                {{ $feature->module_name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Priority -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="inline-flex whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold {{ $priorityClasses[$feature->priority] ?? $priorityClasses['normal'] }}">
                                                {{ ucfirst($feature->priority ?? 'normal') }}
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="inline-flex whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$feature->status] ?? $statusClasses['draft'] }}">
                                                {{ ucfirst($feature->status) }}
                                            </span>
                                        </td>

                                        <!-- Publish Date -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="whitespace-nowrap text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                {{ $feature->publish_at?->format('d M Y') ?? '--' }}
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-2">

                                                <x-edit-button
                                                    :action="route('feature-settings.edit', $feature)"
                                                />

                                                <x-delete-form
                                                    :action="route('feature-settings.destroy', $feature)"
                                                />

                                            </div>
                                        </td>

                                    </tr>
                                @empty
                                    <x-table-no-data
                                        col-span="8"
                                        message="No feature announcements yet."
                                        sub-message="Create your first announcement to get started."
                                    />
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                    <x-pagination
                        :paginator="$features"
                        :per-page="$features->perPage()"
                    />

                </div>
            </div>
        </section>
    </div>

    <!-- Page ends -->
@endsection