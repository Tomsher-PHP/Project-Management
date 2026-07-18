@if ($editable)
    <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2 dark:border-darkblack-400 dark:bg-darkblack-600">
        <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-900 dark:text-white">{{ $label }}</p>
        <div class="mt-1.5 flex items-center gap-3">
            <input type="number" min="0.1" max="5" step="0.1" placeholder="Rating" value="{{ $rating ?? '' }}" class="w-[120px] rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="rating" data-answer-scope="{{ $scope }}" @if($reviewerId ?? null) data-reviewer-id="{{ $reviewerId }}" @endif>
            <textarea rows="1" placeholder="Remark" class="flex-1 rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-answer-input data-question-id="{{ $question['id'] }}" data-answer-field="remark" data-answer-scope="{{ $scope }}" @if($reviewerId ?? null) data-reviewer-id="{{ $reviewerId }}" @endif>{{ $remark ?? '' }}</textarea>
        </div>
    </div>
@else
    <div class="text-sm border-b border-gray-100 pb-1.5 last:border-0 dark:border-darkblack-400">
        <p class="font-bold text-bgray-900 dark:text-white">{{ $label }}</p>
        <div class="mt-0.5 flex flex-wrap items-center gap-x-4 text-bgray-600 dark:text-bgray-300">
            <span>Rating: <strong class="text-bgray-900 dark:text-white">{{ !is_null($rating) && $rating !== '' ? $rating : '--' }}</strong></span>
            <span>Remark: <span class="text-bgray-900 dark:text-white">{{ !is_null($remark) && $remark !== '' ? $remark : '--' }}</span></span>
        </div>
    </div>
@endif
