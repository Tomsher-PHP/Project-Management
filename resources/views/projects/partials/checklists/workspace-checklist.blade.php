@php
    $clientKey = $checklist['clientKey'] ?? '';
    $title = $checklist['title'] ?? '';
    $isExpanded = $checklist['isExpanded'] ?? false;
    $questions = $checklist['questions'] ?? [];
@endphp

<article class="rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-checklist-card data-client-key="{{ $clientKey }}">
    <div class="flex flex-col gap-3 border-b border-bgray-200 pb-4 dark:border-darkblack-400 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0 flex-1">
            <input type="text" value="{{ $title }}" class="w-full rounded-xl border {{ $titleError ? 'border-red-500' : 'border-bgray-200' }} bg-white px-4 py-3 text-sm font-semibold text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Checklist title" data-project-checklist-title-input data-checklist-index="{{ $checklistIndex }}">
            @if ($titleError)
                <p class="mt-1 text-xs text-red-500">{{ $titleError }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-700 transition duration-200 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:bg-darkblack-500" data-project-checklist-toggle data-checklist-index="{{ $checklistIndex }}" aria-label="Toggle checklist">
                <svg class="h-5 w-5 transition-transform duration-200 {{ $isExpanded ? 'rotate-180' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300" data-project-checklist-remove data-checklist-index="{{ $checklistIndex }}" aria-label="Remove checklist">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="{{ $isExpanded ? '' : 'hidden' }}">
        <div class="mt-4 space-y-3">
            @foreach ($questions as $questionIndex => $question)
                @php
                    $qError = $questionErrors[$questionIndex] ?? null;
                @endphp
                <div class="rounded-xl border border-bgray-200 bg-bgray-50/70 p-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                    <div class="flex items-start gap-3">
                        <span class="mt-2 inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-white text-xs font-semibold text-success-500 shadow-sm dark:bg-darkblack-600 dark:text-success-300">
                            {{ $questionIndex + 1 }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <input type="text" value="{{ $question['question'] ?? '' }}" class="w-full rounded-lg border {{ $qError ? 'border-red-500' : 'border-bgray-200' }} bg-white px-3 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter checklist question" data-project-checklist-question-input data-checklist-index="{{ $checklistIndex }}" data-question-index="{{ $questionIndex }}">
                            @if ($qError)
                                <p class="mt-1 text-xs text-red-500">{{ $qError }}</p>
                            @endif
                        </div>

                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-project-checklist-remove-question data-checklist-index="{{ $checklistIndex }}" data-question-index="{{ $questionIndex }}" aria-label="Remove question">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
            @if ($questionsError)
                <p class="text-xs text-red-500">{{ $questionsError }}</p>
            @endif
        </div>

        <div class="mt-4">
            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-medium text-success-500 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300" data-project-checklist-add-question data-checklist-index="{{ $checklistIndex }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Question</span>
            </button>
        </div>
    </div>
</article>
