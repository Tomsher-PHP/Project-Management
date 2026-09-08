@extends('layouts.master')

@section('page-content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-bgray-900 dark:text-white">
                {{ $pageTitle ?? 'Import Leave Balances' }}
            </h1>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                {{ $subTitle ?? 'Bulk import user leave balance details.' }}
            </p>
        </div>

    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-5">

    <a
        href="{{ route('leave-requests.index') }}"
        class="rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-50 dark:border-bgray-600 dark:bg-darkblack-500 dark:text-white"
    >
        Back
    </a>

    <a
        href="{{ route('user-leave-balances.import.sample') }}"
        class="inline-flex items-center gap-2 rounded-lg border border-success-300 px-5 py-2.5 text-sm font-semibold text-success-400 transition hover:bg-success-300 hover:text-white"
    >
        <svg xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3v12"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8 11l4 4 4-4"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 21h14"
            />
        </svg>

        Download Sample Excel
    </a>

    <button
        type="submit"
        class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400"
    >
        Import Leave Balances
    </button>

</div>

    {{-- Success --}}
    @if(session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif


    {{-- Warning --}}
    @if(session('warning'))
        <div class="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700">
            {{ session('warning') }}
        </div>
    @endif


    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">

            <ul class="list-disc pl-5 text-sm text-red-700">

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

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Created --}}
            <div class="rounded-lg border border-green-200 bg-green-50 p-5">
                <p class="text-sm text-green-600">
                    Created
                </p>

                <p class="mt-1 text-2xl font-bold text-green-700">
                    {{ $result['created'] }}
                </p>
            </div>


            {{-- Updated --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-5">
                <p class="text-sm text-blue-600">
                    Updated
                </p>

                <p class="mt-1 text-2xl font-bold text-blue-700">
                    {{ $result['updated'] }}
                </p>
            </div>


            {{-- Failed --}}
            <div class="rounded-lg border border-red-200 bg-red-50 p-5">
                <p class="text-sm text-red-600">
                    Failed
                </p>

                <p class="mt-1 text-2xl font-bold text-red-700">
                    {{ $result['failed'] }}
                </p>
            </div>

        </div>


        {{-- Error Details --}}
        @if(!empty($result['errors']))

            <div class="mb-6 overflow-hidden rounded-lg border border-red-200">

                <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                    <h3 class="font-semibold text-red-700">
                        Import Errors
                    </h3>
                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="bg-bgray-100 dark:bg-darkblack-500">

                            <tr>
                                <th class="px-4 py-3">
                                    Row
                                </th>

                                <th class="px-4 py-3">
                                    Employee Email
                                </th>

                                <th class="px-4 py-3">
                                    Leave Type
                                </th>

                                <th class="px-4 py-3">
                                    Error
                                </th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($result['errors'] as $error)

                                <tr class="border-t border-bgray-200">

                                    <td class="px-4 py-3">
                                        {{ $error['row'] }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $error['employee_email'] ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $error['leave_type'] ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-red-600">
                                        {{ $error['error'] }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @endif

    @endif


    {{-- Import Form --}}
    <div class="rounded-xl border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">

        <div class="mb-6">

            <h2 class="text-lg font-semibold text-bgray-900 dark:text-white">
                Upload Leave Balance File
            </h2>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                Upload an Excel or CSV file containing user leave balance details.
            </p>

        </div>


        <form
            action="{{ route('user-leave-balances.import.store') }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf


            <div class="mb-6">

                <label
                    for="file"
                    class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white"
                >
                    Excel / CSV File
                    <span class="text-red-500">*</span>
                </label>


                <input
                    type="file"
                    name="file"
                    id="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-bgray-100 file:px-4 file:py-2 file:text-sm file:font-medium dark:border-bgray-600 dark:bg-darkblack-500 dark:text-white"
                />


                <p class="mt-2 text-xs text-bgray-500">
                    Allowed formats: XLSX, XLS, CSV. Maximum size: 10 MB.
                </p>

            </div>


            {{-- Expected Columns --}}
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-5">

                <h3 class="mb-3 font-semibold text-blue-800">
                    Required Excel Columns
                </h3>


                <div class="grid grid-cols-1 gap-2 text-sm text-blue-700 md:grid-cols-2 lg:grid-cols-3">

                    <div>Employee Email</div>
                    <div>Leave Type</div>
                    <div>Year</div>
                    <div>Valid From</div>
                    <div>Valid To</div>
                    <div>Yearly Entitlement</div>
                    <div>Monthly Entitlement</div>
                    <div>Opening Balance</div>
                    <div>Current Balance</div>
                    <div>Used Balance</div>
                    <div>Paid Days Used</div>
                    <div>Unpaid Days Used</div>
                    <div>Cancelled Days Restored</div>
                    <div>Carry Forward Balance</div>
                    <div>Is Carry Forward</div>
                    <div>Status</div>

                </div>

            </div>


            {{-- Buttons --}}
            <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-5">

                <a
                    href="{{ route('leave-requests.index') }}"
                    class="rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-50 dark:border-bgray-600 dark:bg-darkblack-500 dark:text-white"
                >
                    Back
                </a>


                <button
                    type="submit"
                    class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400"
                >
                    Import Leave Balances
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
