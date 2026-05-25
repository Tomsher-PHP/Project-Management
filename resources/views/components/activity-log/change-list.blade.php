@props([
    'rows' => collect(),
    'event' => 'updated',
])

@php
    $rows = collect($rows);
    $dotClasses = match ($event) {
        'created' => 'bg-success-300',
        'deleted' => 'bg-red-400',
        'restored' => 'bg-warning-400',
        default => 'bg-sky-400',
    };
@endphp

@if ($rows->isNotEmpty())
    <div class="space-y-4">
        @foreach ($rows as $row)
            @php
                $label = $row['label'] ?? ($row['field'] ?? '--');
                $oldValue = $row['old']['value'] ?? null;
                $oldType = $row['old']['type'] ?? null;
                $newValue = $row['new']['value'] ?? null;
                $newType = $row['new']['type'] ?? null;

                $singleValue = match ($event) {
                    'deleted' => $oldValue ?? $newValue,
                    default => $newValue ?? $oldValue,
                };

                $singleType = match ($event) {
                    'deleted' => $oldType ?? $newType,
                    default => $newType ?? $oldType,
                };
            @endphp

            <article class="flex items-start gap-3 rounded-xl bg-white dark:bg-darkblack-500 border-b pb-4 dark:border-darkblack-400">
                <span class="mt-2 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $dotClasses }}"></span>

                <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                        {{ $label }}
                    </p>

                    @if ($event === 'updated')
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-bgray-700 dark:text-bgray-300">
                            <span class="inline-flex items-center rounded bg-white px-2 py-0.5 dark:bg-darkblack-600 text-bgray-600 dark:text-white">
                                <x-activity-log.value :value="$oldValue" :type="$oldType" />
                            </span>
                            <span class="text-bgray-400 dark:text-bgray-700">&rarr;</span>
                            <span class="inline-flex items-center rounded bg-success-50 px-2 py-0.5 dark:bg-success-900/10">
                                <x-activity-log.value :value="$newValue" :type="$newType" />
                            </span>
                        </div>
                    @else
                        <div class="mt-2 inline-flex items-center rounded bg-white px-2 py-0.5 text-sm text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">
                            <x-activity-log.value :value="$singleValue" :type="$singleType" />
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
