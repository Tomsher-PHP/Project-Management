<section class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <h2 class="text-sm font-bold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Categories</h2>
    <div class="appraisal-answer-categories-list mt-4 space-y-2" data-appraisal-answer-categories>
        @foreach ($categories as $category)
            @php
                $isActive = (int) $category['id'] === (int) $activeCategoryId;
                $progressText = $category['is_completed']
                    ? "✓ {$category['answered_count']} / {$category['total_questions']} Completed"
                    : "{$category['answered_count']} / {$category['total_questions']} Questions";
            @endphp
            <button type="button"
                @class([
                    'w-full rounded-lg border px-3 py-3 text-left transition',
                    'border-success-300 bg-success-50 text-success-400 dark:border-success-900/50 dark:bg-darkblack-600 dark:text-success-300' => $isActive,
                    'border-bgray-200 bg-white text-bgray-700 hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50' => ! $isActive,
                ])
                data-appraisal-answer-category-id="{{ $category['id'] }}">
                <span class="block text-sm font-bold">{{ $category['name'] }}</span>
                <span @class([
                    'mt-1 block text-xs font-medium',
                    'text-success-400 font-bold dark:text-success-300' => $category['is_completed'],
                    'opacity-80' => ! $category['is_completed'],
                ]) data-appraisal-answer-category-progress>{{ $progressText }}</span>
            </button>
        @endforeach
    </div>
</section>
