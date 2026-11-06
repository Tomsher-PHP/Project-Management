@php
    use App\Models\Broadcast;
    $isEdit = $broadcast->exists;
    $selectedTeamIds = collect(old('team_ids', $broadcast->target_team_ids ?? []))->map(fn($id) => (string) $id)->all();
    $selectedUserIds = collect(old('user_ids', $isEdit && $broadcast->audience_type === Broadcast::AUDIENCE_USERS ? $broadcast->recipients->pluck('id')->all() : []))->map(fn($id) => (string) $id)->all();
    $dateRangeValue = old('date_range', ($broadcast->start_date && $broadcast->end_date
        ? ($broadcast->start_date->format('Y-m-d') === $broadcast->end_date->format('Y-m-d')
            ? $broadcast->start_date->format('Y-m-d')
            : $broadcast->start_date->format('Y-m-d') . ' to ' . $broadcast->end_date->format('Y-m-d'))
        : ($broadcast->start_date ? $broadcast->start_date->format('Y-m-d') : '')));
@endphp

<div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-2xl dark:border-darkblack-400 dark:bg-darkblack-600">
    <form data-broadcast-form action="{{ $isEdit ? route('broadcasts.update', $broadcast) : route('broadcasts.store') }}" method="POST" enctype="multipart/form-data" data-mode="{{ $isEdit ? 'edit' : 'create' }}">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="status" value="{{ $broadcast->status ?? Broadcast::STATUS_DRAFT }}" data-broadcast-status-input>
        <input type="hidden" name="content" value="" data-broadcast-content-input>

        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <div>
                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">{{ $isEdit ? 'Edit Broadcast' : 'Add Broadcast' }}</h2>
                <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">{{ $isEdit ? 'Update broadcast details and audience.' : 'Create a draft or publish it immediately.' }}</p>
            </div>
            <button type="button" data-broadcast-modal-close class="rounded-md p-2 text-bgray-600 hover:bg-bgray-100 dark:text-bgray-300 dark:hover:bg-darkblack-400" aria-label="Close modal">✕</button>
        </div>

        <div class="max-h-[72vh] space-y-7 overflow-y-auto px-6 py-5">
            <section>
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Broadcast Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="broadcast_title" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Title <span class="text-red-500">*</span></label>
                        <input id="broadcast_title" name="title" type="text" maxlength="255" value="{{ old('title', $broadcast->title) }}" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required @if(!$isEdit) autofocus @endif>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="title"></span>
                    </div>
                    <div>
                        <label for="broadcast_category" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Category <span class="text-red-500">*</span></label>
                        <select id="broadcast_category" name="broadcast_category_id" class="tom-select w-full" data-sort="0" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('broadcast_category_id', $broadcast->broadcast_category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="broadcast_category_id"></span>
                    </div>
                    <div>
                        <label for="broadcast_priority" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Priority <span class="text-red-500">*</span></label>
                        <select id="broadcast_priority" name="priority" class="tom-select w-full" data-sort="0" required>
                            @foreach ($priorityOptions as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('priority', $broadcast->priority ?? Broadcast::PRIORITY_NORMAL) === (int) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="priority"></span>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Content <span class="text-red-500">*</span></label>
                        <div class="!h-[140px] rounded-lg bg-white text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-broadcast-editor>{!! $broadcast->content !!}</div>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="content"></span>
                    </div>
                </div>
            </section>

            <section class="border-t border-bgray-200 pt-6 dark:border-darkblack-400">
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Target Audience</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="broadcast_audience_type" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Audience Type <span class="text-red-500">*</span></label>
                        <select id="broadcast_audience_type" name="audience_type" class="tom-select w-full" data-sort="0" data-broadcast-audience required>
                            @foreach ($audienceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience_type', $broadcast->audience_type ?? Broadcast::AUDIENCE_ALL) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="audience_type"></span>
                    </div>
                    <div class="{{ old('audience_type', $broadcast->audience_type) === Broadcast::AUDIENCE_TEAMS ? '' : 'hidden' }}" data-broadcast-team-field>
                        <label for="broadcast_team_ids" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Select Teams <span class="text-red-500">*</span></label>
                        <select id="broadcast_team_ids" name="team_ids[]" class="tom-select-multiple w-full" multiple data-sort="0" data-selected-ids='@json($selectedTeamIds)'>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @selected(in_array((string) $team->id, $selectedTeamIds, true))>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="team_ids"></span>
                    </div>
                    <div class="{{ old('audience_type', $broadcast->audience_type) === Broadcast::AUDIENCE_USERS ? '' : 'hidden' }}" data-broadcast-user-field>
                        <label for="broadcast_user_ids" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Select Users <span class="text-red-500">*</span></label>
                        <select id="broadcast_user_ids" name="user_ids[]" class="tom-select-multiple w-full" multiple data-sort="0" data-render-subtype="true" data-selected-ids='@json($selectedUserIds)'>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" data-subtype="{{ $user->email }}" @selected(in_array((string) $user->id, $selectedUserIds, true))>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="user_ids"></span>
                    </div>
                </div>
            </section>

            <section class="border-t border-bgray-200 pt-6 dark:border-darkblack-400">
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Visibility & Attachments</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="broadcast_date_range" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Date Range</label>
                        <input id="broadcast_date_range" name="date_range" type="text" value="{{ $dateRangeValue }}" class="datepicker w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" placeholder="YYYY-MM-DD to YYYY-MM-DD" autocomplete="off">
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="date_range"></span>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="start_date"></span>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="end_date"></span>
                    </div>
                    <div class="md:col-span-2">
                        <label for="broadcast_attachments" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Files
                        </label>
                        <input type="file" id="broadcast_attachments" name="attachments[]" multiple class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-2 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" data-broadcast-attachments-input>
                        <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">
                            You can attach multiple files (up to 5). Allowed types: pdf, xls, xlsx, doc, docx, ppt, pptx, jpg, jpeg, png. Max file size: 15MB each.
                        </p>
                        <div id="selected-broadcast-files" class="mt-3 flex flex-wrap gap-2" data-selected-broadcast-files></div>
                        <span class="text-xs text-red-500 mt-1 hidden error-text block" data-error-for="attachments"></span>

                        @if ($isEdit && $broadcast->attachments->isNotEmpty())
                            <div class="pt-3 mt-4 border-t border-bgray-200 dark:border-darkblack-400" data-existing-attachments>
                                <label class="mb-2 block text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                                    Existing Attachments
                                </label>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach ($broadcast->attachments as $attachment)
                                        <div class="flex items-center justify-between gap-2 rounded-lg border border-bgray-200 bg-white p-2.5 dark:border-darkblack-400 dark:bg-darkblack-500" data-attachment-item>
                                            <a href="{{ route('broadcasts.attachments.download', [$broadcast, $attachment]) }}" target="_blank" class="flex items-center gap-2.5 min-w-0 flex-1 hover:text-success-400 transition" title="{{ $attachment->original_name }}">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-bgray-100 dark:bg-darkblack-600 overflow-hidden">
                                                    <svg class="h-5 w-5 text-bgray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.5L14.5 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white">{{ $attachment->original_name ?: $attachment->file_name }}</p>
                                                    <p class="text-[11px] text-bgray-500 dark:text-bgray-400">{{ number_format($attachment->file_size / 1024, 1) }} KB</p>
                                                </div>
                                            </a>
                                            <button type="button" data-broadcast-attachment-delete data-url="{{ route('broadcasts.attachments.destroy', [$broadcast, $attachment]) }}" class="shrink-0 rounded p-1.5 text-bgray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition" title="Remove attachment" aria-label="Remove attachment">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <button type="button" data-broadcast-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">Cancel</button>
            <button type="submit" data-broadcast-submit data-submit-status="{{ $isEdit ? $broadcast->status : Broadcast::STATUS_DRAFT }}" class="rounded-lg border border-success-300 bg-white px-5 py-2 text-sm font-semibold text-success-300 transition hover:bg-success-50 dark:bg-darkblack-500 dark:hover:bg-darkblack-400">
                <span data-submit-label>{{ $isEdit ? 'Update' : 'Save Draft' }}</span>
            </button>
            @if (!$isEdit)
                @can('broadcast.status_change')
                    <button type="submit" data-broadcast-submit data-submit-status="{{ Broadcast::STATUS_PUBLISHED }}" class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                        <span data-submit-label>Publish</span>
                    </button>
                @endcan
            @endif
        </div>
    </form>
</div>
