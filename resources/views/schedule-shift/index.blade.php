@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        @canType('schedule_shift.create')
        <a href="{{ route('schedule.shift.create') }}" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>

            <span>Schedule Shift</span>
        </a>
        @endcanType

        @php
            $previousWeek = $nextWeek = 0;
        @endphp
        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">

                        <div class="flex items-center justify-between mb-4">
                            <a href="?week={{ $previousWeek }}" class="px-3 py-1 bg-gray-200 rounded">
                                Previous
                            </a>

                            <h2 class="text-lg font-semibold dark:text-bgray-50">
                                {{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}
                            </h2>

                            <a href="?week={{ $nextWeek }}" class="px-3 py-1 bg-gray-200 rounded">
                                Next
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Users</th>

                                        @foreach ($weekDates as $date)
                                            <th class="px-4 py-2 text-center">
                                                {{ $date->format('D') }} <br>
                                                {{ $date->format('d M') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($users as $user)
                                        <tr class="border-t border-gray-200 dark:border-darkblack-400 hover:bg-gray-50 dark:hover:bg-darkblack-500">
                                            <!-- Checkbox column -->
                                            <td class="px-4 py-2 text-center">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" name="selected_users[]" value="{{ $user->id }}" class="user-checkbox h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                                                    <span class="text-sm font-semibold text-gray-600 dark:text-bgray-50">
                                                        {{ $user->name }}
                                                    </span>
                                                </label>
                                            </td>

                                            <!-- Shifts for each day -->
                                            @foreach ($weekDates as $date)
                                                @php
                                                    $shift = $calendar[$user->id][$date->toDateString()] ?? null;
                                                    $isPast = $date->isBefore(\Carbon\Carbon::today());
                                                @endphp

                                                <td class="px-2 py-2 text-center">
                                                    @if ($isPast)
                                                        @if ($shift)
                                                            @php
                                                                $bg = $shift->color_code;
                                                                $text = (hexdec(substr($bg, 1, 2)) * 299 + hexdec(substr($bg, 3, 2)) * 587 + hexdec(substr($bg, 5, 2)) * 114) / 1000 > 125 ? '#000' : '#fff';
                                                            @endphp
                                                            <div class="flex flex-col items-center justify-center rounded px-2 py-1 text-sm font-medium" style="background-color: {{ $bg }}; color: {{ $text }};">
                                                                <span>{{ $shift->shift_name }}</span>
                                                                <span>{{ \Carbon\Carbon::parse($shift->time_from)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->time_to)->format('H:i') }}</span>
                                                            </div>
                                                        @else
                                                            <span class="text-sm text-gray-400">--</span>
                                                        @endif
                                                    @else
                                                        <select class="shift-select w-full border rounded bg-white dark:bg-darkblack-600 border-gray-300 dark:border-darkblack-400 text-gray-700 dark:text-gray-200" data-user="{{ $user->id }}" data-date="{{ $date->toDateString() }}">
                                                            <option value="">--</option>
                                                            @foreach ($shifts as $shiftOption)
                                                                <option value="{{ $shiftOption->id }}" {{ $shift?->shift_id == $shiftOption->id ? 'selected' : '' }}>
                                                                    {{ $shiftOption->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </td>
                                            @endforeach

                                        </tr>
                                    @endforeach
                                </tbody>

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
        $(document).on('change', '.shift-select', function() {

            let user = $(this).data('user');
            let date = $(this).data('date');
            let shift = $(this).val();

            $.post('/shift-assignments', {
                user_id: user,
                date: date,
                shift_id: shift,
                _token: $('meta[name="csrf-token"]').attr('content')
            });

        });
    </script>
@endpush
