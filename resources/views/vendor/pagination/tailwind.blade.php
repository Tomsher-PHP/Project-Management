@if ($paginator->hasPages())
    <div class="flex items-center justify-between mt-6">

        {{-- Previous Button --}}
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                ‹
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-indigo-600">
                ‹
            </a>
        @endif

        {{-- Page Numbers --}}
        <div class="flex items-center space-x-2">
            @foreach ($elements as $element)
                {{-- "..." Separator --}}
                @if (is_string($element))
                    <span class="px-3 py-2 text-gray-400">
                        {{ $element }}
                    </span>
                @endif

                {{-- Page Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-indigo-50 rounded-lg">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Button --}}
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
