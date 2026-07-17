<footer class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-bgray-200 bg-white px-5 py-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-6">
    <p @class([
        'text-sm font-medium text-red-500',
        'hidden' => $answerData['is_submitted'] || $progress['can_submit'],
    ]) data-appraisal-answer-helper-message>
        All questions must be answered before submitting. You can save your progress as a draft anytime.
    </p>
    <div class="ml-auto flex items-center gap-3">
        <button type="button" @class([
            'rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50',
            'hidden' => $answerData['is_submitted'],
        ]) data-appraisal-answer-save-draft>
            Save Draft
        </button>
        <button type="button" @class([
            'rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50',
            'hidden' => $answerData['is_submitted'],
            'cursor-not-allowed opacity-50' => ! $progress['can_submit'],
        ]) data-appraisal-answer-submit @disabled(! $progress['can_submit'])>
            Submit
        </button>
    </div>
</footer>
