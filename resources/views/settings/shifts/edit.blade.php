@extends('layouts.master')

@section('page-content')

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="col-span-12 w-full">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                                <div class="flex flex-row items-center gap-3">
                                    <x-back-button :url="route('settings.shifts.index')" />
                                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white">
                                        Edit Shift
                                    </h3>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-bgray-600 dark:text-bgray-300 bg-bgray-100 dark:bg-darkblack-500 rounded-lg px-4 py-2 border border-bgray-200 dark:border-darkblack-400 max-w-xl">
                                    <svg class="w-5 h-5 text-bgray-500 dark:text-bgray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Only Shift Name and Shift Color can be edited. Time settings and weekend configuration are locked after creation.</span>
                                </div>
                            </div>
                            <div class="mt-8">
                                @include('settings.shifts._form')
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
@endsection
