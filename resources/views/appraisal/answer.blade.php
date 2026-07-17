@extends('layouts.master')

@section('page-content')
    <div class="space-y-6" data-appraisal-answer-page data-save-draft-url="{{ route('appraisal.save-draft', ['appraisal' => $answerData['id']]) }}" data-submit-answers-url="{{ route('appraisal.submit-answers', ['appraisal' => $answerData['id']]) }}" data-acknowledge-review-url="{{ route('appraisal.acknowledge-review', ['appraisal' => $answerData['id']]) }}" data-index-url="{{ route('appraisal.index') }}">
        <script type="application/json" data-appraisal-answer-page-data>
            @json($answerData)
        </script>

        @include('appraisal.partials.answer-header')

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-4">
            <main class="min-h-[480px] min-w-0 rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-6 xl:col-span-3 xl:max-h-[calc(100vh-240px)] xl:overflow-y-auto">
                @include('appraisal.partials.answer-acknowledgement')
                @include('appraisal.partials.answer-questions')
                @include('appraisal.partials.answer-overall-comments')
            </main>

            <aside class="w-full space-y-5 xl:sticky xl:top-24 xl:col-span-1 xl:max-h-[calc(100vh-120px)] xl:overflow-y-auto">
                @include('appraisal.partials.answer-sidebar-progress')
                @include('appraisal.partials.answer-sidebar-categories')
            </aside>
        </div>

        @unless (data_get($answerData, 'acknowledgement.required'))
            @include('appraisal.partials.answer-footer-actions')
        @endunless
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal-answer.js')
@endpush
