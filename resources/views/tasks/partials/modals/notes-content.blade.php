<div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
    <div class="min-w-0 flex-1">
        <h4 class="truncate text-lg font-bold text-bgray-900 dark:text-white">
            Notes & Files
        </h4>
        <p class="mt-0.5 truncate text-xs text-bgray-600 dark:text-bgray-400">
            {{ $task->name ?? ($task->code ?? 'Untitled task') }}
        </p>
    </div>
    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-task-notes-modal-close aria-label="Close modal">
        ✕
    </button>
</div>

<div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
    @forelse ($taskNotes as $note)
        @php
            $filesCount = $note->attachments->count();
        @endphp
        <div class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-user-avatar :user="$note->addedBy" size="sm" :name="$note->addedBy?->name ?? 'Unknown User'" />
                    <div>
                        <h4 class="text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $note->addedBy?->name ?? 'Unknown User' }}
                        </h4>
                        <p class="text-xs text-bgray-600 dark:text-bgray-400">
                            {{ \App\Providers\AppServiceProvider::formatAppDateTime($note->created_at) }}
                        </p>
                    </div>
                </div>

                <span class="inline-flex w-fit rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">
                    {{ $filesCount }} File{{ $filesCount === 1 ? '' : 's' }}
                </span>
            </div>

            @if (!empty(trim(strip_tags($note->description ?? ''))))
                <div class="prose mt-3 max-w-none text-xs text-bgray-700 dark:prose-invert dark:text-bgray-300">
                    {!! $note->description !!}
                </div>
            @endif

            @if ($note->attachments->isNotEmpty())
                <div class="mt-4">
                    <h5 class="mb-2.5 text-xs font-semibold text-bgray-800 dark:text-white">Files</h5>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($note->attachments as $attachment)
                            @php
                                $ext = strtolower(pathinfo($attachment->original_name ?? '', PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
                                $attachmentUrl = rescue(fn() => $attachment->url, null, false);
                            @endphp

                            <div class="rounded-lg border border-bgray-200 p-2.5 transition hover:border-success-300 hover:bg-success-50/40 dark:border-darkblack-400 dark:hover:bg-darkblack-400">
                                <a @if ($attachmentUrl) href="{{ $attachmentUrl }}" target="_blank" @endif class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-bgray-100 dark:bg-darkblack-600">
                                        @if ($isImage && $attachmentUrl)
                                            <img src="{{ $attachmentUrl }}" alt="{{ $attachment->original_name }}" class="h-full w-full object-cover" />
                                        @else
                                            <svg width="20" height="24" viewBox="0 0 67 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.4032 0H46.9892L67 19.8123V80.625C67 83.5946 64.5796 86 61.5968 86H5.4032C2.42052 86 0 83.5946 0 80.625V5.37496C0 2.40536 2.4208 0 5.4032 0Z" fill="white" />
                                                <path d="M5.4032 0.5H46.7835L66.5 20.0208V80.625C66.5 83.3158 64.306 85.5 61.5968 85.5H5.4032C2.69405 85.5 0.5 83.3158 0.5 80.625V5.37496C0.5 2.68413 2.6943 0.5 5.4032 0.5Z" stroke="#E8E9EB" />
                                                <path d="M65.9198 20.4252H51.2864C48.6265 20.4252 46.468 18.2802 46.468 15.6368V1.0752" stroke="#E8E9EB" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-medium text-bgray-900 dark:text-white" title="{{ $attachment->original_name }}">
                                            {{ $attachment->original_name }}
                                        </p>
                                        <p class="text-[11px] text-bgray-600 dark:text-bgray-400">
                                            {{ number_format(($attachment->file_size ?? 0) / 1024, 1) }} KB
                                        </p>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-bgray-300 px-6 py-10 text-center text-sm text-gray-400 dark:border-darkblack-400">
            No notes or files found.
        </div>
    @endforelse
</div>

<div class="flex items-center justify-between border-t border-bgray-200 px-6 py-3.5 dark:border-darkblack-400">
    <a href="{{ $viewAllUrl }}" class="inline-flex items-center gap-1.5 rounded-lg bg-success-300 px-4 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
        <span>View All</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7" />
        </svg>
    </a>

    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-4 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-task-notes-modal-close>
        Close
    </button>
</div>
