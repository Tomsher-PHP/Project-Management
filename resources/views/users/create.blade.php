@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="xl:col-span-7 2xl:col-span-8">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Create New User
                            </h3>
                            <div class="mt-8">
                                @include('users._form', ['user' => null])
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
@endsection
@push('scripts')
    <script src="{{ asset(config('assets.js.image_draggable')) }}"></script>
@endpush
