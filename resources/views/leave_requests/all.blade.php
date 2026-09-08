@extends('layouts.master')

@section('page-content')

<div class="w-full">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">


        @can('leave_requests.create')
            <a href="{{ route('leave-requests.create') }}"
               class="inline-flex items-center gap-1 rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>

                Apply Leave
            </a>
        @endcan
    </div>


    {{-- Success / Error --}}
    @if(session('success'))
        <div class="mb-5 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif


    {{-- Table --}}
    <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead>
                    <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            Employee
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            Leave Type
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            From Date
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            To Date
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            Days
                        </th>
                        <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            Status
                        </th>
                        <th class="px-5 py-4 text-right text-sm font-semibold text-bgray-600 dark:text-bgray-200">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($leaveRequests as $leaveRequest)
                        <tr class="border-b border-bgray-100 dark:border-darkblack-400">
                            {{-- Employee --}}
                            <td class="px-5 py-4">
                                <div class="font-medium text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->user->name ?? '-' }}
                                </div>
                            </td>

                            {{-- Leave Type --}}
                            <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $leaveRequest->leaveType->name ?? '-' }}
                            </td>

                            {{-- From Date --}}
                            <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">
                                    {{ \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y') }}
                            </td>

                            {{-- To Date --}}
                            <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y') }}
                            </td>

                            {{-- Days --}}
                            <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ number_format($leaveRequest->duration, 2) }}
                            </td>

                            {{-- Status --}}
                            <td class="px-5 py-4">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'approved' => 'bg-green-100 text-green-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses[$leaveRequest->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($leaveRequest->status) }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td class="px-6 py-5 xl:w-[220px] xl:px-0">
                                <div class="flex w-full items-center space-x-2">

                                    {{-- View --}}
                                    @can('leaveRequest.view')
                                        <x-view-button
                                            :action="route('leave-requests.show', $leaveRequest->id)"
                                        />
                                    @endcan


                                    {{-- Edit --}}
                                    @can('leaveRequest.edit')
                                        @if($leaveRequest->status === 'pending')

                                            <x-edit-button
                                                :action="route('leave-requests.edit', $leaveRequest->id)"
                                            />

                                        @endif
                                    @endcan

                                    {{-- Cancel Request --}}
                                    @can('leaveRequest.cancel')
                                        @if(
                                            $leaveRequest->user_id === auth()->id()
                                            && in_array($leaveRequest->status, ['pending', 'approved'])
                                        )

                                            <button
                                                type="button"
                                                onclick="openCancelLeaveModal({{ $leaveRequest->id }})"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-orange-200 bg-orange-50 text-orange-600 transition hover:bg-orange-100"
                                                title="Cancel Request">

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor">

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />

                                                </svg>

                                            </button>

                                        @endif
                                    @endcan

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                class="px-5 py-10 text-center text-sm text-bgray-500">
                                No leave requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leaveRequests->hasPages())
            <div class="border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
