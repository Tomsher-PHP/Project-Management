<header class="rounded-xl border border-bgray-200 bg-white px-5 py-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-6">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <div class="shrink-0">
                <x-back-button :url="$appraisalBackUrl" :use-history="true" />
            </div>
            <h1 class="min-w-0 text-xl font-bold text-bgray-900 dark:text-white sm:text-2xl">
                Answer Appraisal <span class="text-bgray-400">&bull;</span> {{ $answerData['period'] ?? '--' }}
            </h1>
        </div>

        <dl class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:gap-x-5">
            <div class="whitespace-nowrap">
                <dt class="max-w-[140px] truncate font-semibold text-bgray-600 dark:text-bgray-300" title="{{ data_get($answerData, 'assignee.name', '--') }}">{{ data_get($answerData, 'assignee.name', '--') }}</dt>
                <dd class="mt-0.5 font-bold text-bgray-900 dark:text-white">
                    <span class="text-warning-300">★</span>
                    {{ $answerData['assignee_average_rating'] !== null ? number_format((float) $answerData['assignee_average_rating'], 2) . ' / 5 (' . ($answerData['assignee_rating_count'] ?? 0) . ')' : '--' }}
                </dd>
            </div>

            @foreach ($answerData['reviewers'] ?? [] as $reviewer)
                <div class="whitespace-nowrap">
                    <dt class="max-w-[140px] truncate font-semibold text-bgray-600 dark:text-bgray-300" title="{{ $reviewer['name'] ?? '--' }}">{{ $reviewer['name'] ?? '--' }}</dt>
                    <dd class="mt-0.5 font-bold text-bgray-900 dark:text-white">
                        <span class="text-warning-300">★</span>
                        {{ $reviewer['average_rating'] !== null ? number_format((float) $reviewer['average_rating'], 2) . ' / 5 (' . ($reviewer['rating_count'] ?? 0) . ')' : '--' }}
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>

    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Assignee</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ data_get($answerData, 'assignee.name', '--') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">KPI</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['kpi_name'] ?? '--' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Status</dt>
            <dd class="mt-1 text-sm font-semibold capitalize text-bgray-900 dark:text-white">{{ $answerData['status'] ?? '--' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-600 dark:text-bgray-300">Current Stage</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['current_stage'] ?? '--' }}</dd>
        </div>
    </dl>

    <div class="mt-5 border-t border-bgray-200 pt-4 dark:border-darkblack-400">
        @include('appraisal.partials.answer-progress-stepper')
    </div>
</header>
