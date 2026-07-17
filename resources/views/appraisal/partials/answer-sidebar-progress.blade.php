<section class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <h2 class="text-sm font-bold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Overall Progress</h2>
    <div class="mt-3 flex items-center justify-between gap-3">
        <span class="text-sm font-bold text-bgray-900 dark:text-white" data-appraisal-answer-overall-count>{{ $progress['completed'] }} / {{ $progress['total'] }} Questions</span>
        <span class="text-sm font-bold text-success-500 dark:text-success-300" data-appraisal-answer-overall-percentage>{{ $progress['percentage'] }}%</span>
    </div>
    <div class="mt-2 h-1.5 w-full rounded-full bg-bgray-100 dark:bg-darkblack-500">
        <div class="h-1.5 rounded-full bg-success-300 transition-all duration-300" data-appraisal-answer-overall-bar style="width: {{ $progress['percentage'] }}%"></div>
    </div>
</section>
