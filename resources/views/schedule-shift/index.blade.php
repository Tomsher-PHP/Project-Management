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

                        {{-- Header: Prev | Calendar Icon | Week Range | Next --}}
                        <div class="flex items-center justify-between mb-4 gap-4">

                            {{-- Previous button --}}
                            <button id="prevWeek" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition text-sm font-medium">
                                Previous
                            </button>

                            {{-- Calendar icon button --}}
                            <div class="relative">
                                <button type="button" id="weekPickerBtn" class="flex items-center justify-center w-10 h-10 border rounded bg-white dark:bg-darkblack-600 hover:bg-gray-50 dark:hover:bg-darkblack-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </button>

                                {{-- Hidden input for Flatpickr --}}
                                <input type="text" class="weekPicker absolute top-0 left-0 opacity-0 w-0 h-0 pointer-events-none" value="{{ $startOfWeek->toDateString() }}">
                            </div>

                            {{-- Week range label --}}
                            <span id="week-date-range" class="text-lg font-semibold text-center dark:text-bgray-50">
                                {{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}
                            </span>

                            {{-- Next button --}}
                            <button id="nextWeek" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition text-sm font-medium">
                                Next
                            </button>

                        </div>

                        <div id="schedule-table">
                            @include('schedule-shift.partials.schedule-table')
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
        let currentWeek = "{{ $startOfWeek->toDateString() }}";

        document.addEventListener("click", function(e) {

            const btn = e.target.closest(".edit-shift");
            if (!btn) return;

            const td = btn.closest("td");

            const view = td.querySelector(".shift-view");
            const edit = td.querySelector(".shift-edit");

            if (!view || !edit) return;

            view.classList.toggle("hidden");
            edit.classList.toggle("hidden");

        });

        document.querySelectorAll(".shift-select").forEach(select => {

            select.addEventListener("change", function() {

                const userId = this.dataset.user;
                const date = this.dataset.date;
                const shiftId = this.value;

                fetch("/shift/update", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            date: date,
                            shift_id: shiftId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        location.reload();
                    });

            });

        });

        function loadWeek(date) {
            fetch(`/schedule-shift?week=${date}`, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(res => res.json()) // <-- parse as JSON
                .then(data => {
                    // Replace table
                    document.querySelector("#schedule-table").innerHTML = data.html;

                    // Update week range label
                    document.getElementById("week-date-range").innerText = data.weekRange;

                    // Update currentWeek for next/prev calculations
                    currentWeek = date;
                });
        }

        document.getElementById("nextWeek").addEventListener("click", function() {
            let next = new Date(currentWeek);
            next.setDate(next.getDate() + 7);

            loadWeek(next.toISOString().split('T')[0]);
        });

        document.getElementById("prevWeek").addEventListener("click", function() {
            let prev = new Date(currentWeek);
            prev.setDate(prev.getDate() - 7);

            loadWeek(prev.toISOString().split('T')[0]);
        });

        document.getElementById("weekPickerBtn").addEventListener("click", function() {
            document.querySelector(".weekPicker")._flatpickr.open();
        });
    </script>
@endpush
