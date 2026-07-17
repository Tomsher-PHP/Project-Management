@php
    $comments = collect($answerData['comments'] ?? []);
    $reporterComment = $comments->firstWhere('role', 'reporter');
    $managerComment = $comments->firstWhere('role', 'manager');
    $reporterEditable = $answerData['role'] === 'reporter' && ! $answerData['is_submitted'];
    $managerEditable = $answerData['role'] === 'manager' && ! $answerData['is_submitted'];
@endphp

<section class="mt-8 border-t border-bgray-200 pt-6 dark:border-darkblack-400" data-appraisal-overall-comments-section>
    <h2 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">Overall Comments</h2>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="mb-3 flex items-center justify-between gap-3">
                <span class="text-sm font-bold text-bgray-900 dark:text-white">Reporter Comment</span>
                <span class="text-xs text-bgray-500 dark:text-bgray-300" data-appraisal-reporter-comment-meta>
                    @if ($reporterComment)
                        By {{ $reporterComment['commentator_name'] }} &bull; {{ $reporterComment['created_at'] }}
                    @endif
                </span>
            </div>
            <textarea class="w-full rounded-lg border border-bgray-200 p-3 text-sm focus:border-success-300 focus:ring-success-300 disabled:bg-bgray-50 disabled:opacity-60 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-600" rows="4" placeholder="No comment provided yet." data-appraisal-reporter-comment-textarea @disabled(! $reporterEditable)>{{ $reporterComment['comment'] ?? '' }}</textarea>
        </div>

        <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="mb-3 flex items-center justify-between gap-3">
                <span class="text-sm font-bold text-bgray-900 dark:text-white">Manager Comment</span>
                <span class="text-xs text-bgray-500 dark:text-bgray-300" data-appraisal-manager-comment-meta>
                    @if ($managerComment)
                        By {{ $managerComment['commentator_name'] }} &bull; {{ $managerComment['created_at'] }}
                    @endif
                </span>
            </div>
            <textarea class="w-full rounded-lg border border-bgray-200 p-3 text-sm focus:border-success-300 focus:ring-success-300 disabled:bg-bgray-50 disabled:opacity-60 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-600" rows="4" placeholder="No comment provided yet." data-appraisal-manager-comment-textarea @disabled(! $managerEditable)>{{ $managerComment['comment'] ?? '' }}</textarea>
        </div>
    </div>
</section>
