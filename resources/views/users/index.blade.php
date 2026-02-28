@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        @canType('user.create')
        <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>

            <span>New User</span>
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
                                                Name
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
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Department</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Designation</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Phone</span>
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
                                @forelse ($users as $key => $user)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $key + 1 }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-5">
                                                <div class="h-[64px] w-[64px]">
                                                    <img class="h-full w-full rounded-lg object-cover" src="{{ $user->profile_image_url }}" alt="" />
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                        {{ $user->name }}
                                                    </h4>
                                                    <div class="flex flex-col">
                                                        <span class="text-base font-medium text-bgray-700 dark:text-bgray-50">Role: {{ $user->role_name }}</span>
                                                        <span class="text-gray-500 dark:text-bgray-50">Email: {{ $user->email }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ config('constants.user_types')[$user->user_type] ?? 'Unknown' }}</span>
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
                                                <x-status-toggle :model="$user" route="users.toggleStatus" entity="user" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @canType('user.edit')
                                                <a href="{{ route('users.edit', $user->id) }}" class="inline-flex items-center justify-center w-8 h-8
                                                    rounded-lg bg-gray-100 dark:bg-darkblack-500
                                                    hover:bg-gray-200 dark:hover:bg-darkblack-400
                                                    transition duration-200 group">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                    </svg>
                                                </a>
                                                @endcanType
                                                @canType('user.delete')
                                                {{-- <form action="{{ route('user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')"> --}}
                                                <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="delete-form">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="inline-flex items-center justify-center w-8 h-8
                                                        rounded-lg bg-gray-100 dark:bg-darkblack-500
                                                        hover:bg-red-200 dark:hover:bg-darkblack-400
                                                        transition duration-200 group">

                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600 group-hover:text-red-700 transition" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M6 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6-1a1 1 0 00-2 0v6a1 1 0 002 0V7z" clip-rule="evenodd" />
                                                            <path fill-rule="evenodd" d="M4 5a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                        </svg>

                                                    </button>
                                                </form>
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
                        <x-pagination :paginator="$users" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->
@endsection
