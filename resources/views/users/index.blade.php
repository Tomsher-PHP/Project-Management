@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            @can('user.create')
                <x-button.create-button :href="route('users.create')" label="User" />
            @endcan

            <x-filters.button />
            <x-filters.list-search />
        </div>

        <div>
        @can('user.restore')
            <a href="{{ route('users.restore.index') }}" class="inline-flex items-center gap-2 rounded-md border border-success-300 px-4 py-1.5 text-sm font-semibold text-success-400 transition duration-200 hover:bg-success-300 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 8l5 4-5 4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12h-8" />
                </svg>
                <span>Restore Users</span>
            </a>
        @endcan
        @can('user_leave_balance.import')
            <a
                href="{{ route('user-leave-balances.import') }}"
                class="inline-flex items-center gap-2 rounded-md border border-success-300 px-4 py-1.5 text-sm font-semibold text-success-400 transition duration-200 hover:bg-success-300 hover:text-white"
            >
            <svg xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M14 3v6h6"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 17V11"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9.5 13.5L12 11l2.5 2.5"
            />
        </svg>
                <span>Import Leave Balances</span>
            </a>
        @endcan
        </div>
    </div>
    @php
        session(['users_return_url' => url()->full()]);
    @endphp

    <!-- write your code here-->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!--list table-->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <td class="py-4 pl-4 pr-3 whitespace-nowrap">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                </td>
                                <td class="px-4 py-4 min-w-[260px] lg:min-w-[280px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="name" label="Name" />
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap min-w-[140px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Department</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap min-w-[140px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap min-w-[150px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap min-w-[165px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                    </div>
                                </td>
                                <td class="py-4 pl-4 pr-4 whitespace-nowrap min-w-[120px]">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </div>
                                </td>
                            </tr>
                            @php
                                $startNumber = ($users->currentPage() - 1) * $users->perPage();
                            @endphp
                            @forelse ($users as $key => $user)
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                    <td class="py-4 pl-4 pr-3 whitespace-nowrap">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                    </td>
                                    <td class="px-4 py-4 min-w-[260px] lg:min-w-[280px]">
                                        <div class="flex items-center gap-4">
                                            <x-user-avatar :user="$user" class="h-12 w-12 shrink-0 text-lg sm:h-[64px] sm:w-[64px] sm:text-xl" />
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-base font-bold text-bgray-900 dark:text-white sm:text-lg">
                                                    <a href="{{ route('users.show', $user->id) }}" class="transition hover:text-success-400">
                                                        {{ $user->name }}
                                                    </a>
                                                </h4>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50 sm:text-base">Role:
                                                        {{ $user->role_name }}</span>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-bgray-50 sm:text-sm">
                                                        <span class="shrink-0">Email:</span>
                                                        @if (filled($user->email))
                                                            <a href="mailto:{{ $user->email }}" class="break-all transition hover:text-success-400">{{ $user->email }}</a>
                                                            <button type="button" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" onclick="copyProfileEmail(event, @js($user->email))" aria-label="Copy user email" title="Copy email">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                    <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </button>
                                                        @else
                                                            <span>--</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md bg-bgray-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $user->details->department->name ?? '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md bg-bgray-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $user->details?->designation?->name ?? '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <span class="block rounded-md bg-bgray-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">{{ $user->details?->phone ?? '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center">
                                            <x-status-toggle :model="$user" route="users.toggleStatus" entity="user" permission="user.edit" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('user.view')
                                                <x-view-button :action="route('users.show', $user->id)" />
                                            @endcan
                                            @can('user.edit')
                                                <x-edit-button :action="route('users.edit', $user->id)" />
                                            @endcan
                                            @if (auth()->id() != $user->id)
                                                @can('user.delete')
                                                    <x-delete-form :action="route('users.destroy', $user->id)" />
                                                @endcan
                                            @endif
                                            @can('user.leave_details.view')
                                                <a href="{{ route('users.leave-details', $user->id) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600 transition hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300"
                                                title="Leave Details">

                                                    <svg class="h-4 w-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        viewBox="0 0 24 24">

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z"/>

                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M8 13H10M14 13H16M8 17H10M14 17H16"/>

                                                    </svg>

                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="7" message="No users found." />
                            @endforelse
                        </table>
                    </div>
                    <x-pagination :paginator="$users" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
    @if (session('initial_shift_user_id'))
        @php
            $initialShiftUser = \App\Models\User::find(session('initial_shift_user_id'));
            $onboardingShifts = \App\Models\Shift::active()->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();
        @endphp
        @if ($initialShiftUser)
            @include('users.partials.initial-shift-modal', ['user' => $initialShiftUser, 'shifts' => $onboardingShifts])
        @endif
    @endif

    @if (request('leave_assignment_user_id'))
        @php
            $leaveAssignmentUser = \App\Models\User::find(
                request('leave_assignment_user_id')
            );

            $leaveTypes = \App\Models\LeaveType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        @endphp

        @if($leaveAssignmentUser)
            @include('users.partials.leave-assignment-modal', [
                'user' => $leaveAssignmentUser,
                'leaveTypes' => $leaveTypes,
                'balances' => collect(),
                'previousBalances' => collect(),
                'mode' => 'create',
                'returnTo' => 'users.index',
            ])
        @endif
    @endif
    <!-- write your code here-->
    <!-- Page ends -->

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Name" />
        <x-filters.input name="email" label="Email" />
        <x-filters.multi-select name="role_id" label="Role" :options="$roles" />
        <x-filters.multi-select name="department_id" label="Departments" :options="$departments" />
        <x-filters.multi-select name="designation_id" label="Designations" :options="$designations" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection
