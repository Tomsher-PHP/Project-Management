@extends('customer-portal.layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <ol class="flex items-center gap-2">
        <li class="font-medium text-slate-700">Dashboard</li>
    </ol>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Dashboard
            </h1>
            <p class="mt-2 text-base text-slate-600">
                Welcome, <span class="font-semibold text-slate-900">{{ $customer->name }}</span>.
            </p>
            <p class="mt-3 text-sm text-slate-500">
                You are signed in to the {{ config('app.name', 'Project Management') }} Customer Portal. Additional portal modules will appear here as they become available.
            </p>
        </div>
    </div>
@endsection
