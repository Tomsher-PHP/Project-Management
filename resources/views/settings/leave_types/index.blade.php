@extends('layouts.master')

@section('page-content')
    {{-- Page Header --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">

        <x-back-button :url="route('settings.index')" label="Back" />

        @can('leave_types.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Leave Type" data-url="{{ route('settings.leave-types.store') }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}" label="Leave Type" />
        @endcan

        <x-filters.button />

    </div>


    {{-- Main Content --}}
    <div class="2xl:flex 2xl:space-x-[48px]">

        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            {{-- List Table --}}
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

                <div class="flex flex-col space-y-5">

                    <div class="table-content w-full overflow-x-auto">

                        <table class="w-full min-w-[1000px]">

                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                                    {{-- # --}}
                                    <th class="px-6 py-5 text-left text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        #
                                    </th>

                                    {{-- Leave Type --}}
                                    <th class="px-6 py-5 text-left text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Leave Type" />
                                        </div>
                                    </th>

                                    {{-- Code --}}
                                    <th class="px-6 py-5 text-left text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Code
                                    </th>

                                    {{-- Color --}}
                                    <th class="px-6 py-5 text-center text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Color
                                    </th>

                                    {{-- File Required --}}
                                    <th class="px-6 py-5 text-center text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        File Required
                                    </th>

                                    {{-- Paid --}}
                                    <th class="px-6 py-5 text-center text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Paid
                                    </th>

                                    {{-- Status --}}
                                    <th class="px-6 py-5 text-center text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Status
                                    </th>

                                    {{-- Actions --}}
                                    <th class="px-6 py-5 text-center text-base font-medium text-bgray-600 dark:text-bgray-50">
                                        Actions
                                    </th>

                                </tr>
                            </thead>


                            <tbody>

                                @php
                                    $startNumber = ($leaveTypes->currentPage() - 1) * $leaveTypes->perPage();
                                @endphp

                                @forelse ($leaveTypes as $key => $leaveType)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">

                                        {{-- # --}}
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                {{ $startNumber + $loop->iteration }}
                                            </span>
                                        </td>


                                        {{-- Leave Type --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center space-x-2.5">

                                                <div class="flex flex-wrap items-center gap-2">

                                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $leaveType->name }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- Code --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center">

                                                <span class="block rounded-md bg-bgray-100 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                                                    {{ strtoupper($leaveType->code) }}
                                                </span>

                                            </div>

                                        </td>


                                        {{-- Color --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center justify-center">

                                                @if ($leaveType->color)
                                                    <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">

                                                        <span class="h-4 w-4 rounded-full border border-black/10 shadow-sm" style="background-color: {{ $leaveType->color }};"></span>

                                                        <span>
                                                            {{ strtoupper($leaveType->color) }}
                                                        </span>

                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-500 dark:bg-gray-900/30 dark:text-gray-400">
                                                        Not Set
                                                    </span>
                                                @endif

                                            </div>

                                        </td>


                                        {{-- File Required --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center justify-center">

                                                @if ($leaveType->is_file_upload_required)
                                                    <span class="inline-flex rounded-full bg-error-300 px-3 py-1.5 text-xs font-medium text-white">
                                                        Required
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-gray-900/30 dark:text-gray-300">
                                                        Not Required
                                                    </span>
                                                @endif

                                            </div>

                                        </td>


                                        {{-- Paid --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center justify-center">

                                                @if ($leaveType->is_paid)
                                                    <span class="inline-flex rounded-full bg-success-50 px-3 py-1.5 text-xs font-medium text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">
                                                        Paid
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-error-300 px-3 py-1.5 text-xs font-semibold text-white">
                                                        Unpaid
                                                    </span>
                                                @endif

                                            </div>

                                        </td>


                                        {{-- Status --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center justify-center">

                                                <x-status-toggle :model="$leaveType" route="settings.leave-type.toggleStatus" entity="leave type" permission="leave_types.edit" />

                                            </div>

                                        </td>


                                        {{-- Actions --}}
                                        <td class="px-6 py-5 xl:px-0">

                                            <div class="flex w-full items-center justify-center space-x-2">

                                                {{-- Edit --}}
                                                @can('leave_types.edit')
                                                    <x-edit-button action="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route('settings.leave-types.update', $leaveType->id) }}" data-name="{{ $leaveType->name }}" data-code="{{ $leaveType->code }}" data-color="{{ $leaveType->color }}" data-description="{{ $leaveType->description }}" data-status="{{ $leaveType->status }}" data-is_file_upload_required="{{ $leaveType->is_file_upload_required }}" data-is_paid="{{ $leaveType->is_paid }}" data-method="PUT" data-module="Leave Type" title="Edit Leave Type" />
                                                @endcan


                                                {{-- Delete --}}
                                                @can('leave_types.delete')
                                                    <x-delete-form :action="route('settings.leave-types.destroy', $leaveType->id)" />
                                                @endcan

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <x-table-no-data :col-span="8" message="No leave types found." />
                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- Pagination --}}
                    <x-pagination :paginator="$leaveTypes" :per-page="$perPage" />

                </div>

            </div>

        </section>

    </div>


    {{-- ============================================================
        LEAVE TYPE CREATE / EDIT MODAL
    ============================================================= --}}

    <x-form-modal modalId="multi-step-modal" module="Leave Type" formId="leaveTypeForm" action="{{ route('settings.leave-types.store') }}" button="Create Leave Type">

        {{-- Leave Type Name --}}
        <div>

            <label for="leave-type-name" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">
                Leave Type Name <x-red-star />
            </label>

            <input type="text" id="leave-type-name" name="name" maxlength="100" autocomplete="off" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>

        </div>


        {{-- Code --}}
        <div>

            <label for="leave-type-code" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">
                Code <x-red-star />
            </label>

            <input type="text" id="leave-type-code" name="code" maxlength="50" autocomplete="off" class="w-full rounded-lg border border-gray-300 p-2 uppercase focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>

            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                Use a unique code such as ANNUAL, SICK or UNPAID.
            </p>

        </div>


        {{-- Calendar Color --}}
        <div>

            <label for="leave-type-color" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">
                Calendar Color <x-red-star />
            </label>

            <div class="flex items-center gap-3">

                <input type="color" id="leave-type-color" name="color" value="#3B82F6" class="h-11 w-16 cursor-pointer rounded-lg border border-bgray-200 bg-white p-1 dark:border-darkblack-400 dark:bg-darkblack-500" required>

                <div>

                    <p id="leave-type-color-value" class="text-sm font-medium text-bgray-700 dark:text-bgray-200">
                        #3B82F6
                    </p>

                    <p class="mt-0.5 text-xs text-bgray-500 dark:text-bgray-400">
                        Used to identify this leave on the attendance calendar.
                    </p>

                </div>

            </div>

        </div>


        {{-- Description --}}
        <div>

            <label for="leave-type-description" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">
                Description
            </label>

            <textarea id="leave-type-description" name="description" rows="4" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Enter a description for this leave type..."></textarea>

        </div>


        {{-- File Upload Required --}}
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="flex items-start gap-3">

                <input type="checkbox" id="leave-type-file-required" name="is_file_upload_required" value="1" class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300">

                <div>
                    <label for="leave-type-file-required" class="text-sm font-medium text-bgray-800 dark:text-white">
                        File Upload Required
                    </label>

                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                        Require employees to upload a document when applying for this leave type.
                    </p>
                </div>

            </div>
        </div>


        {{-- Paid Leave --}}
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="flex items-start gap-3">

                <input type="checkbox" id="leave-type-paid" name="is_paid" value="1" class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300">

                <div>
                    <label for="leave-type-paid" class="text-sm font-medium text-bgray-800 dark:text-white">
                        Paid Leave
                    </label>

                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                        Mark this leave type as paid leave.
                    </p>
                </div>

            </div>
        </div>


        {{-- Status --}}
        <div>

            <label for="leave-type-status" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">
                Status
            </label>

            <select id="leave-type-status" name="status" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <option value="1" selected>
                    Active
                </option>

                <option value="0">
                    Inactive
                </option>
            </select>

        </div>


    </x-form-modal>


    {{-- ============================================================
        FILTER DRAWER
    ============================================================= --}}

    <x-filters.drawer>

        <x-filters.input-search name="search" label="Leave Type" />

        <x-filters.select name="is_file_upload_required" label="File Required" :options="[
            1 => 'Required',
            0 => 'Not Required',
        ]" />

        <x-filters.select name="is_paid" label="Paid" :options="[
            1 => 'Paid',
            0 => 'Unpaid',
        ]" />

        <x-filters.select name="status" label="Status" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />

    </x-filters.drawer>


    {{-- ============================================================
        LEAVE TYPE MODAL SCRIPT
    ============================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const codeInput = document.getElementById('leave-type-code');
            const colorInput = document.getElementById('leave-type-color');
            const colorValue = document.getElementById('leave-type-color-value');


            /* ==========================================
               CODE - FORCE UPPERCASE
            ========================================== */

            if (codeInput) {

                codeInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });

            }


            /* ==========================================
               COLOR - DISPLAY HEX VALUE
            ========================================== */

            if (colorInput && colorValue) {

                const updateColorValue = function() {
                    colorValue.textContent = this.value.toUpperCase();
                };

                colorInput.addEventListener('input', updateColorValue);
                colorInput.addEventListener('change', updateColorValue);

            }


            /* ==========================================
               EDIT MODAL SUPPORT
            ========================================== */

            document.addEventListener('click', function(event) {

                const editButton = event.target.closest('.edit-record');

                if (!editButton) {
                    return;
                }

                const nameInput = document.getElementById('leave-type-name');
                const codeInput = document.getElementById('leave-type-code');
                const colorInput = document.getElementById('leave-type-color');
                const colorValue = document.getElementById('leave-type-color-value');
                const descriptionInput = document.getElementById('leave-type-description');
                const statusInput = document.getElementById('leave-type-status');
                const fileRequiredInput = document.getElementById('leave-type-file-required');
                const paidInput = document.getElementById('leave-type-paid');

                if (nameInput) {
                    nameInput.value = editButton.dataset.name || '';
                }

                if (codeInput) {
                    codeInput.value = (editButton.dataset.code || '').toUpperCase();
                }

                if (colorInput) {

                    const color = editButton.dataset.color || '#3B82F6';

                    colorInput.value = color;

                    if (colorValue) {
                        colorValue.textContent = color.toUpperCase();
                    }
                }

                if (descriptionInput) {
                    descriptionInput.value = editButton.dataset.description || '';
                }

                if (statusInput) {
                    statusInput.value = editButton.dataset.status ?? '1';
                }

                if (fileRequiredInput) {
                    fileRequiredInput.checked =
                        String(editButton.dataset.is_file_upload_required) === '1';
                }

                if (paidInput) {
                    paidInput.checked =
                        String(editButton.dataset.is_paid) === '1';
                }

            });

        });
    </script>
@endsection
