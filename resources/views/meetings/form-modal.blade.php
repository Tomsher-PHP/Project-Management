@php
    $projects = $projects ?? [];
    $meetingTypes = $meetingTypes ?? \App\Models\MeetingType::active()->orderBy('sort_order')->get();
    $meetingLocations = $meetingLocations ?? \App\Models\MeetingLocation::active()->orderBy('sort_order')->get();
    $meetingStatuses = $meetingStatuses ?? \App\Models\MeetingStatus::active()->orderBy('sort_order')->get();
    $users = $users ?? app(\App\Services\UserService::class)->getAccessibleUsers(auth()->user())->values();
    $meetingTags = $meetingTags ?? \App\Models\MeetingTag::active()->orderBy('sort_order')->get();
@endphp

<div id="meeting_modal" class="fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-4xl rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600 my-8">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 id="meeting_modal_title" class="text-lg font-bold text-bgray-900 dark:text-white">
                Add New Meeting
            </h3>
            <button type="button" data-meeting-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form id="meeting_form" method="POST" action="{{ route('meetings.store') }}" data-create-url="{{ route('meetings.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="meeting_form_method" value="POST">
            <input type="hidden" name="description" id="meeting_description_input">
            <input type="hidden" name="end_at" id="meeting_end_at">

            <div class="max-h-[75vh] overflow-y-auto p-6 space-y-6">

                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                        <div class="font-bold mb-1">Please fix the following validation errors:</div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 1. Title -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Meeting Title <x-red-star />
                    </label>
                    <input type="text" name="title" id="meeting_title" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Meeting title type here..">
                </div>

                <!-- 2. Project & Meeting Type -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Project (Optional)
                        </label>
                        <select name="project_id" id="meeting_project_id" class="tom-select-lazy w-full" data-route="{{ route('projects.search') }}" data-sort="0">
                            <option value="">Search your project here..</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" data-data='@json(['subtype' => $project->project_code ?: '--'])'>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        $defaultTypeId = $meetingTypes->firstWhere('is_default', true)?->id;
                    @endphp
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Meeting Type <x-red-star />
                        </label>
                        <select name="meeting_type_id" id="meeting_type_id" required class="tom-select w-full" data-default-id="{{ $defaultTypeId ?? '' }}">
                            <option value="">Select Type</option>
                            @foreach ($meetingTypes as $type)
                                <option value="{{ $type->id }}" {{ $defaultTypeId && $type->id == $defaultTypeId ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 3. Organizer -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Organizer <x-red-star />
                    </label>
                    <select name="organizer_id" id="meeting_organizer_id" required class="tom-select w-full" data-default-id="{{ auth()->id() }}">
                        <option value="">Select Organizer</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Participants Section -->
                <div class="rounded-xl border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30 space-y-4">
                    <h4 class="text-sm font-bold text-bgray-900 dark:text-white">
                        Participants
                    </h4>

                    <!-- Internal Participants Dropdown -->
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                            Internal Participants
                        </label>
                        <select id="meeting_internal_participants" multiple class="tom-select-multiple w-full" data-placeholder="Select internal participants...">
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- External Participants Section -->
                    <div class="pt-2 border-t border-bgray-200 dark:border-darkblack-400">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                                    External Participants
                                </label>
                            </div>
                            <button type="button" id="add_external_participant_btn" class="inline-flex items-center gap-1 rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add External
                            </button>
                        </div>

                        <div id="external_participants_container" class="space-y-3">
                            <!-- Dynamic External Participant Rows Inserted Here -->
                        </div>
                    </div>
                </div>

                <!-- 5. Start Date & Time + Duration -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Start Date & Time <x-red-star />
                        </label>
                        <input type="text" name="start_at" id="meeting_start_at" required data-enable-time="true" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD HH:MM">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Duration (Minutes) <x-red-star />
                        </label>
                        <input type="number" name="duration_minutes" id="meeting_duration_minutes" required min="1" max="1440" value="60" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="60">
                    </div>
                </div>

                <!-- 6. Location, Location Details, Meeting URL & Tags -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @php
                        $defaultLocationId = $meetingLocations->firstWhere('is_default', true)?->id;
                    @endphp
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Location
                        </label>
                        <select name="meeting_location_id" id="meeting_location_id" class="tom-select w-full" data-default-id="{{ $defaultLocationId ?? '' }}">
                            <option value="">Select Location</option>
                            @foreach ($meetingLocations as $location)
                                <option value="{{ $location->id }}" {{ $defaultLocationId && $location->id == $defaultLocationId ? 'selected' : '' }}>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Location Details (e.g. Room Name / Address)
                        </label>
                        <input type="text" name="location_details" id="meeting_location_details" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Conference Room 2B">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Meeting URL (e.g. Google Meet / Zoom Link)
                        </label>
                        <input type="url" name="url" id="meeting_url" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="https://meet.google.com/xyz">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Tags
                        </label>
                        <select name="tag_ids[]" id="meeting_tag_ids" multiple class="tom-select-multiple w-full">
                            @foreach ($meetingTags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 9. Description (Quill Editor) -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Description
                    </label>
                    <div id="meeting_description_editor" class="rounded-lg border border-bgray-300 bg-white text-sm text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white [&_.ql-editor]:min-h-[120px] [&_.ql-editor]:max-h-[180px] [&_.ql-editor]:overflow-y-auto"></div>
                    <style>
                        #meeting_description_editor .ql-editor {
                            min-height: 120px;
                            max-height: 180px;
                            overflow-y: auto;
                        }
                    </style>
                </div>

                <!-- 10. Attachments -->
                <div class="rounded-xl border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30 space-y-3">
                    <label for="meeting_attachments_input" class="block text-sm font-semibold text-bgray-900 dark:text-white">
                        Attachments
                    </label>

                    <input type="file" name="attachments[]" id="meeting_attachments_input" multiple class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-1.5 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                    <p class="text-xs text-bgray-700 dark:text-bgray-400">
                        You can attach up to 5 files at a time. Allowed types: pdf, xls, xlsx, doc, docx, ppt, pptx, jpg, jpeg, png. Max file size: 15MB.
                    </p>

                    <!-- Selected files list -->
                    <div id="selected_meeting_files" class="flex flex-wrap gap-2 pt-1"></div>

                    <!-- Existing attachments section (for Edit mode) -->
                    <div id="existing_meeting_attachments_section" class="hidden pt-3 border-t border-bgray-200 dark:border-darkblack-400">
                        <label class="mb-2 block text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                            Existing Attachments
                        </label>
                        <div id="existing_meeting_attachments_list" class="grid grid-cols-1 gap-3 sm:grid-cols-2"></div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                <button type="button" data-meeting-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                    Cancel
                </button>
                <button type="submit" id="meeting_submit_btn" class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                    Save Meeting
                </button>
            </div>
        </form>

    </div>
</div>

@include('meetings.partials.reschedule-modal')

<!-- External Participant Row Template (Hidden) -->
<template id="external_participant_row_template">
    <div class="external-participant-row rounded-lg border border-bgray-200 bg-white p-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 space-y-2" data-index="{INDEX}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">Guest #{DISPLAY_INDEX}</span>
            <button type="button" class="remove-external-participant-btn rounded p-1 text-red-500 hover:bg-red-50 dark:hover:bg-darkblack-400 transition" title="Remove Guest">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div>
                <input type="text" class="external-name-input w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Full Name *" required>
            </div>
            <div>
                <input type="email" class="external-email-input w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Email Address *" required>
            </div>
            <div>
                <input type="text" class="external-phone-input w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Phone (Optional)">
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" id="ext_send_email_{INDEX}" class="external-send-email-check rounded border-bgray-300 text-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <label for="ext_send_email_{INDEX}" class="text-xs text-bgray-600 dark:text-bgray-300 cursor-pointer">
                Send Email Notification
            </label>
        </div>
    </div>
</template>
