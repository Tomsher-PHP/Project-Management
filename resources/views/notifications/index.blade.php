@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-notifications-page>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                <button type="button" id="bulk-read-btn" data-bulk-read-url="{{ route('notifications.bulkMarkAsRead') }}" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                    Bulk Mark as Read
                </button>

                <button type="button" id="bulk-delete-btn" data-bulk-delete-url="{{ route('notifications.bulkDelete') }}" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                    Bulk Delete
                </button>

                <button type="button" id="clear-all-btn" data-clear-all-url="{{ route('notifications.clearAll') }}" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50">
                    Clear All
                </button>

                <span id="selected-count" class="hidden">0 selected</span>
            </div>

            @php
                $tabs = [
                    'unread' => 'Unread',
                    'read' => 'Read',
                ];
            @endphp
            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('notifications.index', array_merge(request()->except(['page', 'read_status']), ['read_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Notification Title</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Message</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">From User</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Recipient</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="created_at" label="Created At" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Read Status</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($notifications as $notification)
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <input type="checkbox" class="notification-checkbox h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" value="{{ $notification->id }}">
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-base font-semibold text-bgray-900 dark:text-white">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                            {{ $notification->data['message'] ?? '--' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                            {{ $notification->project?->name ?? '--' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                            {{ $notification->user?->name ?? 'System' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                            {{ auth()->user()->name }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                            @appDateTime($notification->created_at)
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $notification->read_at ? 'bg-success-50 text-success-300' : 'bg-warning-50 text-warning-300' }}">
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
            </div>

            <x-pagination :paginator="$notifications" :per-page="$perPage" />
        </section>
    </main>

    <x-filters.drawer>
        <input type="hidden" name="read_status" value="{{ $selectedStatus }}">
        <x-filters.date-range label="Date Range" startName="from_date" endName="to_date" />
        <x-filters.multi-select name="project_id" label="Projects" :options="$projects" />
        <x-filters.multi-select name="user_id" label="Users" :options="$users" />
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/list-notifications.js')
@endpush
