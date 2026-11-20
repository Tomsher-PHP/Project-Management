<!-- REPORT NAVIGATION -->
<aside class="col-span-3 lg:col-span-3 h-fit rounded-lg bg-white p-4 dark:bg-darkblack-600">
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-bgray-800 dark:text-bgray-300">
        Reports
    </h2>

    <div class="space-y-2">
        @foreach ($reportDefinitions ?? config('report-settings.reports', []) as $key => $definition)
            @php
                $isAvailable = $definition['available'] ?? true;
                $isSelected = ($reportKey ?? 'daily_work') === $key;
                $reportUrl = isset($definition['route'])
                    ? route($definition['route'])
                    : (isset($definition['url'])
                        ? $definition['url']
                        : route('settings.daily-work-reports.index', ['report_key' => $key]));
            @endphp

            @if ($isAvailable)
                <a href="{{ $reportUrl }}" class="block rounded-lg px-3 py-3 transition
                    {{ $isSelected ? 'bg-success-300 text-white' : 'text-bgray-700 hover:bg-gray-100 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                    <span class="block text-sm font-semibold">
                        {{ $definition['label'] }}
                    </span>

                    @if (!empty($definition['description']))
                        <span class="mt-1 block text-xs {{ $isSelected ? 'text-white/80' : 'text-bgray-600 dark:text-bgray-300' }}">
                            {{ $definition['description'] }}
                        </span>
                    @endif
                </a>
            @else
                <div class="rounded-lg px-3 py-3 text-bgray-600 dark:text-bgray-300">
                    <span class="block text-sm font-semibold">
                        {{ $definition['label'] }}
                    </span>

                    <span class="mt-1 block text-xs">
                        Coming soon
                    </span>
                </div>
            @endif
        @endforeach
    </div>
</aside>

