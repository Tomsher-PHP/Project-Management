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
                <button type="button"
                    onclick="openLeaveTypeModal('create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-5 py-3 text-sm font-medium text-white transition hover:bg-success-400">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 5v14M5 12h14" />
                    </svg>

                    Add Leave Type
                </button>
            @endcan

        </div>


        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700
                dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">

                {{ session('success') }}

            </div>
        @endif


        @if (session('error'))
            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700
                dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">

                {{ session('error') }}

            </div>
        @endif


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700
                dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">

                <ul class="list-disc space-y-1 pl-5">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Leave Types Table --}}
        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600 overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1000px]">

                    <thead>

                        <tr class="border-b border-bgray-200 dark:border-darkblack-400">

                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                #
                            </th>

                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Leave Type
                            </th>

                            <th class="px-6 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Code
                            </th>

                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Color
                            </th>

                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                File Required
                            </th>

                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Paid
                            </th>

                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Status
                            </th>

                            <th class="px-6 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-300">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($leaveTypes as $index => $leaveType)

                            <tr
                                class="border-b border-bgray-100 transition hover:bg-bgray-50
                                dark:border-darkblack-400 dark:hover:bg-darkblack-500">

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
                                        class="inline-flex rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-medium
                                        text-bgray-700 dark:bg-darkblack-400 dark:text-bgray-200">

                                        {{ $leaveType->code }}

                                    </span>

                                </td>


                                {{-- Color --}}
                                <td class="px-6 py-4 text-center">

                                    @if ($leaveType->color)

                                        <span
                                            class="inline-flex items-center gap-2 rounded-full border border-bgray-200
                                            bg-white px-3 py-1.5 text-xs font-medium text-bgray-700
                                            dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">

                                            <span
                                                class="h-4 w-4 rounded-full border border-black/10 shadow-sm"
                                                style="background-color: {{ $leaveType->color }};">
                                            </span>

                                            <span>
                                                {{ strtoupper($leaveType->color) }}
                                            </span>

                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs
                                            font-medium text-gray-500 dark:bg-gray-900/30 dark:text-gray-400">

                                            Not Set

                                        </span>

                                    @endif

                                </td>


                                {{-- File Required --}}
                                <td class="px-6 py-4 text-center">

                                    @if ($leaveType->is_file_upload_required)

                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs
                                            font-medium text-white">

                                            Required

                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs
                                            font-medium text-gray-600 dark:bg-gray-900/30 dark:text-gray-300">

                                            Not Required

                                        </span>

                                    @endif

                                </td>


                                {{-- Paid --}}
                                <td class="px-6 py-4 text-center">

                                    @if ($leaveType->is_paid)

                                        <span
                                            class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs
                                            font-medium text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">

                                            Paid

                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs
                                            font-semibold text-white">

                                            Unpaid

                                        </span>

                                    @endif

                                </td>


                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">

                                    @if ($leaveType->status)

                                        <span
                                            class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs
                                            font-medium text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">

                                            Active

                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-error-300 px-3 py-1 text-xs
                                            font-semibold text-white">

                                            Inactive

                                        </span>

                                    @endif

                                </td>


                                {{-- Action --}}
                                <td class="px-6 py-5 xl:w-[165px] xl:px-0">

                                    <div class="flex w-full items-center justify-center space-x-2">

                                        @can('leave_types.edit')

                                            <button type="button"
                                                onclick="openLeaveTypeModal('edit', {{ $leaveType->id }})"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                                                border border-bgray-200 bg-white text-bgray-600
                                                transition hover:bg-bgray-50
                                                dark:border-darkblack-400 dark:bg-darkblack-500
                                                dark:text-bgray-300 dark:hover:bg-darkblack-400"
                                                title="Edit">

                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">

                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />

                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />

                                                </svg>

                                            </button>

                                        @endcan


                                        @can('leave_types.delete')

                                            <x-delete-form
                                                :action="route('settings.leave-types.destroy', $leaveType->id)" />

                                        @endcan

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="px-6 py-12 text-center">

                                    <div class="flex flex-col items-center">

                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-full
                                            bg-bgray-100 dark:bg-darkblack-400">

                                            <svg class="h-7 w-7 text-bgray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />

                                            </svg>

                                        </div>

                                        <p class="mt-3 text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                            No leave types found.
                                        </p>

                                        @can('leave_types.create')

                                            <button type="button"
                                                onclick="openLeaveTypeModal('create')"
                                                class="mt-3 text-sm font-medium text-success-300 hover:underline">

                                                Add your first leave type

                                            </button>

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


    {{-- ========================================================= --}}
    {{-- LEAVE TYPE MODAL --}}
    {{-- ========================================================= --}}

    <div id="leaveTypeModal"
        class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4 py-6">

        <div
            class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl
            dark:bg-darkblack-600">

            {{-- Modal Header --}}
            <div
                class="flex items-center justify-between border-b border-bgray-100 px-6 py-4
                dark:border-darkblack-400">

                <div>

                    <h3 id="leaveTypeModalTitle"
                        class="text-xl font-semibold text-bgray-900 dark:text-white">

                        Create Leave Type

                    </h3>

                    <p id="leaveTypeModalDescription"
                        class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">

                        Add a new leave type to the organization.

                    </p>

                </div>


                <button type="button"
                    onclick="closeLeaveTypeModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg
                    text-bgray-500 hover:bg-bgray-100 hover:text-bgray-700
                    dark:text-bgray-300 dark:hover:bg-darkblack-500">

                    <svg class="h-5 w-5" fill="none"
                        stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12" />

                    </svg>

                </button>

            </div>


            {{-- Modal Form --}}
            <form id="leaveTypeForm"
                action="{{ route('settings.leave-types.store') }}"
                method="POST">

                @csrf

                <input type="hidden"
                    id="leaveTypeMethod"
                    name="_method"
                    value="POST">


                <div class="p-6">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- Name --}}
                        <div>

                            <label for="modal_name"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">

                                Leave Type Name

                                <span class="text-red-500">*</span>

                            </label>

                            <input type="text"
                                id="modal_name"
                                name="name"
                                placeholder="e.g. Annual Leave"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm
                                text-bgray-900 outline-none focus:border-success-300
                                dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                required>

                        </div>


                        {{-- Code --}}
                        <div>

                            <label for="modal_code"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">

                                Code

                                <span class="text-red-500">*</span>

                            </label>

                            <input type="text"
                                id="modal_code"
                                name="code"
                                placeholder="e.g. ANNUAL"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm uppercase
                                text-bgray-900 outline-none focus:border-success-300
                                dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                required>

                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                Use a unique code such as ANNUAL, SICK or UNPAID.
                            </p>

                        </div>


                        {{-- Color --}}
                        <div>

                            <label for="modal_color"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">

                                Calendar Color

                                <span class="text-red-500">*</span>

                            </label>

                            <div class="flex items-center gap-3">

                                <input type="color"
                                    id="modal_color"
                                    name="color"
                                    value="#3B82F6"
                                    class="h-11 w-16 cursor-pointer rounded-lg border border-bgray-200
                                    bg-white p-1 dark:border-darkblack-400 dark:bg-darkblack-500"
                                    required>

                                <div>

                                    <p id="modal_color_value"
                                        class="text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                        #3B82F6
                                    </p>

                                    <p class="mt-0.5 text-xs text-bgray-500 dark:text-bgray-400">
                                        Used to identify this leave on the attendance calendar.
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Status --}}
                        <div>

                            <label for="modal_status"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">

                                Status

                            </label>

                            <select id="modal_status"
                                name="status"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm
                                text-bgray-900 outline-none focus:border-success-300
                                dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        {{-- Description --}}
                        <div class="md:col-span-2">

                            <label for="modal_description"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">

                                Description

                            </label>

                            <textarea id="modal_description"
                                name="description"
                                rows="4"
                                placeholder="Enter a description for this leave type..."
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm
                                text-bgray-900 outline-none focus:border-success-300
                                dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>

                        </div>


                        {{-- File Upload --}}
                        <div
                            class="rounded-lg border border-bgray-200 p-4
                            dark:border-darkblack-400">

                            <div class="flex items-start gap-3">

                                <input type="hidden"
                                    name="is_file_upload_required"
                                    value="0">

                                <input type="checkbox"
                                    id="modal_is_file_upload_required"
                                    name="is_file_upload_required"
                                    value="1"
                                    class="mt-1 h-4 w-4 rounded border-bgray-300
                                    text-success-300 focus:ring-success-300">

                                <div>

                                    <label for="modal_is_file_upload_required"
                                        class="text-sm font-medium text-bgray-800 dark:text-white">

                                        File Upload Required

                                    </label>

                                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">

                                        Require employees to upload a document when
                                        applying for this leave type.

                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Paid Leave --}}
                        <div
                            class="rounded-lg border border-bgray-200 p-4
                            dark:border-darkblack-400">

                            <div class="flex items-start gap-3">

                                <input type="hidden"
                                    name="is_paid"
                                    value="0">

                                <input type="checkbox"
                                    id="modal_is_paid"
                                    name="is_paid"
                                    value="1"
                                    class="mt-1 h-4 w-4 rounded border-bgray-300
                                    text-success-300 focus:ring-success-300">

                                <div>

                                    <label for="modal_is_paid"
                                        class="text-sm font-medium text-bgray-800 dark:text-white">

                                        Paid Leave

                                    </label>

                                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">

                                        Mark this leave type as paid leave.

                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Modal Footer --}}
                <div
                    class="flex items-center justify-end gap-3 border-t border-bgray-100
                    px-6 py-4 dark:border-darkblack-400">

                    <button type="button"
                        onclick="closeLeaveTypeModal()"
                        class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm
                        font-medium text-bgray-700 hover:bg-bgray-50
                        dark:border-darkblack-400 dark:text-bgray-200
                        dark:hover:bg-darkblack-500">

                        Cancel

                    </button>


                    <button type="submit"
                        id="leaveTypeSubmitButton"
                        class="rounded-lg bg-success-300 px-5 py-2.5 text-sm
                        font-medium text-white transition hover:bg-success-400">

                        Save Leave Type

                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script>

        /*
        |--------------------------------------------------------------------------
        | Leave Type Data
        |--------------------------------------------------------------------------
        |
        | Convert the Laravel collection into JavaScript data so the same modal
        | can be used for both Create and Edit.
        |
        */

        const leaveTypes = @json($leaveTypes->items());


        /*
        |--------------------------------------------------------------------------
        | Open Modal
        |--------------------------------------------------------------------------
        */

        function openLeaveTypeModal(mode, id = null) {

            const modal = document.getElementById('leaveTypeModal');

            const form = document.getElementById('leaveTypeForm');

            const title = document.getElementById('leaveTypeModalTitle');

            const description = document.getElementById('leaveTypeModalDescription');

            const submitButton = document.getElementById('leaveTypeSubmitButton');

            const method = document.getElementById('leaveTypeMethod');


            /*
            |--------------------------------------------------------------------------
            | Create
            |--------------------------------------------------------------------------
            */

            if (mode === 'create') {

                form.action = "{{ route('settings.leave-types.store') }}";

                method.value = 'POST';

                title.textContent = 'Create Leave Type';

                description.textContent =
                    'Add a new leave type to the organization.';

                submitButton.textContent = 'Save Leave Type';


                document.getElementById('modal_name').value = '';

                document.getElementById('modal_code').value = '';

                document.getElementById('modal_color').value = '#3B82F6';

                document.getElementById('modal_color_value').textContent = '#3B82F6';

                document.getElementById('modal_description').value = '';

                document.getElementById('modal_status').value = '1';

                document.getElementById('modal_is_file_upload_required').checked = false;

                document.getElementById('modal_is_paid').checked = true;

            }


            /*
            |--------------------------------------------------------------------------
            | Edit
            |--------------------------------------------------------------------------
            */

            if (mode === 'edit') {

                const leaveType = leaveTypes.find(
                    item => Number(item.id) === Number(id)
                );


                if (!leaveType) {

                    console.error('Leave type not found:', id);

                    return;

                }


                form.action =
                    "{{ url('settings/leave-types') }}/" + leaveType.id;

                method.value = 'PUT';

                title.textContent = 'Edit Leave Type';

                description.textContent =
                    'Update the leave type details.';

                submitButton.textContent = 'Update Leave Type';


                document.getElementById('modal_name').value =
                    leaveType.name ?? '';

                document.getElementById('modal_code').value =
                    leaveType.code ?? '';


                let color = leaveType.color || '#3B82F6';

                /*
                 * HTML color input requires a valid hex color.
                 */
                if (!/^#[0-9A-F]{6}$/i.test(color)) {
                    color = '#3B82F6';
                }


                document.getElementById('modal_color').value = color;

                document.getElementById('modal_color_value').textContent =
                    color.toUpperCase();


                document.getElementById('modal_description').value =
                    leaveType.description ?? '';


                document.getElementById('modal_status').value =
                    leaveType.status ? '1' : '0';


                document.getElementById('modal_is_file_upload_required').checked =
                    Boolean(leaveType.is_file_upload_required);


                document.getElementById('modal_is_paid').checked =
                    Boolean(leaveType.is_paid);

            }


            /*
            |--------------------------------------------------------------------------
            | Show Modal
            |--------------------------------------------------------------------------
            */

            modal.classList.remove('hidden');

            modal.classList.add('flex');

            document.body.classList.add('overflow-hidden');

        }


        /*
        |--------------------------------------------------------------------------
        | Close Modal
        |--------------------------------------------------------------------------
        */

        function closeLeaveTypeModal() {

            const modal = document.getElementById('leaveTypeModal');

            modal.classList.add('hidden');

            modal.classList.remove('flex');

            document.body.classList.remove('overflow-hidden');

        }


        /*
        |--------------------------------------------------------------------------
        | Color Preview
        |--------------------------------------------------------------------------
        */

        document.addEventListener('DOMContentLoaded', function() {

            const colorInput =
                document.getElementById('modal_color');

            const colorValue =
                document.getElementById('modal_color_value');


            if (colorInput && colorValue) {

                colorInput.addEventListener('input', function() {

                    colorValue.textContent =
                        this.value.toUpperCase();

                });

            }

        });


        /*
        |--------------------------------------------------------------------------
        | Close Modal When Clicking Outside
        |--------------------------------------------------------------------------
        */

        document.getElementById('leaveTypeModal')
            .addEventListener('click', function(event) {

                if (event.target === this) {

                    closeLeaveTypeModal();

                }

            });


        /*
        |--------------------------------------------------------------------------
        | Close Modal With ESC
        |--------------------------------------------------------------------------
        */

        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                const modal =
                    document.getElementById('leaveTypeModal');

                if (!modal.classList.contains('hidden')) {

                    closeLeaveTypeModal();

                }

            }

        });

    </script>

@endsection
