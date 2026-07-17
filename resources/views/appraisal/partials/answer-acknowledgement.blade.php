@if (data_get($answerData, 'acknowledgement.required'))
    <section class="mb-6 rounded-xl border border-success-200 bg-success-50 p-5 dark:border-success-900/50 dark:bg-darkblack-500" data-appraisal-acknowledgement-section>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.08em] text-success-400">Acknowledgement Required</p>
                <h2 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white">
                    Reviewer Level {{ data_get($answerData, 'acknowledgement.level') }} Review
                </h2>
                <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">{{ data_get($answerData, 'acknowledgement.reviewer_name') }}</p>
            </div>
            <span class="text-xs font-medium text-bgray-600 dark:text-bgray-300">Submitted {{ data_get($answerData, 'acknowledgement.submitted_at') }}</span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                <p class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Reviewer Rating Summary</p>
                <p class="mt-2 text-xl font-bold text-bgray-900 dark:text-white">
                    @if (data_get($answerData, 'acknowledgement.average_rating') !== null)
                        {{ number_format((float) data_get($answerData, 'acknowledgement.average_rating'), 2) }} / 5
                    @else
                        &mdash;
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                <p class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Reviewer Overall Comment</p>
                <p class="mt-2 whitespace-pre-line text-sm text-bgray-700 dark:text-bgray-100">{{ data_get($answerData, 'acknowledgement.overall_comment') ?: 'No overall comment provided.' }}</p>
            </div>
        </div>

        <div class="mt-5">
            <label for="appraisal-acknowledgement-remark" class="text-sm font-semibold text-bgray-900 dark:text-white">Acknowledgement Remark</label>
            <textarea id="appraisal-acknowledgement-remark" rows="4" class="mt-2 w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm focus:border-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter an optional acknowledgement remark" data-appraisal-acknowledgement-remark></textarea>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            <a href="{{ route('appraisal.index') }}" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50">Cancel</a>
            <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-acknowledge-review data-appraisal-reviewer-id="{{ data_get($answerData, 'acknowledgement.appraisal_reviewer_id') }}">Acknowledge</button>
        </div>
    </section>
@endif
