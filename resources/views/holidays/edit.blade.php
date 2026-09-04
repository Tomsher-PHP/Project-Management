@extends('layouts.master')

@section('page-content')

<div>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                Edit Holiday
            </h2>

            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                Update the holiday details.
            </p>
        </div>

        <a href="{{ route('holidays.index') }}"
           class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
            Back
        </a>
    </div>


    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">

        <form action="{{ route('holidays.update', $holiday->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Holiday Name <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $holiday->name) }}"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >

                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                <div>
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Date <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="date"
                        value="{{ old('date', $holiday->date?->format('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >

                    @error('date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-white">
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        class="w-full rounded-lg border border-bgray-200 px-4 py-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    >{{ old('description', $holiday->description) }}</textarea>

                    @error('description')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>


                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $holiday->is_active) ? 'checked' : '' }}
                            class="rounded border-bgray-300"
                        >

                        <span class="text-sm text-bgray-700 dark:text-white">
                            Active
                        </span>

                    </label>
                </div>

            </div>


            <div class="mt-6 flex justify-end gap-3">

                <a href="{{ route('holidays.index') }}"
                   class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-400">
                    Update Holiday
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
