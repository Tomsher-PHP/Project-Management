@extends('layouts.master')

@section('page-content')
    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Request Leave Change
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Submit a request to change your approved leave.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('leave-change-requests.store', $leaveRequest) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Current Leave --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <h2 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                    Current Approved Leave
                </h2>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            Employee
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveRequest->user?->name ?? 'Employee' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            Leave Type
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveRequest->leaveType?->name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            From
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveRequest->approved_from_date?->format('d M Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            To
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveRequest->approved_to_date?->format('d M Y') }}
                        </p>
                    </div>

                </div>

                <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Current duration:
                        <strong>
                            {{ $leaveRequest->duration }}
                            {{ $leaveRequest->duration == 1 ? 'day' : 'days' }}
                        </strong>
                    </p>
                </div>

            </div>

            {{-- Requested Change --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <h2 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                    Requested Change
                </h2>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Leave Type
                        </label>

                        <select name="new_leave_type_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Leave Type</option>

                            @foreach ($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected(old('new_leave_type_id', $leaveRequest->leave_type_id) == $leaveType->id)>
                                    {{ $leaveType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Day Type
                        </label>

                        <select name="new_day_type" id="new_day_type" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="full_day" @selected(old('new_day_type', $leaveRequest->day_type) === 'full_day')>
                                Full Day
                            </option>

                            <option value="half_day" @selected(old('new_day_type', $leaveRequest->day_type) === 'half_day')>
                                Half Day
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            From Date
                        </label>

                        <input type="date" name="new_from_date" value="{{ old('new_from_date', optional($leaveRequest->approved_from_date)->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            To Date
                        </label>

                        <input type="date" name="new_to_date" value="{{ old('new_to_date', optional($leaveRequest->approved_to_date)->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Duration
                        </label>

                        <input type="number" name="new_duration" step="0.5" min="0.5" value="{{ old('new_duration', $leaveRequest->duration) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div id="half-day-type-wrapper">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Half Day Type
                        </label>

                        <select name="new_half_day_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Select</option>

                            <option value="first" @selected(old('new_half_day_type', $leaveRequest->half_day_type) === 'first')>
                                First Half
                            </option>

                            <option value="second" @selected(old('new_half_day_type', $leaveRequest->half_day_type) === 'second')>
                                Second Half
                            </option>
                        </select>
                    </div>

                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Reason for Change
                    </label>

                    <textarea name="reason" rows="5" required maxlength="2000" placeholder="Please explain why you want to change this approved leave..." class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('reason') }}</textarea>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Supporting Document
                    </label>

                    <input type="file" name="attachment" class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-sm file:font-medium dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">

                    <p class="mt-1 text-xs text-gray-500">
                        Upload any supporting document if required.
                    </p>
                </div>

            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">

                <a href="{{ route('leave-requests.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </a>

                <button type="submit" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-success-400">
                    Submit Change Request
                </button>

            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dayType = document.getElementById('new_day_type');
            const halfDayWrapper = document.getElementById('half-day-type-wrapper');

            function toggleHalfDay() {
                if (dayType.value === 'half_day') {
                    halfDayWrapper.classList.remove('hidden');
                } else {
                    halfDayWrapper.classList.add('hidden');
                }
            }

            dayType.addEventListener('change', toggleHalfDay);

            toggleHalfDay();
        });
    </script>
@endsection
