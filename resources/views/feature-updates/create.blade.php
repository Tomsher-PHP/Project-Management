@extends('layouts.master')

@section('page-content')

    <!-- Page starts -->

    <div class="mb-6">
       

        <div class="border-b border-bgray-200 pb-5 flex flex-row items-center gap-3">
            <x-back-button />

            <h3 class="text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                Add New Feature
            </h4>
        </div>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="mb-5 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-300 dark:border-error-900/40 dark:bg-darkblack-500">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Create Feature Form -->
    <div class="w-full rounded-lg bg-white px-6 py-6 dark:bg-darkblack-600 sm:px-8">

        <form
            method="POST"
            action="{{ route('feature-settings.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            @include('feature-updates.partials.form')

            <!-- Form Actions -->
            <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-5 dark:border-darkblack-400">

                <a href="{{ route('feature-settings.index') }}"
                    class="rounded-lg border border-bgray-200 bg-white px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                    <i class="fa-solid fa-floppy-disk mr-1"></i>
                    Save Feature
                </button>

            </div>
        </form>

    </div>

    <!-- Page ends -->

@endsection
