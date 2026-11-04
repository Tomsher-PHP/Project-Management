@php
    $featureUpdate = $featureUpdate ?? null;

    $isEdit = $featureUpdate !== null;

    $currentScreenshots = old(
        'screenshots',
        $featureUpdate?->screenshots ?? []
    );
@endphp


<div class="space-y-6">

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Feature Title <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title"
            value="{{ old('title', $featureUpdate->title ?? '') }}"
            required maxlength="255"
            placeholder="e.g. Developer Notes"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Short Description
        </label>
        <textarea name="short_description" rows="2"
            placeholder="A short summary of this announcement..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('short_description', $featureUpdate->short_description ?? '') }}</textarea>
        @error('short_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Full Description
        </label>
        <textarea name="description" rows="6"
            placeholder="Explain what the feature does..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description', $featureUpdate->description ?? '') }}</textarea>
        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Instructions / How to Use
        </label>
        <textarea name="instructions" rows="6"
            placeholder="Explain how users can access and use this feature..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('instructions', $featureUpdate->instructions ?? '') }}</textarea>
        @error('instructions') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Feature Type <span class="text-red-500">*</span>
            </label>
            <select name="feature_type" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                @foreach([
                    'new_feature' => 'New Feature',
                    'improvement' => 'Improvement',
                    'important_update' => 'Important Update',
                    'bug_fix' => 'Bug Fix',
                ] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('feature_type', $featureUpdate->feature_type ?? 'new_feature') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('feature_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Module Name
            </label>
            <input type="text" name="module_name"
                value="{{ old('module_name', $featureUpdate->module_name ?? '') }}"
                placeholder="e.g. Task Management"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            @error('module_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Icon (optional)
            </label>
            <input type="text" name="icon"
                value="{{ old('icon', $featureUpdate->icon ?? '') }}"
                placeholder="e.g. fa-solid fa-list-check"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            @error('icon') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Priority <span class="text-red-500">*</span>
            </label>
            <select name="priority" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('priority', $featureUpdate->priority ?? 'normal') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Visibility
            </label>
            <select name="target_type"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="all" @selected(old('target_type', $featureUpdate->target_type ?? 'all') === 'all')>
                    All users
                </option>
                <option value="roles" @selected(old('target_type', $featureUpdate->target_type ?? 'all') === 'roles')>
                    Selected roles
                </option>
                <option value="users" @selected(old('target_type', $featureUpdate->target_type ?? 'all') === 'users')>
                    Selected users
                </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
                Targeting is intended for the login tour. The Help Center will show all published announcements to all users.
            </p>
            @error('target_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Publish Status <span class="text-red-500">*</span>
            </label>
            <select name="status" required
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('status', $featureUpdate->status ?? 'draft') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Publish Date
            </label>
            <input type="date" name="publish_at"
                value="{{ old('publish_at', isset($featureUpdate?->publish_at) ? $featureUpdate->publish_at->format('Y-m-d') : '') }}"
                class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            @error('publish_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Expiry Date
            </label>
            <input type="date" name="expires_at"
                value="{{ old('expires_at', isset($featureUpdate?->expires_at) ? $featureUpdate->expires_at->format('Y-m-d') : '') }}"
                class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            @error('expires_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Cover Image
        </label>

        @if($isEdit && $featureUpdate->cover_image)
            <div class="mb-3">
                <img src="{{ asset('storage/' . $featureUpdate->cover_image) }}"
                    alt="Current cover image"
                    class="max-h-40 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500 mt-1">Current cover image</p>
            </div>
        @endif

        <input type="file" name="cover_image" accept="image/*"
            class="w-full text-sm text-gray-600 dark:text-gray-300">
        <p class="text-xs text-gray-500 mt-1">Maximum file size: 5 MB.</p>
        @error('cover_image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Screenshots
        </label>

        @if($isEdit && count($currentScreenshots))
            <div class="flex flex-wrap gap-3 mb-3">
                @foreach($currentScreenshots as $screenshot)
                    <img src="{{ asset('storage/' . $screenshot) }}"
                        alt="Feature screenshot"
                        class="h-24 w-32 object-cover rounded-lg border border-gray-200">
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mb-2">
                Existing screenshots will be retained. Newly uploaded screenshots will be added.
            </p>
        @endif

        <input type="file" name="screenshots[]" accept="image/*" multiple
            class="w-full text-sm text-gray-600 dark:text-gray-300">
        <p class="text-xs text-gray-500 mt-1">You can select multiple images. Maximum 5 MB per image.</p>
        @error('screenshots') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('screenshots.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Video URL (optional)
        </label>
        <input type="url" name="video_url"
            value="{{ old('video_url', $featureUpdate->video_url ?? '') }}"
            placeholder="https://www.youtube.com/watch?v=..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        @error('video_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40">
        <input type="checkbox" name="is_mandatory" value="1" id="is_mandatory"
            @checked(old('is_mandatory', $featureUpdate->is_mandatory ?? false))
            class="rounded border-gray-300 text-indigo-600">
        <label for="is_mandatory" class="text-sm text-gray-700 dark:text-gray-200">
            <span class="font-semibold block">Mark as important / mandatory</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                This flag is stored for future tour behaviour. It does not currently block users from using PMS.
            </span>
        </label>
    </div>

</div>
