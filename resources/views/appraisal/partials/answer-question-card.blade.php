@php
    $questionType = $question['question_type'] ?? 'rating';
    $answer = $question['answer'] ?? [];
    $reviews = collect($question['reviews'] ?? []);
    $assigneeEditable = $answerData['role'] === 'assignee' && ! $answerData['is_submitted'];
    $unit = $question['unit'] ?? '';
@endphp

<article class="overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-answer-question-card data-question-type="{{ $questionType }}">
    <header class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 transition hover:bg-bgray-50 dark:hover:bg-darkblack-600 animate-fade-in" data-appraisal-answer-question-header>
        <div class="flex items-center gap-3">
            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">{{ $index + 1 }}</span>
            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $question['question'] }}</p>
        </div>
        <button type="button" class="text-bgray-600 transition-transform duration-200 hover:text-bgray-800 focus:outline-none dark:text-bgray-400 dark:hover:text-white" aria-label="Toggle answer body" data-appraisal-answer-question-toggle>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rotate-180 transform transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
        </button>
    </header>

    <div class="space-y-2 border-t border-bgray-100 p-4 pt-3 transition-all duration-200 dark:border-darkblack-400" data-appraisal-answer-question-body>
        @if ($questionType === 'answer')
            @if ($assigneeEditable)
                <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-900 dark:text-white">Assignee Answer</p>
                    <textarea rows="3" placeholder="Enter your answer" class="mt-1.5 w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="answer" data-answer-scope="answer">{{ $answer['answer'] ?? '' }}</textarea>
                </div>
            @else
                <div class="text-sm border-b border-gray-100 pb-1.5 last:border-0 dark:border-darkblack-400">
                    <p class="font-bold text-bgray-900 dark:text-white">Answer:</p>
                    <p class="mt-1 text-bgray-600 dark:text-bgray-300 whitespace-pre-line">{{ !is_null($answer['answer'] ?? null) && ($answer['answer'] ?? '') !== '' ? $answer['answer'] : '--' }}</p>
                </div>
            @endif

        @elseif ($questionType === 'target')
            {{-- Compact Target Label --}}
            <div class="flex items-center">
                <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-900/30 dark:text-success-400">
                    Target: {{ $question['target_value'] }} {{ $unit }}
                </span>
            </div>

            @if ($assigneeEditable)
                <div class="flex flex-wrap items-center gap-4 mt-2">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-bgray-900 dark:text-white">Achieved</span>
                        <div class="flex items-center gap-1.5 w-[140px]">
                            <input type="number" step="any" value="{{ $answer['achieved_value'] ?? '' }}" placeholder="Achieved" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="achieved_value" data-answer-scope="answer" data-target-value="{{ $question['target_value'] }}">
                            <span class="text-sm font-medium text-bgray-600 dark:text-bgray-300">{{ $unit }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-bgray-600 dark:text-bgray-300">
                        <span>Achievement %:</span>
                        <strong class="text-bgray-900 dark:text-white" data-appraisal-target-achievement>
                            {{ $answer['achievement_percentage'] !== null ? number_format((float) $answer['achievement_percentage'], 2).'%' : '—' }}
                        </strong>
                    </div>
                </div>
            @else
                <div class="text-sm mt-1 flex flex-wrap items-center gap-x-4 text-bgray-600 dark:text-bgray-300">
                    <span>Achieved: <strong class="text-bgray-900 dark:text-white">{{ !is_null($answer['achieved_value'] ?? null) && ($answer['achieved_value'] ?? '') !== '' ? $answer['achieved_value'].' '.$unit : '--' }}</strong></span>
                    <span>Achievement: <strong class="text-bgray-900 dark:text-white">{{ $answer['achievement_percentage'] !== null ? number_format((float) $answer['achievement_percentage'], 2).'%' : '—' }}</strong></span>
                </div>
            @endif

            @foreach ($reviews as $review)
                @php $reviewEditable = $answerData['role'] === 'reviewer' && $review['is_current'] && ! $answerData['is_submitted']; @endphp
                @if ($reviewEditable)
                    <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2 dark:border-darkblack-400 dark:bg-darkblack-600 mt-2">
                        <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-900 dark:text-white">Reviewer Level {{ $review['level'] }} &bull; {{ $review['name'] }} Remark</p>
                        <textarea rows="1" placeholder="Reviewer remark" class="mt-1.5 w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="remark" data-answer-scope="review" data-reviewer-id="{{ $review['appraisal_reviewer_id'] }}">{{ $review['remark'] ?? '' }}</textarea>
                    </div>
                @else
                    <div class="text-sm border-b border-gray-100 pb-1.5 last:border-0 dark:border-darkblack-400 mt-2">
                        <p class="font-bold text-bgray-900 dark:text-white">Reviewer Level {{ $review['level'] }} &bull; {{ $review['name'] }} Remark:</p>
                        <p class="mt-0.5 text-bgray-600 dark:text-bgray-300">{{ !is_null($review['remark'] ?? null) && ($review['remark'] ?? '') !== '' ? $review['remark'] : '--' }}</p>
                    </div>
                @endif
            @endforeach

        @else
            @include('appraisal.partials.answer-rating-section', [
                'label' => 'Self (Assignee)', 'scope' => 'answer', 'reviewerId' => null,
                'rating' => $answer['rating'] ?? null, 'remark' => $answer['remark'] ?? null,
                'editable' => $assigneeEditable,
            ])
            @foreach ($reviews as $review)
                @include('appraisal.partials.answer-rating-section', [
                    'label' => 'Reviewer Level '.$review['level'].' • '.$review['name'],
                    'scope' => 'review', 'reviewerId' => $review['appraisal_reviewer_id'],
                    'rating' => $review['rating'] ?? null, 'remark' => $review['remark'] ?? null,
                    'editable' => $answerData['role'] === 'reviewer' && $review['is_current'] && ! $answerData['is_submitted'],
                ])
            @endforeach
        @endif
    </div>
</article>
