<tr>
    <td colspan="{{ $colSpan }}" class="px-6 py-10 text-center">
        <div class="mx-auto max-w-md rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 dark:border-darkblack-400 dark:bg-darkblack-500">
            <p class="text-base font-semibold text-bgray-900 dark:text-white">{{ $message }}</p>
            <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                {{ $subMessage ?? 'There are no records to display.' }}
            </p>
        </div>
    </td>
</tr>
