@props(['action', 'id' => '', 'checkRoute' => '', 'formClass' => 'delete-form', 'ajax' => false, 'renderTarget' => '', 'renderMode' => 'replace_outer', 'confirmTitle' => '', 'confirmMessage' => ''])

<form action="{{ $action }}" method="POST" class="{{ $formClass }}" data-id="{{ $id }}" data-route="{{ $checkRoute }}" @if ($ajax) data-ajax-delete="true" @endif @if ($renderTarget) data-render-target="{{ $renderTarget }}" @endif @if ($renderMode) data-render-mode="{{ $renderMode }}" @endif @if ($confirmTitle) data-confirm-title="{{ $confirmTitle }}" @endif @if ($confirmMessage) data-confirm-message="{{ $confirmMessage }}" @endif>
    @csrf
    @method('DELETE')

    <button type="button" {{ $attributes->merge([
        'class' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400 group',
    ]) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>
</form>
