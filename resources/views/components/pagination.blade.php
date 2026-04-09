@props(['paginator', 'perPage'])

<div class="flex flex-col lg:flex-row items-center justify-between gap-4 mt-6 @if ($paginator->total() < 5) hidden @endif">

    <!-- Per Page Selector -->
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

    <!-- Laravel Pagination Links -->
    <div>
        {{ $paginator->links() }}
    </div>

</div>
