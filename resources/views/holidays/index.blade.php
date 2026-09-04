@extends('layouts.master')

@section('page-content')

<div>

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                Holidays
            </h2>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                Manage company holidays.
            </p>
        </div>

        @can('holidays.create')
            <a href="{{ route('holidays.create') }}"
               class="rounded-lg bg-success-300 px-4 py-2.5 text-sm font-medium text-white hover:bg-success-400">
                Add Holiday
            </a>
        @endcan

    </div>


    {{-- Success --}}
    @if(session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif


    {{-- Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-darkblack-600">

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="border-b border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <tr>

                        <th class="px-6 py-4 text-xs font-semibold uppercase text-bgray-500">
                            Holiday
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold uppercase text-bgray-500">
                            Date
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold uppercase text-bgray-500">
                            Description
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold uppercase text-bgray-500">
                            Status
                        </th>

                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-bgray-500">
                            Actions
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">

                    @forelse($holidays as $holiday)

                        <tr class="hover:bg-bgray-50 dark:hover:bg-darkblack-500">

                            <td class="px-6 py-4 text-sm font-medium text-bgray-900 dark:text-white">
                                {{ $holiday->name }}
                            </td>

                            <td class="px-6 py-4 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $holiday->date->format('d M Y') }}
                            </td>

                            <td class="px-6 py-4 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $holiday->description ?: '-' }}
                            </td>

                            <td class="px-6 py-4">

                                @if($holiday->is_active)

                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                        Active
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        Inactive
                                    </span>

                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <div class="flex justify-end gap-2">

                                    @can('holidays.view')
                                        <a href="{{ route('holidays.show', $holiday->id) }}"
                                           class="rounded-lg border border-bgray-200 px-3 py-2 text-xs font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                                            View
                                        </a>
                                    @endcan

                                    @can('holidays.edit')
                                        <a href="{{ route('holidays.edit', $holiday->id) }}"
                                           class="rounded-lg border border-bgray-200 px-3 py-2 text-xs font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('holidays.delete')
                                        <form action="{{ route('holidays.destroy', $holiday->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this holiday?');">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-bgray-500">
                                No holidays found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($holidays->hasPages())
            <div class="border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                {{ $holidays->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
