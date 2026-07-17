@php
    $isEditable = ! $answerData['is_submitted'];
    $isAnswerQuestion = $question['question_type'] === 'answer';
    $answer = $question['answer'] ?? [];
@endphp

<article class="overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-answer-question-card>
    <header class="flex cursor-pointer items-center justify-between gap-3 p-4 transition hover:bg-bgray-50 dark:hover:bg-darkblack-600 animate-fade-in" data-appraisal-answer-question-header>
        <div class="flex items-center gap-3">
            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">{{ $index + 1 }}</span>
            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $question['question'] }}</p>
        </div>
        <button type="button" class="text-bgray-600 transition-transform duration-200 hover:text-bgray-800 focus:outline-none dark:text-bgray-400 dark:hover:text-white" aria-label="Toggle answer body" data-appraisal-answer-question-toggle>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rotate-180 transform transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </header>

    <div class="space-y-3 border-t border-bgray-100 p-4 transition-all duration-200 dark:border-darkblack-400" data-appraisal-answer-question-body>
        @if ($isAnswerQuestion)
            @php
                $editable = $answerData['role'] === 'assignee' && $isEditable;
                $readonlyClasses = $editable ? '' : 'bg-bgray-100 dark:bg-darkblack-400 cursor-default';
            @endphp
            <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 dark:border-darkblack-400 dark:bg-darkblack-600">
                <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-900 dark:text-white">Assignee Answer Only</p>
                <div class="mt-1.5">
                    <textarea rows="4" placeholder="Enter your answer" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 {{ $readonlyClasses }}" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="assignee_answer" @readonly(! $editable)>{{ $answer['assignee_answer'] ?? '' }}</textarea>
                </div>
            </div>
        @else
            @include('appraisal.partials.answer-rating-section', [
                'label' => $answerData['role'] === 'assignee' && $isEditable ? 'Self' : 'Assignee',
                'ratingField' => 'assignee_rating',
                'remarkField' => 'assignee_remark',
                'editable' => $answerData['role'] === 'assignee' && $isEditable,
            ])
            @include('appraisal.partials.answer-rating-section', [
                'label' => 'Reporter',
                'ratingField' => 'reporter_rating',
                'remarkField' => 'reporter_remark',
                'editable' => $answerData['role'] === 'reporter' && $isEditable,
            ])
            @include('appraisal.partials.answer-rating-section', [
                'label' => 'Manager',
                'ratingField' => 'manager_rating',
                'remarkField' => 'manager_remark',
                'editable' => $answerData['role'] === 'manager' && $isEditable,
            ])
        @endif
    </div>
</article>
