<!-- DATA SYNC NAVIGATION -->
<aside class="col-span-3 lg:col-span-3 h-fit rounded-lg bg-white p-4 dark:bg-darkblack-600">
    <div class="mb-4 flex items-center gap-3">
        <x-back-button :url="route('settings.index')" />
        <h2 class="text-sm font-semibold uppercase tracking-wide text-bgray-800 dark:text-bgray-300">
            Data Sync {{ '- ' . $currentSection ?? 'inbound' }}
        </h2>
    </div>
    <div class="space-y-2">
        @php
            $syncSections = [
                'inbound' => [
                    'label' => 'Inbound',
                    'description' => 'Receive data from external systems.',
                    'url' => route('settings.data-sync.index'),
                ],
                'outbound' => [
                    'label' => 'Outbound',
                    'description' => 'Send data to external systems.',
                    'url' => route('settings.data-sync.outbound.connections'),
                ],
            ];
            $currentSection = $currentSection ?? 'inbound';
        @endphp

        @foreach ($syncSections as $key => $section)
            @php
                $isSelected = $currentSection === $key;
            @endphp
            <a href="{{ $section['url'] }}" class="block rounded-lg px-3 py-3 transition
                {{ $isSelected ? 'bg-success-300 text-white' : 'text-bgray-700 hover:bg-gray-100 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                <span class="block text-sm font-semibold">
                    {{ $section['label'] }}
                </span>

                @if (!empty($section['description']))
                    <span class="mt-1 block text-xs {{ $isSelected ? 'text-white/80' : 'text-bgray-600 dark:text-bgray-300' }}">
                        {{ $section['description'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</aside>
