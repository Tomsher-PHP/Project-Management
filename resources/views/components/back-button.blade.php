@php
    $defaultUrl = request()->headers->get('referer');

    if (!$defaultUrl) {
        $user = auth()->user();

        $defaultUrl = $user && ($user->is_super_admin || $user->can('dashboard.view')) ? route('dashboard') : route('user.workspace');
    }
    $classes = 'inline-flex items-center gap-1 rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300';
@endphp

@props([
    'url' => $defaultUrl,
    'label' => null,
])
<button class="{{ $classes }}" onclick="window.location='{{ $url }}'">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
    </svg>
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</button>
