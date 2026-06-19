@extends('layouts.master')

@section('page-content')
    <div class="mb-6 flex items-center">
        <x-filters.button />
    </div>

    <section>
        <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                        <tr>
                            @foreach (['User', 'Login At', 'Last Activity At', 'Browser', 'Platform', 'IP Address', 'Country', 'City'] as $column)
                                <th class="whitespace-nowrap border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50">{{ $column }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-darkblack-600">
                        @forelse ($activities as $activity)
                            <tr class="{{ config('assets.classes.table_row_hover') }}">
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 dark:border-darkblack-500">
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar :user="$activity->user" size="sm" :name="$activity->user?->name ?? 'Unknown User'" />
                                        <div>
                                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $activity->user?->name ?? '-' }}</p>
                                            <p class="text-xs text-bgray-500 dark:text-bgray-300">{{ $activity->user?->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">@appDateTime($activity->login_at)</td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->last_activity_at ? \App\Providers\AppServiceProvider::formatAppDateTime($activity->last_activity_at) : '-' }}</td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->browser ?? '-' }}</td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->platform ?? '-' }}</td>
                                {{-- <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->device ?? '-' }}</td> --}}
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 font-mono text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->ip_address ?? '-' }}</td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->country ?? '-' }}</td>
                                <td class="whitespace-nowrap border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-500 dark:text-bgray-300">{{ $activity->city ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-table-no-data col-span="9" message="No login activity found." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-5">
                <x-pagination :paginator="$activities" :per-page="$perPage" />
            </div>
        </div>
    </section>

    <x-filters.drawer>
        <x-filters.date-range label="Login Date" start-name="date_from" end-name="date_to" />
        <x-filters.multi-select name="user_id" label="Users" :options="$users" />
    </x-filters.drawer>
@endsection
