@php
    $comments = collect($answerData['comments'] ?? [])->keyBy('appraisal_reviewer_id');
    $reviewers = collect($answerData['reviewers'] ?? []);
@endphp

<section class="mt-8 border-t border-bgray-200 pt-6 dark:border-darkblack-400" data-appraisal-overall-comments-section>
    <h2 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">Overall Comments</h2>

    @if ($reviewers->isEmpty())
        <div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-300">
            No overall comments yet.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($reviewers as $reviewer)
                @php
                    $comment = $comments->get($reviewer['id']);
                    $editable = $answerData['role'] === 'reviewer' && (int) $answerData['current_reviewer_id'] === (int) $reviewer['id'] && !$answerData['is_submitted'];
                @endphp
                <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-bgray-900 dark:text-white">Reviewer Level {{ $reviewer['level'] }} &bull; {{ $reviewer['name'] }}</span>
                        <span class="text-xs text-bgray-600 dark:text-bgray-300">
                            @if ($comment)
                                @appDateTime($comment['created_at'])
                            @endif
                        </span>
                    </div>
                    @if ($editable)
                        <textarea class="w-full rounded-lg border border-bgray-200 p-3 text-sm focus:border-success-300 focus:ring-success-300 disabled:bg-bgray-50 disabled:opacity-60 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-600" rows="4" placeholder="No comment provided yet." data-appraisal-reviewer-comment-textarea>{{ $comment['comment'] ?? '' }}</textarea>
                    @else
                        <div class="text-sm text-bgray-900 dark:text-white whitespace-pre-line bg-bgray-50 dark:bg-darkblack-500 p-3 rounded-lg border border-bgray-200 dark:border-darkblack-400">
                            {{ $comment['comment'] ?? ($answerData['role'] === 'assignee' ? 'No comment submitted' : 'No comment provided yet.') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
