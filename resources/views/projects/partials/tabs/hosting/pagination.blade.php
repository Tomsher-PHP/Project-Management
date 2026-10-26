@php
    $firstItem = $paginator->firstItem() ?? 0;
    $lastItem = $paginator->lastItem() ?? 0;
    $total = $paginator->total();
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
@endphp

<div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400" data-project-hosting-pagination>
    <!-- Results info -->
    <p class="text-xs text-bgray-600 dark:text-bgray-300">
        Showing <span class="font-semibold text-bgray-900 dark:text-white">{{ $firstItem }}</span> to <span class="font-semibold text-bgray-900 dark:text-white">{{ $lastItem }}</span> of <span class="font-semibold text-bgray-900 dark:text-white">{{ $total }}</span> records
    </p>

    <!-- Page navigation -->
    @if ($paginator->hasPages())
        <nav class="inline-flex items-center gap-1" aria-label="Pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 text-bgray-400 opacity-50 dark:border-darkblack-400 dark:text-bgray-500 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <button type="button" data-page="{{ $currentPage - 1 }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Previous Page">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @endif

            {{-- Numbered Page Links --}}
            @php
                if ($lastPage <= 7) {
                    $pages = range(1, $lastPage);
                } elseif ($currentPage <= 4) {
                    $pages = [1, 2, 3, 4, 5, '...', $lastPage];
                } elseif ($currentPage >= $lastPage - 3) {
                    $pages = [1, '...', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                } else {
                    $pages = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $lastPage];
                }
            @endphp

            @foreach ($pages as $p)
                @if ($p === '...')
                    <span class="inline-flex h-8 w-8 items-center justify-center text-xs text-bgray-500 dark:text-bgray-400">...</span>
                @elseif ($p === $currentPage)
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-success-300 text-xs font-semibold text-white shadow-sm">
                        {{ $p }}
                    </span>
                @else
                    <button type="button" data-page="{{ $p }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 bg-white text-xs font-semibold text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                        {{ $p }}
                    </button>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button type="button" data-page="{{ $currentPage + 1 }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Next Page">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 text-bgray-400 opacity-50 dark:border-darkblack-400 dark:text-bgray-500 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </nav>
    @endif
</div>
