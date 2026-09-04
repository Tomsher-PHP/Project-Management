@extends('layouts.master')
@section('page-content')
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-bgray-900 dark:text-white">
                    {{ $pageTitle }}
                </h2>

                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                    {{ $subTitle }}
                </p>
            </div>

            @can('leave_types.create')
                <a href="{{ route('settings.leave-types.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-5 py-3 text-sm font-medium text-white transition hover:bg-success-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Add Leave Type
                </a>
            @endcan
        </div>


        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif


        @if (session('error'))
            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif


        {{-- Leave Types Table --}}
        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                            {{-- # --}}
                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                #
                            </th>
                            {{-- Leave Type --}}
                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Leave Type
                            </th>
                            {{-- Code --}}
                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Code
                            </th>
                            {{-- Color --}}
                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Color
                            </th>
                            {{-- File Required --}}
                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                File Required
                            </th>
                            {{-- Paid --}}
                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Paid
                            </th>
                            {{-- Status --}}
                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Status
                            </th>
                            {{-- Action --}}
                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($leaveTypes as $index => $leaveType)
                            <tr
                                class="border-b border-bgray-100 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">
                                {{-- # --}}
                                <td class="px-6 py-4 text-sm text-bgray-600 dark:text-bgray-300">
                                    {{ $leaveTypes->firstItem() + $index }}
                                </td>

                                {{-- Leave Type --}}
                                <td class="px-6 py-4">
                                    <div class="font-medium text-bgray-900 dark:text-white">
                                        {{ $leaveType->name }}
                                    </div>

                                    @if ($leaveType->description)
                                        <div class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                            {{ Str::limit($leaveType->description, 80) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Code --}}
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-400 dark:text-bgray-200">
                                        {{ $leaveType->code }}
                                    </span>
                                </td>

                                {{-- Color --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($leaveType->color)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                                            {{-- Color Preview --}}
                                            <span class="h-4 w-4 rounded-full border border-black/10 shadow-sm"
                                                style="background-color: {{ $leaveType->color }};"></span>

                                            {{-- Hex Value --}}
                                            <span>
                                                {{ strtoupper($leaveType->color) }}
                                            </span>
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-900/30 dark:text-gray-400">
                                            Not Set
                                        </span>
                                    @endif
                                </td>

                                {{-- File Required --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($leaveType->is_file_upload_required)
                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs font-medium text-white transition hover:bg-error-400">
                                            Required
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-900/30 dark:text-gray-300">
                                            Not Required
                                        </span>
                                    @endif
                                </td>

                                {{-- Paid --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($leaveType->is_paid)
                                        <span
                                            class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                            Paid
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs font-semibold text-white transition hover:bg-error-400">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($leaveType->status)
                                        <span
                                            class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs font-semibold text-white transition hover:bg-error-400">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center justify-center space-x-2">
                                        @can('leave_types.edit')
                                            <x-edit-button :action="route('settings.leave-types.edit', $leaveType->id)" />
                                        @endcan

                                        @can('leave_types.delete')
                                            <x-delete-form :action="route('settings.leave-types.destroy', $leaveType->id)" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-bgray-100 dark:bg-darkblack-400">
                                            <svg class="h-7 w-7 text-bgray-400" fill="none" stroke="currentColor"
                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                            </svg>
                                        </div>

                                        <p class="mt-3 text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                            No leave types found.
                                        </p>

                                        @can('leave_types.create')
                                            <a href="{{ route('settings.leave-types.create') }}"
                                                class="mt-3 text-sm font-medium text-success-300 hover:underline">
                                                Add your first leave type
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($leaveTypes->hasPages())
                <div class="border-t border-bgray-100 px-6 py-4 dark:border-darkblack-400">
                    {{ $leaveTypes->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
