@php
    $readonlyClasses = $editable ? '' : 'bg-bgray-100 dark:bg-darkblack-400 cursor-default';
@endphp

<div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 dark:border-darkblack-400 dark:bg-darkblack-600">
    <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-900 dark:text-white">{{ $label }}</p>
    <div class="mt-1.5 grid gap-2 md:grid-cols-[120px_1fr]">
        <input type="number" min="0.1" max="5" step="0.1" placeholder="Rating" value="{{ $rating ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 {{ $readonlyClasses }}" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="rating" data-answer-scope="{{ $scope }}" @if($reviewerId ?? null) data-reviewer-id="{{ $reviewerId }}" @endif @readonly(! $editable)>
        <textarea rows="1" placeholder="Remark" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 {{ $readonlyClasses }}" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="remark" data-answer-scope="{{ $scope }}" @if($reviewerId ?? null) data-reviewer-id="{{ $reviewerId }}" @endif @readonly(! $editable)>{{ $remark ?? '' }}</textarea>
    </div>
</div>
