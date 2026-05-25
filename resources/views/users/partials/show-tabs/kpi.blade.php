<div id="kpiTab" class="tab-pane">
    <h3 class="mb-5 text-2xl font-bold text-bgray-900 dark:text-white">
        KPI Details
    </h3>

    <div class="rounded-xl bg-white p-6 shadow dark:bg-darkblack-500">
        @forelse($user->kpis as $kpi)
            <h4 class="mb-4 mt-4 text-lg font-bold text-bgray-800 dark:text-white">
                {{ $kpi->name }}
            </h4>

            <div class="mb-6 space-y-5 text-sm text-bgray-600 dark:text-darkblack-300">
                @foreach (preg_split('/\r\n\r\n|\n\n/', $kpi->description) as $section)
                    @php
                        $lines = preg_split('/\r\n|\r|\n/', trim($section));
                        $heading = array_shift($lines);
                    @endphp

                    @if ($heading)
                        <div>
                            <h5 class="mb-2 font-semibold text-bgray-800 dark:text-white">
                                {{ $heading }}
                            </h5>

                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($lines as $line)
                                    @if (trim($line))
                                        <li>{{ $line }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>

            <hr class="my-4 border-gray-200 dark:border-darkblack-400">
        @empty
            <div class="text-bgray-700 dark:text-darkblack-300">
                No KPIs assigned to this user.
            </div>
        @endforelse
    </div>
</div>
