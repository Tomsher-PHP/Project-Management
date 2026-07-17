<header class="rounded-xl border border-bgray-200 bg-white px-5 py-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-6">
    <h1 class="text-xl font-bold text-bgray-900 dark:text-white sm:text-2xl">
        Answer Appraisal <span class="text-bgray-400">&bull;</span> {{ $answerData['period'] ?? '--' }}
    </h1>

    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Assignee</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ data_get($answerData, 'assignee.name', '--') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">KPI</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['kpi_name'] ?? '--' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Status</dt>
            <dd class="mt-1 text-sm font-semibold capitalize text-bgray-900 dark:text-white">{{ $answerData['status'] ?? '--' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Current Stage</dt>
            <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['role_label'] ?? '--' }}</dd>
        </div>
    </dl>
</header>
