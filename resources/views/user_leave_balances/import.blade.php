@extends('layouts.master')

@section('page-content')

<div class="space-y-6">


    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900 dark:bg-darkblack-500 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700 dark:border-yellow-900 dark:bg-darkblack-500 dark:text-yellow-400">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-darkblack-500">
            <ul class="list-disc pl-5 text-sm text-red-700 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Import Result --}}
    @php
        $result = session('leave_balance_import_result');
    @endphp

    @if($result)
        <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-base font-bold text-bgray-900 dark:text-white">
                    Import Results
                </h3>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                {{-- Created --}}
                <div class="rounded-lg border border-green-200 bg-green-50/70 p-4 dark:border-green-900/50 dark:bg-darkblack-500">
                    <p class="text-xs font-semibold text-green-600 dark:text-green-400">
                        Created
                    </p>
                    <p class="mt-1 text-2xl font-bold text-green-700 dark:text-green-400">
                        {{ $result['created'] ?? 0 }}
                    </p>
                </div>

                {{-- Updated --}}
                <div class="rounded-lg border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900/50 dark:bg-darkblack-500">
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                        Updated
                    </p>
                    <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-400">
                        {{ $result['updated'] ?? 0 }}
                    </p>
                </div>

                {{-- Failed --}}
                <div class="rounded-lg border border-red-200 bg-red-50/70 p-4 dark:border-red-900/50 dark:bg-darkblack-500">
                    <p class="text-xs font-semibold text-red-600 dark:text-red-400">
                        Failed
                    </p>
                    <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-400">
                        {{ $result['failed'] ?? 0 }}
                    </p>
                </div>

            </div>

            {{-- Import Errors --}}
            @if(!empty($result['errors']))

                <div class="mt-6 overflow-hidden rounded-lg border border-red-200 dark:border-red-900/50">

                    <div class="border-b border-red-200 bg-red-50 px-5 py-3 dark:border-red-900/50 dark:bg-darkblack-500">
                        <h4 class="text-sm font-semibold text-red-700 dark:text-red-400">
                            Import Errors
                        </h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">

                            <thead class="bg-bgray-50 text-[11px] uppercase tracking-wider text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Row</th>
                                    <th class="px-4 py-3 font-semibold">Employee Email</th>
                                    <th class="px-4 py-3 font-semibold">Leave Type</th>
                                    <th class="px-4 py-3 font-semibold">Error</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-bgray-100 bg-white dark:divide-darkblack-400 dark:bg-darkblack-600">

                                @foreach($result['errors'] as $error)
                                    <tr>
                                        <td class="px-4 py-3 font-mono text-bgray-700 dark:text-bgray-300">
                                            {{ $error['row'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-bgray-700 dark:text-bgray-300">
                                            {{ $error['employee_email'] ?: '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-bgray-700 dark:text-bgray-300">
                                            {{ $error['leave_type'] ?: '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-red-600 dark:text-red-400">
                                            {{ $error['error'] }}
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>

                        </table>
                    </div>

                </div>

            @endif

        </div>
    @endif

    {{-- Import Card --}}
    <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">

        {{-- Overview and Template --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <x-back-button :url="route('leave-requests.index')" />

                <div>

                    <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                        Leave Balance Import
                    </h3>

                    {{-- Import Steps --}}
                    <div class="mt-2.5 flex flex-wrap items-center gap-3 text-xs text-bgray-600 dark:text-bgray-300">

                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">
                                1
                            </span>
                            Download Template
                        </span>

                        <svg class="h-3 w-3 text-bgray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>

                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">
                                2
                            </span>
                            Fill Leave Details
                        </span>

                        <svg class="h-3 w-3 text-bgray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>

                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">
                                3
                            </span>
                            Upload &amp; Import
                        </span>

                    </div>
                </div>
            </div>

            {{-- Download Sample --}}
            <div class="flex-shrink-0">

                <a
                    href="{{ route('user-leave-balances.import.sample') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-success-300 bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:border-success-400 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16"/>
                    </svg>

                    <span>Download Sample Excel</span>
                </a>

            </div>

        </div>

        {{-- Instructions --}}
        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50/60 p-5 dark:border-blue-900/50 dark:bg-darkblack-500">

            <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-blue-800 dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 110-20 10 10 0 010 20z"/>
                </svg>
                Import Instructions
            </h4>

            <ul class="space-y-2 text-xs leading-relaxed text-blue-700 dark:text-blue-300">

                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-blue-500">•</span>
                    Download the sample Excel file to get the correct import format.
                </li>

                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-blue-500">•</span>
                    Enter the employee leave balance details in the appropriate columns.
                </li>

                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-blue-500">•</span>
                    Do not change the column headings in the downloaded template.
                </li>

                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-blue-500">•</span>
                    Ensure employee email addresses and leave types are valid.
                </li>

                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-blue-500">•</span>
                    Existing leave balances will be updated according to the import matching rules.
                </li>

            </ul>

        </div>

        {{-- Expected Columns --}}
        <div class="mt-5">

            <h4 class="mb-3 text-sm font-bold text-bgray-800 dark:text-white">
                Expected Excel Columns
            </h4>

            <div class="flex flex-wrap gap-2">

                @foreach([
                    'Employee Email',
                    'Leave Type',
                    'Year',
                    'Valid From',
                    'Valid To',
                    'Yearly Entitlement',
                    'Monthly Entitlement',
                    'Opening Balance',
                    'Current Balance',
                    'Used Balance',
                    'Paid Days Used',
                    'Unpaid Days Used',
                    'Cancelled Days Restored',
                    'Carry Forward Balance',
                    'Is Carry Forward',
                    'Status',
                ] as $column)

                    <span class="rounded-md border border-bgray-200 bg-bgray-50 px-2.5 py-1.5 text-[11px] font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                        {{ $column }}
                    </span>

                @endforeach

            </div>

        </div>

        {{-- Import Form --}}
        <form
            id="leave-balance-import-form"
            action="{{ route('user-leave-balances.import.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="mt-7"
        >

            @csrf

            {{-- Drag and Drop Upload --}}
            <div
                id="leave-file-upload-box"
                class="cursor-pointer rounded-xl border-2 border-dashed border-bgray-300 p-8 text-center transition hover:border-success-300 dark:border-darkblack-400"
            >

                {{-- Upload Prompt --}}
                <div id="leave-file-upload-prompt">

                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-success-50 dark:bg-darkblack-500">
                        <svg class="h-6 w-6 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/>
                        </svg>
                    </div>

                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                        Drag and drop your Excel or CSV file here
                    </p>

                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                        or
                        <span class="font-semibold text-success-500">
                            browse file
                        </span>
                    </p>

                </div>

                {{-- Selected File --}}
                <div id="leave-file-chosen-container" class="hidden items-center justify-center gap-2">

                    <svg class="h-6 w-6 flex-shrink-0 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a2 2 0 01.707.293l5.414 5.414a2 2 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>

                    <div class="text-left">
                        <p id="leave-file-chosen-name" class="break-all text-sm font-semibold text-bgray-900 dark:text-white"></p>
                        <p class="mt-0.5 text-xs text-success-500">
                            File selected successfully
                        </p>
                    </div>

                    <span class="ml-2 text-xs font-medium text-bgray-400">
                        (Click to change)
                    </span>

                </div>

                <input
                    id="leave-excel-file-input"
                    type="file"
                    name="file"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                    required
                />

            </div>

            {{-- File Information and Submit --}}
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <p class="text-xs text-bgray-500 dark:text-bgray-400">
                    Accepted formats: .xlsx, .xls, .csv (Maximum file size: 10 MB)
                </p>

                <button
                    type="submit"
                    id="leave-btn-start-import"
                    disabled
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-success-300 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition duration-200 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                >

                    <svg
                        id="leave-btn-import-icon"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>

                    <svg
                        id="leave-btn-import-spinner"
                        class="hidden h-4 w-4 animate-spin text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>

                    <span id="leave-btn-import-label">
                        Import Leave Balances
                    </span>

                </button>

            </div>

            @error('file')
                <p class="mt-2 text-xs font-medium text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </form>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const uploadBox = document.getElementById('leave-file-upload-box');
    const fileInput = document.getElementById('leave-excel-file-input');
    const uploadPrompt = document.getElementById('leave-file-upload-prompt');
    const chosenContainer = document.getElementById('leave-file-chosen-container');
    const chosenName = document.getElementById('leave-file-chosen-name');

    const importForm = document.getElementById('leave-balance-import-form');
    const importButton = document.getElementById('leave-btn-start-import');
    const importIcon = document.getElementById('leave-btn-import-icon');
    const importSpinner = document.getElementById('leave-btn-import-spinner');
    const importLabel = document.getElementById('leave-btn-import-label');

    if (!uploadBox || !fileInput || !importForm) {
        return;
    }

    const maxFileSize = 10 * 1024 * 1024;
    const allowedExtensions = ['xlsx', 'xls', 'csv'];

    function updateFileDisplay(file) {

        if (!file) {
            uploadPrompt.classList.remove('hidden');
            chosenContainer.classList.add('hidden');
            chosenContainer.classList.remove('flex');
            chosenName.textContent = '';
            importButton.disabled = true;
            return;
        }

        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            alert('Please select a valid Excel or CSV file.');
            fileInput.value = '';
            updateFileDisplay(null);
            return;
        }

        if (file.size > maxFileSize) {
            alert('File size must not exceed 10 MB.');
            fileInput.value = '';
            updateFileDisplay(null);
            return;
        }

        uploadPrompt.classList.add('hidden');
        chosenContainer.classList.remove('hidden');
        chosenContainer.classList.add('flex');

        chosenName.textContent = file.name;
        importButton.disabled = false;
    }

    // Open file browser
    uploadBox.addEventListener('click', function () {
        fileInput.click();
    });

    // File selection
    fileInput.addEventListener('change', function () {
        updateFileDisplay(this.files[0]);
    });

    // Drag over
    uploadBox.addEventListener('dragover', function (event) {
        event.preventDefault();
        uploadBox.classList.add('border-success-400', 'bg-success-50/30');
    });

    // Drag leave
    uploadBox.addEventListener('dragleave', function () {
        uploadBox.classList.remove('border-success-400', 'bg-success-50/30');
    });

    // Drop file
    uploadBox.addEventListener('drop', function (event) {
        event.preventDefault();

        uploadBox.classList.remove('border-success-400', 'bg-success-50/30');

        const files = event.dataTransfer.files;

        if (!files.length) {
            return;
        }

        try {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            fileInput.files = dataTransfer.files;

            updateFileDisplay(files[0]);
        } catch (error) {
            console.error('Unable to select dropped file:', error);
        }
    });

    // Submit loading state
    importForm.addEventListener('submit', function (event) {

        if (!fileInput.files.length) {
            event.preventDefault();
            alert('Please select a file before importing.');
            return;
        }

        importButton.disabled = true;

        importIcon.classList.add('hidden');
        importSpinner.classList.remove('hidden');

        importLabel.textContent = 'Importing...';
    });

});
</script>
@endpush
