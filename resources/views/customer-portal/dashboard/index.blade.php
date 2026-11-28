@extends('customer-portal.layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <ol class="flex items-center gap-2">
        <li class="font-medium text-bgray-900 dark:text-white">Dashboard</li>
    </ol>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-bgray-900 dark:text-white">
                Dashboard
            </h1>
            <p class="mt-2 text-base text-bgray-700 dark:text-bgray-200">
                Welcome, <span class="font-semibold text-bgray-900 dark:text-white">{{ $customer->name }}</span>.
            </p>
            <p class="mt-3 text-sm text-bgray-600 dark:text-bgray-300">
                You are signed in to the {{ config('app.name', 'Project Management') }} Customer Portal. Additional portal modules will appear here as they become available.
            </p>
        </div>
    </div>
@endsection
