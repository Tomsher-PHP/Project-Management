@php
    use App\Models\Bulletin;
    $isEdit = $bulletin->exists;
    $selectedTeamIds = collect(old('team_ids', $bulletin->target_team_ids ?? []))->map(fn($id) => (string) $id)->all();
    $selectedUserIds = collect(old('user_ids', $isEdit && $bulletin->audience_type === Bulletin::AUDIENCE_USERS ? $bulletin->recipients->pluck('id') : []))->map(fn($id) => (string) $id)->all();
@endphp

<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-2xl dark:border-darkblack-400 dark:bg-darkblack-600">
    <form data-bulletin-form action="{{ $isEdit ? route('bulletins.update', $bulletin) : route('bulletins.store') }}" method="POST" enctype="multipart/form-data" data-mode="{{ $isEdit ? 'edit' : 'create' }}">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="status" value="{{ $bulletin->status ?? Bulletin::STATUS_DRAFT }}" data-bulletin-status-input>
        <input type="hidden" name="content" value="" data-bulletin-content-input>

        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <div>
                <h2 class="text-xl font-bold text-bgray-900 dark:text-white">{{ $isEdit ? 'Edit Bulletin' : 'Add Bulletin' }}</h2>
                <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">{{ $isEdit ? 'Update bulletin details and audience.' : 'Create a draft or publish it immediately.' }}</p>
            </div>
            <button type="button" data-bulletin-modal-close class="rounded-md p-2 text-bgray-600 hover:bg-bgray-100 dark:text-bgray-300 dark:hover:bg-darkblack-400" aria-label="Close modal">✕</button>
        </div>

        <div class="max-h-[72vh] space-y-7 overflow-y-auto px-6 py-5">
            <div class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" data-bulletin-errors>
                <p class="font-semibold">Please correct the following errors:</p>
                <p class="mt-1 whitespace-pre-line" data-bulletin-error-list></p>
            </div>

            <section>
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Bulletin Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="bulletin_title" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Title <span class="text-red-500">*</span></label>
                        <input id="bulletin_title" name="title" type="text" maxlength="255" value="{{ old('title', $bulletin->title) }}" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
                    </div>
                    <div>
                        <label for="bulletin_category" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Category <span class="text-red-500">*</span></label>
                        <select id="bulletin_category" name="bulletin_category_id" class="tom-select w-full" data-sort="0" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('bulletin_category_id', $bulletin->bulletin_category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bulletin_priority" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Priority <span class="text-red-500">*</span></label>
                        <select id="bulletin_priority" name="priority" class="tom-select w-full" data-sort="0" required>
                            @foreach ($priorityOptions as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('priority', $bulletin->priority ?? Bulletin::PRIORITY_NORMAL) === (int) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Content</label>
                        <div class="!h-[140px] rounded-lg bg-white text-bgray-900 dark:bg-darkblack-500 dark:text-white" data-bulletin-editor>{!! $bulletin->content !!}</div>
                    </div>
                </div>
            </section>

            <section class="border-t border-bgray-200 pt-6 dark:border-darkblack-400">
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Target Audience</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="bulletin_audience_type" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Audience Type <span class="text-red-500">*</span></label>
                        <select id="bulletin_audience_type" name="audience_type" class="tom-select w-full" data-sort="0" data-bulletin-audience required>
                            @foreach ($audienceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience_type', $bulletin->audience_type ?? Bulletin::AUDIENCE_ALL) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="{{ old('audience_type', $bulletin->audience_type) === Bulletin::AUDIENCE_TEAMS ? '' : 'hidden' }}" data-bulletin-team-field>
                        <label for="bulletin_team_ids" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Select Teams <span class="text-red-500">*</span></label>
                        <select id="bulletin_team_ids" name="team_ids[]" class="tom-select w-full" multiple data-sort="0">
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @selected(in_array((string) $team->id, $selectedTeamIds, true))>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="{{ old('audience_type', $bulletin->audience_type) === Bulletin::AUDIENCE_USERS ? '' : 'hidden' }}" data-bulletin-user-field>
                        <label for="bulletin_user_ids" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Select Users <span class="text-red-500">*</span></label>
                        <select id="bulletin_user_ids" name="user_ids[]" class="tom-select w-full" multiple data-sort="0" data-render-subtype="true">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" data-subtype="{{ $user->email }}" @selected(in_array((string) $user->id, $selectedUserIds, true))>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="border-t border-bgray-200 pt-6 dark:border-darkblack-400">
                <h3 class="mb-4 text-base font-semibold text-bgray-900 dark:text-white">Visibility & Attachments</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="bulletin_start_date" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Start Date</label>
                        <input id="bulletin_start_date" name="start_date" type="text" value="{{ old('start_date', $bulletin->start_date?->format('Y-m-d')) }}" class="datepicker w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="Y-m-d" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label for="bulletin_end_date" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">End Date</label>
                        <input id="bulletin_end_date" name="end_date" type="text" value="{{ old('end_date', $bulletin->end_date?->format('Y-m-d')) }}" class="datepicker w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="Y-m-d" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="md:col-span-2">
                        <label for="bulletin_attachments" class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">Attachments</label>
                        <input id="bulletin_attachments" name="attachments[]" type="file" multiple accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-1.5 file:font-medium file:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">Up to 5 files, 15 MB each.</p>
                    </div>
                    @if ($isEdit && $bulletin->attachments->isNotEmpty())
                        <div class="md:col-span-2" data-existing-attachments>
                            <p class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Existing Attachments</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($bulletin->attachments as $attachment)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-bgray-200 px-3 py-2 dark:border-darkblack-400" data-attachment-item>
                                        <a href="{{ route('bulletins.attachments.download', [$bulletin, $attachment]) }}" class="min-w-0 truncate text-sm font-medium text-success-500 hover:underline" title="{{ $attachment->original_name }}">{{ $attachment->original_name ?: $attachment->file_name }}</a>
                                        <button type="button" data-bulletin-attachment-delete data-url="{{ route('bulletins.attachments.destroy', [$bulletin, $attachment]) }}" class="shrink-0 rounded p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="Remove attachment" aria-label="Remove attachment">✕</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <button type="button" data-bulletin-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-400">Cancel</button>
            <button type="submit" data-bulletin-submit data-submit-status="{{ $isEdit ? $bulletin->status : Bulletin::STATUS_DRAFT }}" class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                <span data-submit-label>{{ $isEdit ? 'Update' : 'Save Draft' }}</span>
            </button>
            @if (!$isEdit)
                @can('bulletin.status_change')
                    <button type="submit" data-bulletin-submit data-submit-status="{{ Bulletin::STATUS_PUBLISHED }}" class="rounded-lg border border-success-300 bg-white px-5 py-2 text-sm font-semibold text-success-500 transition hover:bg-success-50 dark:bg-darkblack-500 dark:hover:bg-darkblack-400">
                        <span data-submit-label>Publish</span>
                    </button>
                @endcan
            @endif
        </div>
    </form>
</div>
