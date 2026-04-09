@props([
    'rows' => collect(),
    'event' => 'updated',
])

@php
    $rows = collect($rows);

    $singleValueLabel = match ($event) {
        'created' => 'New Value',
        'deleted' => 'Previous Value',
        'restored' => 'Current Value',
        default => 'Value',
    };
@endphp

@if ($rows->isNotEmpty())
    <div class="space-y-2.5">
        @foreach ($rows as $row)
            @php
                $label = $row['label'] ?? $row['field'] ?? '--';
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

            <div class="rounded-xl border border-bgray-200 p-3.5 dark:border-darkblack-400">
                <p class="mb-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">
                    {{ $label }}
                </p>

                @if ($event === 'updated')
                    <div class="grid gap-2.5 md:grid-cols-2">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 px-3 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">
                                Old Value
                            </p>
                            <div class="text-sm font-medium text-bgray-900 dark:text-white">
                                <x-activity-log.value :value="$oldValue" :type="$oldType" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-success-200 bg-success-50 px-3 py-2.5 dark:border-success-900/30 dark:bg-success-900/10">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-success-400">
                                New Value
                            </p>
                            <div class="text-sm font-medium text-bgray-900 dark:text-white">
                                <x-activity-log.value :value="$newValue" :type="$newType" />
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg bg-bgray-50 px-3 py-2.5 text-sm font-medium text-bgray-900 dark:bg-darkblack-500 dark:text-white">
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">
                            {{ $singleValueLabel }}
                        </p>
                        <x-activity-log.value :value="$singleValue" :type="$singleType" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
