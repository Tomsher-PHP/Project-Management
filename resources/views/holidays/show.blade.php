@extends('layouts.master')

@section('page-content')

<div>

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                Holiday Details
            </h2>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                View holiday information.
            </p>
        </div>

        <div class="flex gap-2">

            @can('holidays.edit')
                <a href="{{ route('holidays.edit', $holiday->id) }}"
                   class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                    Edit
                </a>
            @endcan

            <a href="{{ route('holidays.index') }}"
               class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                Back
            </a>

        </div>

    </div>


    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            <div>
                <p class="text-xs font-medium uppercase text-bgray-500">
                    Holiday Name
                </p>

                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                    {{ $holiday->name }}
                </p>
            </div>


            <div>
                <p class="text-xs font-medium uppercase text-bgray-500">
                    Date
                </p>

                <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                    {{ $holiday->date->format('d M Y') }}
                </p>
            </div>


            <div>
                <p class="text-xs font-medium uppercase text-bgray-500">
                    Status
                </p>

                <p class="mt-1">

                    @if($holiday->is_active)
                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                            Active
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                            Inactive
                        </span>
                    @endif

                </p>
            </div>


            <div>
                <p class="text-xs font-medium uppercase text-bgray-500">
                    Created At
                </p>

                <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                    {{ $holiday->created_at->format('d M Y h:i A') }}
                </p>
            </div>


            <div class="md:col-span-2">

                <p class="text-xs font-medium uppercase text-bgray-500">
                    Description
                </p>

                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                    {{ $holiday->description ?: 'No description provided.' }}
                </p>

            </div>

        </div>

    </div>

</div>

@endsection
