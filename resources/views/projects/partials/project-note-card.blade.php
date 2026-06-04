<article class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-note-id="{{ $note->id }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-3">
            <x-user-avatar :user="$note->addedBy" size="sm" :name="$note->addedBy?->name ?? 'Unknown User'" />
            <div>
                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                    {{ $note->addedBy?->name ?? 'Unknown User' }}
                </h4>
                <p class="text-sm text-bgray-700 dark:text-bgray-300">
                    @appDateTime($note->created_at)
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex w-fit rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-400 note-attachments-count">
                {{ $note->attachments->count() }} File{{ $note->attachments->count() === 1 ? '' : 's' }}
            </span>

            @if ($canRemove)
                <button type="button" class="delete-project-note rounded-lg border border-red-200 px-3 py-1 text-xs font-medium text-red-500 transition hover:bg-red-50" data-note-id="{{ $note->id }}">
                    Delete
                </button>
            @endif
        </div>
    </div>

    <div class="prose mt-4 max-w-none text-sm text-bgray-700 dark:text-bgray-300">
        {!! $note->description !!}
    </div>

    @if ($note->attachments->isNotEmpty())
        <div class="mt-5 note-attachments-section">
            <h5 class="text-sm font-semibold text-bgray-800 dark:text-white">Files</h5>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($note->attachments as $attachment)
                    @php
                        $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
                        $attachmentUrl = rescue(fn() => $attachment->url, null, false);
                    @endphp

                    <div class="rounded-lg border border-bgray-200 p-3 transition hover:border-success-300 hover:bg-success-50/40 dark:border-darkblack-400 dark:hover:bg-darkblack-400" data-note-attachment-id="{{ $attachment->id }}">
                        <a @if ($attachmentUrl) href="{{ $attachmentUrl }}" target="_blank" @endif class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-bgray-100 dark:bg-darkblack-600">
                                @if ($isImage && $attachmentUrl)
                                    <img src="{{ $attachmentUrl }}" alt="{{ $attachment->original_name }}" class="h-full w-full object-cover" />
                                @else
                                    <svg width="22" height="28" viewBox="0 0 67 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.4032 0H46.9892L67 19.8123V80.625C67 83.5946 64.5796 86 61.5968 86H5.4032C2.42052 86 0 83.5946 0 80.625V5.37496C0 2.40536 2.4208 0 5.4032 0Z" fill="white" />
                                        <path d="M5.4032 0.5H46.7835L66.5 20.0208V80.625C66.5 83.3158 64.306 85.5 61.5968 85.5H5.4032C2.69405 85.5 0.5 83.3158 0.5 80.625V5.37496C0.5 2.68413 2.6943 0.5 5.4032 0.5Z" stroke="#E8E9EB" />
                                        <path d="M65.9198 20.4252H51.2864C48.6265 20.4252 46.468 18.2802 46.468 15.6368V1.0752" stroke="#E8E9EB" />
                                    </svg>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-bgray-900 dark:text-white">
                                    {{ $attachment->original_name }}
                                </p>
                                <p class="text-xs text-bgray-700 dark:text-bgray-300">
                                    {{ number_format($attachment->file_size / 1024, 1) }} KB
                                </p>
                            </div>
                        </a>

                        @if ($canRemove)
                            <button type="button" class="delete-project-note-file mt-3 text-xs font-medium text-error-300 hover:underline" data-note-id="{{ $note->id }}" data-attachment-id="{{ $attachment->id }}">
                                Remove File
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</article>
