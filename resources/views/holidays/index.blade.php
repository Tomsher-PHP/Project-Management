@extends('layouts.master')

@section('page-content')

    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        @can('holidays.create')
            <x-button.create-button :href="route('holidays.create')" label="Holiday" />
        @endcan

        <x-filters.button />
        <x-filters.list-search />

        @php
            session(['holidays_return_url' => url()->full()]);
        @endphp
    </div>

    <!-- Holiday list -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            <!-- List table -->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

                <div class="flex flex-col space-y-5">

                    <div class="table-content w-full overflow-x-auto">

                        <table class="w-full">

                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                                {{-- # --}}
                                <td class="">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        #
                                    </span>
                                </td>

                                {{-- Holiday --}}
                                <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="name" label="Holiday" />
                                    </div>
                                </td>

                                {{-- From Date --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="from_date" label="From Date" />
                                    </div>
                                </td>

                                {{-- To Date --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <x-sorting.sortable-column column="to_date" label="To Date" />
                                    </div>
                                </td>

                                {{-- Applied To --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Applied To
                                        </span>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Is Active
                                        </span>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                    <div class="flex w-full items-center space-x-2.5">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            Actions
                                        </span>
                                    </div>
                                </td>

                            </tr>

                            @php
                                $startNumber = ($holidays->currentPage() - 1) * $holidays->perPage();
                            @endphp

                            @forelse ($holidays as $holiday)
                                @php
                                    $holidayViewData = [
                                        'id' => $holiday->id,
                                        'name' => $holiday->name,
                                        'from_date' => $holiday->from_date?->format('d M Y'),
                                        'to_date' => $holiday->to_date?->format('d M Y'),
                                        'duration' => $holiday->from_date && $holiday->to_date
                                            ? $holiday->from_date->diffInDays($holiday->to_date) + 1
                                            : null,
                                        'description' => $holiday->description,
                                        'is_public' => (bool) $holiday->is_public,
                                        'is_active' => (bool) $holiday->is_active,
                                        'applied_to' => $holiday->applied_to,
                                        'users' => $holiday->users->map(fn ($user) => [
                                            'id' => $user->id,
                                            'name' => $user->name,
                                        ])->values()->toArray(),
                                        'shifts' => $holiday->shifts->map(fn ($shift) => [
                                            'id' => $shift->id,
                                            'name' => $shift->name,
                                        ])->values()->toArray(),
                                    ];
                                @endphp
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">

                                    {{-- # --}}
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            {{ $startNumber + $loop->iteration }}
                                        </span>
                                    </td>

                                    {{-- Holiday --}}
                                    <td class="px-6 py-5 xl:px-0">

                                        <div class="flex flex-col">

                                            <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                {{ $holiday->name }}
                                            </h4>


                                            @if ($holiday->is_public)
                                                <span class="mt-1 inline-flex w-fit rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600">
                                                    Public Holiday
                                                </span>
                                            @else
                                                <span class="mt-1 inline-flex w-fit rounded-md bg-bgray-50 px-2 py-1 text-xs font-semibold text-bgray-600">
                                                    Private Holiday
                                                </span>
                                            @endif

                                        </div>

                                    </td>

                                    {{-- From Date --}}
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">

                                        <div class="flex w-full items-center">

                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                {{ $holiday->from_date?->format('d M Y') ?? '-' }}
                                            </span>

                                        </div>

                                    </td>

                                    {{-- To Date --}}
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">

                                        <div class="flex w-full items-center">

                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                                {{ $holiday->to_date?->format('d M Y') ?? '-' }}
                                            </span>

                                        </div>

                                    </td>

                                    {{-- Applied To --}}
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">

                                        <div class="flex w-full items-center">

                                            @if ($holiday->applied_to === 'all_users')
                                                <span class="rounded-md bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600">
                                                    All Users
                                                </span>
                                            @elseif ($holiday->applied_to === 'shift')
                                                <div class="flex items-center gap-2">

                                                    <span class="rounded-md bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-600">
                                                        Shift
                                                    </span>

                                                    @if ($holiday->shifts->count() > 0)
                                                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-300">
                                                            {{ $holiday->shifts->count() }}
                                                        </span>
                                                    @endif

                                                </div>
                                            @elseif ($holiday->applied_to === 'user')
                                                <div class="flex items-center gap-2">

                                                    <span class="rounded-md bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-600">
                                                        Users
                                                    </span>

                                                    @if ($holiday->users->count() > 0)
                                                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-300">
                                                            {{ $holiday->users->count() }}
                                                        </span>
                                                    @endif

                                                </div>
                                            @else
                                                <span class="text-sm text-bgray-500">
                                                    -
                                                </span>
                                            @endif

                                        </div>

                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">

                                        <div class="flex w-full items-center">

                                            @can('holidays.edit')
                                                <x-status-toggle :model="$holiday" route="holidays.toggleStatus" entity="holiday" permission="holidays.edit" />
                                            @else
                                                @if ($holiday->is_active)
                                                    <span class="rounded-md bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-600 dark:bg-green-900/20 dark:text-green-300">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:bg-gray-900/20 dark:text-gray-300">
                                                        Inactive
                                                    </span>
                                                @endif
                                            @endcan

                                        </div>

                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2">
                                            @can('holidays.view')
                                                <button
                                                    type="button"
                                                    onclick='openHolidayViewModal(@json($holidayViewData))'
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 text-bgray-600 transition hover:bg-bgray-50 hover:text-bgray-900 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white"
                                                    title="View"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                        />
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                        />
                                                    </svg>
                                                </button>
                                            @endcan

                                            @can('holidays.edit')
                                                <x-edit-button :action="route('holidays.edit', $holiday->id)" />
                                            @endcan

                                            @can('holidays.delete')
                                                <x-delete-form :action="route('holidays.destroy', $holiday->id)" />
                                            @endcan

                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <x-table-no-data col-span="7" message="No holidays found." sub-message="There are no holidays to display for the current filters." />
                            @endforelse

                        </table>

                    </div>

                    <x-pagination :paginator="$holidays" :per-page="$perPage" />

                </div>

            </div>

        </section>
    </div>

    <!-- Filter drawer -->
    <x-filters.drawer>

        <x-filters.input-search name="search" label="Holiday Name" />

        <x-filters.select name="status" label="Is Active" :options="[
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]" />

        <x-filters.select name="is_public" label="Public / Private" :options="[
            1 => 'Public',
            0 => 'Private',
        ]" />

        <x-filters.select name="applied_to" label="Applied To" :options="[
            'all_users' => 'All Users',
            'shift' => 'Shift',
            'user' => 'Users',
        ]" />

    </x-filters.drawer>
    <!-- Filter drawer end -->

    {{-- Holiday View Modal --}}
    <div id="holidayViewModal" class="fixed inset-0 z-[9999] hidden overflow-y-auto" aria-labelledby="holidayViewModalTitle" aria-modal="true" role="dialog">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeHolidayViewModal()"></div>

        {{-- Modal --}}
        <div class="relative flex min-h-full items-center justify-center p-4">

            <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-darkblack-600" onclick="event.stopPropagation()">

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">

                    <div>
                        <h3 id="holidayViewModalTitle" class="text-xl font-semibold text-bgray-900 dark:text-white">
                            Holiday Details
                        </h3>

                        <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                            View holiday information
                        </p>
                    </div>

                    <button type="button" onclick="closeHolidayViewModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>

                {{-- Body --}}
                <div class="max-h-[70vh] overflow-y-auto px-6 py-6">

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        {{-- Holiday Name --}}
                        <div class="sm:col-span-2">
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Holiday
                            </p>

                            <p id="holidayViewName" class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">
                                -
                            </p>
                        </div>

                        {{-- From Date --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                From Date
                            </p>

                            <p id="holidayViewFromDate" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                -
                            </p>
                        </div>

                        {{-- To Date --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                To Date
                            </p>

                            <p id="holidayViewToDate" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                -
                            </p>
                        </div>

                        {{-- Duration --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Duration
                            </p>

                            <p id="holidayViewDuration" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                -
                            </p>
                        </div>

                        {{-- Applied To --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Applied To
                            </p>

                            <p id="holidayViewAppliedTo" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                -
                            </p>
                        </div>

                        {{-- Public --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Holiday Type
                            </p>

                            <div id="holidayViewPublic" class="mt-1">
                                -
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Status
                            </p>

                            <div id="holidayViewStatus" class="mt-1">
                                -
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="sm:col-span-2">
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Description
                            </p>

                            <div id="holidayViewDescription" class="mt-2 rounded-lg bg-bgray-50 px-4 py-3 text-sm text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                                -
                            </div>
                        </div>

                        {{-- Users --}}
                        <div id="holidayViewUsersWrapper" class="hidden sm:col-span-2">
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Selected Users
                            </p>

                            <div id="holidayViewUsers" class="mt-2 flex flex-wrap gap-2"></div>
                        </div>

                        {{-- Shifts --}}
                        <div id="holidayViewShiftsWrapper" class="hidden sm:col-span-2">
                            <p class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">
                                Selected Shifts
                            </p>

                            <div id="holidayViewShifts" class="mt-2 flex flex-wrap gap-2"></div>
                        </div>

                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <button type="button" onclick="closeHolidayViewModal()" class="rounded-lg border border-bgray-200 px-4 py-2 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white dark:hover:bg-darkblack-500">
                        Close
                    </button>

                </div>

            </div>

        </div>
    </div>
    <!-- Page ends -->

@endsection

@push('scripts')
<script>
    function openHolidayViewModal(holiday) {
        const modal = document.getElementById('holidayViewModal');

        if (!modal) {
            return;
        }

        document.getElementById('holidayViewName').textContent =
            holiday.name || '-';

        document.getElementById('holidayViewFromDate').textContent =
            holiday.from_date || '-';

        document.getElementById('holidayViewToDate').textContent =
            holiday.to_date || '-';

        document.getElementById('holidayViewDuration').textContent =
            holiday.duration
                ? `${holiday.duration} ${holiday.duration === 1 ? 'Day' : 'Days'}`
                : '-';

        document.getElementById('holidayViewAppliedTo').textContent =
            getHolidayAppliedToLabel(holiday.applied_to);

        document.getElementById('holidayViewDescription').textContent =
            holiday.description || 'No description provided.';

        /*
         * Public / Private
         */
        document.getElementById('holidayViewPublic').innerHTML =
            holiday.is_public
                ? `
                    <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                        Public
                    </span>
                `
                : `
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                        Private
                    </span>
                `;

        /*
         * Active / Inactive
         */
        document.getElementById('holidayViewStatus').innerHTML =
            holiday.is_active
                ? `
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                        Active
                    </span>
                `
                : `
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                        Inactive
                    </span>
                `;

        /*
         * Users
         */
        const usersWrapper =
            document.getElementById('holidayViewUsersWrapper');

        const usersContainer =
            document.getElementById('holidayViewUsers');

        usersContainer.innerHTML = '';

        if (
            holiday.applied_to === 'user' &&
            Array.isArray(holiday.users) &&
            holiday.users.length
        ) {
            usersWrapper.classList.remove('hidden');

            holiday.users.forEach(user => {
                const badge = document.createElement('span');

                badge.className =
                    'rounded-md bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-600';

                badge.textContent = user.name;

                usersContainer.appendChild(badge);
            });
        } else {
            usersWrapper.classList.add('hidden');
        }

        /*
         * Shifts
         */
        const shiftsWrapper =
            document.getElementById('holidayViewShiftsWrapper');

        const shiftsContainer =
            document.getElementById('holidayViewShifts');

        shiftsContainer.innerHTML = '';

        if (
            holiday.applied_to === 'shift' &&
            Array.isArray(holiday.shifts) &&
            holiday.shifts.length
        ) {
            shiftsWrapper.classList.remove('hidden');

            holiday.shifts.forEach(shift => {
                const badge = document.createElement('span');

                badge.className =
                    'rounded-md bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-600';

                badge.textContent = shift.name;

                shiftsContainer.appendChild(badge);
            });
        } else {
            shiftsWrapper.classList.add('hidden');
        }

        modal.classList.remove('hidden');

        document.body.classList.add('overflow-hidden');
    }

    function closeHolidayViewModal() {
        const modal = document.getElementById('holidayViewModal');

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');

        document.body.classList.remove('overflow-hidden');
    }

    function getHolidayAppliedToLabel(appliedTo) {
        switch (appliedTo) {
            case 'all_users':
                return 'All Users';

            case 'shift':
                return 'Shift';

            case 'user':
                return 'Users';

            default:
                return '-';
        }
    }

    /*
     * Close modal with Escape key.
     */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeHolidayViewModal();
        }
    });
</script>
@endpush
