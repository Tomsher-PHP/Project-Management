@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-notifications-page>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                <div class="flex flex-wrap items-center justify-end gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span id="selected-count" class="inline-flex h-11 items-center rounded-lg bg-bgray-100 px-4 text-sm font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                        0 selected
                    </span>

                    <button type="button" id="bulk-delete-btn" data-bulk-delete-url="{{ route('notifications.bulkDelete') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-red-200 bg-red-50 px-5 text-sm font-semibold leading-none text-red-600 shadow-sm transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-200 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:bg-bgray-100 disabled:text-bgray-400 disabled:shadow-none dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-500 dark:disabled:text-bgray-700"
                        disabled>
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Bulk Delete</span>
                    </button>

                    <button type="button" id="bulk-read-btn" data-bulk-read-url="{{ route('notifications.bulkMarkAsRead') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-success-200 bg-success-50 px-5 text-sm font-semibold leading-none text-success-600 shadow-sm transition duration-200 hover:border-success-500 hover:bg-success-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-success-200 disabled:cursor-not-allowed disabled:border-bgray-200 disabled:bg-bgray-100 disabled:text-bgray-400 disabled:shadow-none dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-400 dark:hover:border-success-500 dark:hover:bg-success-500 dark:hover:text-white dark:disabled:border-darkblack-400 dark:disabled:bg-darkblack-500 dark:disabled:text-bgray-700"
                        disabled>
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Bulk Mark as Read</span>
                    </button>

                    <button type="button" id="clear-all-btn" data-clear-all-url="{{ route('notifications.clearAll') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-red-200 bg-white px-5 text-sm font-semibold leading-none text-red-600 shadow-sm transition duration-200 hover:border-red-500 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200 dark:border-red-900/40 dark:bg-darkblack-600 dark:text-red-400 dark:hover:bg-darkblack-500">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Clear All</span>
                    </button>
                </div>
            </div>

            @php
                $tabs = [
                    'unread' => 'Unread',
                    'read' => 'Read',
                ];
            @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('notifications.index', array_merge(request()->except(['page', 'read_status']), ['read_status' => $status])) }}"
                       class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full border-separate border-spacing-0">
                                <thead>
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 bg-bgray-50/80 dark:bg-darkblack-500">
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-success-500 focus:ring-success-500">
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Notification Title</span>
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Message</span>
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project</span>
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">From User</span>
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Recipient</span>
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <x-sorting.sortable-column column="created_at" label="Created At" />
                                        </th>
                                        <th class="border-b border-bgray-200 px-6 py-4 text-left dark:border-b-darkblack-400">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Read Status</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-darkblack-600">
                                    @forelse ($notifications as $notification)
                                        <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <input type="checkbox" class="notification-checkbox rounded border-gray-300 text-success-500 focus:ring-success-500" value="{{ $notification->id }}">
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $notification->data['title'] ?? 'Notification' }}
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ $notification->data['message'] ?? '--' }}
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ $notification->project?->name ?? '--' }}
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                    {{ $notification->user?->name ?? 'System' }}
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ auth()->user()->name }}
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    @appDateTime($notification->created_at)
                                                </span>
                                            </td>
                                            <td class="border-b border-bgray-100 px-6 py-5 dark:border-darkblack-400">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $notification->read_at ? 'bg-success-50 text-success-400' : 'bg-warning-50 text-warning-500' }}">
                                                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-table-no-data :col-span="8" message="No notifications found." />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <x-pagination :paginator="$notifications" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
    </main>

    <x-filters.drawer>
        <input type="hidden" name="read_status" value="{{ $selectedStatus }}">
        <x-filters.select name="project_id" label="Project" :options="$projects" />
        <x-filters.select name="user_id" label="User" :options="$users" />
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/list-notifications.js')
@endpush
