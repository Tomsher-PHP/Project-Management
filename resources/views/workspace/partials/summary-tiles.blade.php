<div class="custom-scroll flex items-center gap-3 overflow-x-auto py-0 dark:bg-darkblack-700" data-workspace-summary-section data-workspace-summary-url="{{ route('user.workspace.summary') }}">
    @foreach ($workspaceSummaryTiles as $tile)
        <div class="group relative flex min-w-[160px] flex-1 items-center gap-3 shrink-0 rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] hover:shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)] dark:border-darkblack-400 dark:bg-darkblack-600" data-workspace-summary-tile>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tile['iconBg'] }} {{ $tile['accent'] }} transition-transform duration-300 group-hover:scale-110">
                @if ($tile['icon'] === 'folder')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5A2.5 2.5 0 0 1 5.5 6H9l2 2h7.5A2.5 2.5 0 0 1 21 10.5v7A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-9Z" />
                    </svg>
                @elseif ($tile['icon'] === 'grid')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 4.5h7M16.5 13v7" />
                    </svg>
                @elseif ($tile['icon'] === 'clock')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5" />
                    </svg>
                @elseif ($tile['icon'] === 'pulse')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2.5-5 4 10 2.5-5H21" />
                    </svg>
                @elseif ($tile['icon'] === 'check')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.5 10.5 15.5 16.5 8.5" />
                        <circle cx="12" cy="12" r="8" />
                    </svg>
                @elseif ($tile['icon'] === 'archive')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M6.5 7.5v9A2.5 2.5 0 0 0 9 19h6a2.5 2.5 0 0 0 2.5-2.5v-9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 5h17v2.5h-17V5ZM10 11.5h4" />
                    </svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 5.5h10A2.5 2.5 0 0 1 19.5 8v8a2.5 2.5 0 0 1-2.5 2.5H7A2.5 2.5 0 0 1 4.5 16V8A2.5 2.5 0 0 1 7 5.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 10h7M8.5 13.5h5" />
                    </svg>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-300">{{ $tile['label'] }}</h4>
                <p class="text-xl font-black leading-none {{ $tile['accent'] }}">
                    <span data-workspace-summary-count="{{ $tile['key'] }}">{{ $tile['count'] }}</span>
                </p>
            </div>
        </div>
    @endforeach
</div>
