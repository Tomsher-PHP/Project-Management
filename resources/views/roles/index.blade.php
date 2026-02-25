@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        @canType('role.create')
        <a href="{{ route('roles.create') }}" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>

            <span>New Role</span>
        </a>
        @endcanType

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Role name
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User Type</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td>
                                </tr>
                                @forelse ($roles as $key => $role)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $key + 1 }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $role->name }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500">{{ config('constants.user_types')[$role->user_type] ?? 'Unknown' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">

                                                <button type="button" data-id="{{ $role->id }}" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                                    {{ $role->status ? 'bg-green-600 active' : 'bg-gray-200' }}" role="switch" aria-checked="{{ $role->status ? 'true' : 'false' }}" @unlesscanType('role.edit') disabled @endcanType>

                                                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                                        {{ $role->status ? 'translate-x-5' : 'translate-x-0' }}">
                                                    </span>

                                                </button>

                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @canType('role.edit')

                                                <a href="{{ route('roles.edit', $role->id) }}" class="inline-flex items-center justify-center w-8 h-8
                                                    rounded-lg bg-gray-100 dark:bg-darkblack-500
                                                    hover:bg-gray-200 dark:hover:bg-darkblack-400
                                                    transition duration-200 group">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                    </svg>
                                                </a>

                                                @endcanType
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-sm text-gray-500 dark:text-gray-200">
                                            No roles found.
                                        </td>
                                    </tr>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $(document).on('click', '.switch-btn', function() {

                let btn = $(this);

                // Prevent multiple clicks while processing
                if (btn.data('processing')) return;
                btn.data('processing', true);

                let roleId = btn.data('id');
                let isActive = btn.attr('aria-checked') === 'true';
                let actionText = isActive ? 'deactivate' : 'activate';

                if (!confirm(`Are you sure you want to ${actionText} this role?`)) {
                    btn.data('processing', false);
                    return;
                }

                $.ajax({
                    url: '/roles/toggle-status',
                    type: 'PATCH',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        roleId: roleId
                    },
                    success: function(response) {

                        if (response.success) {

                            let newStatus = response.status == 1;

                            // Update switch UI
                            btn.attr('aria-checked', newStatus);

                            btn.toggleClass('bg-green-600', newStatus);
                            btn.toggleClass('bg-gray-200', !newStatus);

                            btn.find('span').toggleClass('translate-x-5', newStatus);
                            btn.find('span').toggleClass('translate-x-0', !newStatus);

                            // Update badge
                            let badge = btn.closest('tr').find('.status-badge');

                            if (newStatus) {
                                badge.removeClass('bg-secondary')
                                    .addClass('bg-success')
                                    .text('Active');
                            } else {
                                badge.removeClass('bg-success')
                                    .addClass('bg-secondary')
                                    .text('Inactive');
                            }

                        } else {
                            alert('Status update failed.');
                        }

                    },
                    error: function() {
                        alert('Something went wrong.');
                    },
                    complete: function() {
                        btn.data('processing', false);
                    }
                });

            });

        });
    </script>
@endpush
