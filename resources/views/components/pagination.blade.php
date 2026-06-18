@props(['paginator', 'perPage'])

@php
    $firstItem = $paginator->firstItem() ?? 0;
    $lastItem = $paginator->lastItem() ?? 0;
    $total = $paginator->total();
@endphp

<div class="flex flex-col lg:flex-row items-center justify-between gap-4 mt-6">

    <!-- Per Page Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-600">
                Show result:
            </span>

            <form method="GET">
                <!-- Preserve other query parameters -->
                @foreach (request()->query() as $key => $value)
                    @if ($key !== 'per_page' && $key !== 'page')
                        @if (is_array($value))
                            @foreach ($value as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endif
                @endforeach

                <select name="per_page" onchange="this.form.submit()" class="appearance-none rounded-lg border border-gray-300 px-3 pr-10 py-2 text-sm focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-darkblack-500 dark:text-white w-full">

                    @foreach ([5, 10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach

                </select>
            </form>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ $firstItem }} to {{ $lastItem }} of {{ $total }} results
        </p>
    </div>

    <!-- Laravel Pagination Links -->
    @if ($paginator->hasPages())
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();

            if ($lastPage <= 5) {
                $visiblePages = range(1, $lastPage);
            } elseif ($currentPage === 1) {
                $visiblePages = [1, 2, 3, $lastPage];
            } elseif ($currentPage === 2) {
                $visiblePages = [1, 2, 3, 4, $lastPage];
            } elseif ($currentPage >= $lastPage - 1) {
                $visiblePages = [1, $lastPage - 2, $lastPage - 1, $lastPage];
            } else {
                $visiblePages = [1, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage];
            }

            $visiblePages = collect($visiblePages)->filter(fn($page) => $page >= 1 && $page <= $lastPage)->unique()->values();
        @endphp

        <div class="flex items-center justify-between mt-6">

            <!-- Previous Button -->
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                    ‹
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-indigo-600">
                    ‹
                </a>
            @endif

            <!-- Page Numbers -->
            <div class="flex items-center space-x-2">
                @php
                    $previousVisiblePage = null;
                @endphp

                @foreach ($visiblePages as $page)
                    @if ($previousVisiblePage !== null && $page - $previousVisiblePage > 1)
                        <span class="px-3 py-2 text-gray-400">
                            ...
                        </span>
                    @endif

                    @if ($page === $currentPage)
                        <span class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-indigo-50 rounded-lg">
                            {{ $page }}
                        </a>
                    @endif

                    @php
                        $previousVisiblePage = $page;
                    @endphp
                @endforeach
            </div>

            <!-- Next Button -->
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-indigo-600">
                    ›
                </a>
            @else
                <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                    ›
                </span>
            @endif
        </div>
    @endif

</div>
