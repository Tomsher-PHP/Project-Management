@extends('layouts.master')

@section('page-content')

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="col-span-12 w-full">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Edit Team
                            </h3>

                            <div class="mt-8">
                                @include('teams._form', ['team' => $team])
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
@endsection
@push('scripts')
    @vite('resources/js/image-draggable.js')
    @vite('resources/js/modules/team.js')
@endpush
