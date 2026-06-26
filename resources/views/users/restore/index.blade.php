@extends('layouts.master')

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <x-back-button :url="session('users_return_url', route('users.index'))" />

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-user-restore-bulk-button disabled>
                Bulk Restore
            </button>

            <form method="POST" action="{{ route('users.restore.bulk') }}" class="hidden" data-user-restore-bulk-form>
                @csrf
                <div data-user-restore-bulk-hidden-inputs></div>
            </form>
        </div>
    </div>

    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <td class="px-6 py-5 xl:px-0">
                                    <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-user-restore-select-all>
                                </td>
                                <td>
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                </td>
                                <td class="px-6 py-5 xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Name</span>
                                </td>
                                <td class="px-6 py-5 xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</span>
                                </td>
                                <td class="px-6 py-5 xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </td>
                            </tr>
                            @php
                                $startNumber = ($users->currentPage() - 1) * $users->perPage();
                            @endphp
                            @forelse ($users as $user)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="px-6 py-5 xl:px-0">
                                        <input type="checkbox" value="{{ $user->id }}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-user-restore-checkbox>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-semibold text-bgray-900 dark:text-white">{{ $user->name }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base text-bgray-600 dark:text-bgray-50">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <form action="{{ route('users.restore', $user->id) }}" method="POST" data-user-restore-form>
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400">
                                                Restore
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="6" message="No deleted users found." />
                            @endforelse
                        </table>
                    </div>

                    <x-pagination :paginator="$users" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/users/user-restore.js')
@endpush
