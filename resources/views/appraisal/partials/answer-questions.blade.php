<div>
    <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Questions</p>
    <h2 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-answer-category-title>{{ $categories->first()['name'] ?? '' }}</h2>
</div>

<div class="mt-5" data-appraisal-answer-questions>
    @forelse ($categories as $category)
        <div @class(['space-y-2', 'hidden' => (int) $category['id'] !== (int) $activeCategoryId]) data-appraisal-answer-category-panel data-category-id="{{ $category['id'] }}" data-category-name="{{ $category['name'] }}">
            @forelse ($category['questions'] as $index => $question)
                @include('appraisal.partials.answer-question-card', [
                    'question' => $question,
                    'index' => $index,
                ])
            @empty
                <div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-300">No questions found.</div>
            @endforelse
        </div>
    @empty
        <div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-300">No questions found.</div>
    @endforelse
</div>
