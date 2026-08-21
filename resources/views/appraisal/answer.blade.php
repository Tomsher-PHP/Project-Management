@extends('layouts.master')

@section('page-content')
    @php
        $appraisalIndexUrl = route('appraisal.index');
        $previousUrl = url()->previous();
        $appraisalBackUrl = parse_url($previousUrl, PHP_URL_PATH) === parse_url($appraisalIndexUrl, PHP_URL_PATH) ? $previousUrl : $appraisalIndexUrl;
    @endphp

    <div class="appraisal-answer-page" data-appraisal-answer-page data-save-draft-url="{{ route('appraisal.save-draft', ['appraisal' => $answerData['id']]) }}" data-submit-answers-url="{{ route('appraisal.submit-answers', ['appraisal' => $answerData['id']]) }}" data-acknowledge-review-url="{{ route('appraisal.acknowledge-review', ['appraisal' => $answerData['id']]) }}" data-index-url="{{ route('appraisal.index') }}">
        <script type="application/json" data-appraisal-answer-page-data>
            @json($answerData)
        </script>

        <div class="appraisal-answer-content">
            <div class="appraisal-answer-left-col">
                <div class="appraisal-answer-header">
                    @include('appraisal.partials.answer-header')
                </div>

                <div class="appraisal-answer-left">
                    @include('appraisal.partials.answer-acknowledgement')
                    @include('appraisal.partials.answer-questions')
                    @include('appraisal.partials.answer-overall-comments')
                </div>

                @if (!$answerData['is_submitted'] && !data_get($answerData, 'acknowledgement.required'))
                    @include('appraisal.partials.answer-footer-actions')
                @endif
            </div>

            <div class="appraisal-answer-right sticky top-20">
                @include('appraisal.partials.answer-sidebar-progress')
                @include('appraisal.partials.answer-sidebar-rating-scale')
                @include('appraisal.partials.answer-sidebar-categories')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal-answer.js')
@endpush
