@props(['action', 'id' => '', 'checkRoute' => '', 'formClass' => 'delete-form', 'ajax' => false, 'renderTarget' => '', 'renderMode' => 'replace_outer'])

<form action="{{ $action }}" method="POST" class="{{ $formClass }}" data-id="{{ $id }}" data-route="{{ $checkRoute }}" @if ($ajax) data-ajax-delete="true" @endif @if ($renderTarget) data-render-target="{{ $renderTarget }}" @endif @if ($renderMode) data-render-mode="{{ $renderMode }}" @endif>
    @csrf
    @method('DELETE')

    <button type="button" {{ $attributes->merge([
        'class' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 shadow-sm transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white dark:border-red-900/40 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-error-300',
    ]) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-current transition" viewBox="0 0 20 20" fill="currentColor">
            <path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z"></path>
        </svg>
    </button>
</form>
