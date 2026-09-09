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
        <form id="meeting_form" method="POST" action="{{ route('meetings.store') }}" data-create-url="{{ route('meetings.store') }}">
            @csrf
            <input type="hidden" name="_method" id="meeting_form_method" value="POST">
            <input type="hidden" name="description" id="meeting_description_input">

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

                <!-- Title -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Meeting Title <x-red-star />
                    </label>
                    <input type="text" name="title" id="meeting_title" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. Weekly Sprint Planning">
                </div>

                <!-- Grid Row 1: Project & Meeting Type -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Project (Optional)
                        </label>
                        <select name="project_id" id="meeting_project_id" class="tom-select w-full">
                            <option value="">Select Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Meeting Type <x-red-star />
                        </label>
                        <select name="meeting_type_id" id="meeting_type_id" required class="tom-select w-full">
                            <option value="">Select Type</option>
                            @foreach ($meetingTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Grid Row 2: Location, Status & Organizer -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Location
                        </label>
                        <select name="meeting_location_id" id="meeting_location_id" class="tom-select w-full">
                            <option value="">Select Location</option>
                            @foreach ($meetingLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Status
                        </label>
                        <select name="meeting_status_id" id="meeting_status_id" class="tom-select w-full">
                            @foreach ($meetingStatuses as $status)
                                <option value="{{ $status->id }}" {{ $status->id == $defaultStatusId ? 'selected' : '' }}>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Organizer <x-red-star />
                        </label>
                        <select name="organizer_id" id="meeting_organizer_id" required class="tom-select w-full">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Grid Row 3: Start & End Date Time -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Start Date & Time <x-red-star />
                        </label>
                        <input type="text" name="start_at" id="meeting_start_at" required data-enable-time="true" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD HH:MM">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            End Date & Time <x-red-star />
                        </label>
                        <input type="text" name="end_at" id="meeting_end_at" required data-enable-time="true" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD HH:MM">
                    </div>
                </div>

                <!-- Grid Row 4: URL & Location Details -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Meeting URL (e.g. Google Meet / Zoom Link)
                        </label>
                        <input type="url" name="url" id="meeting_url" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="https://meet.google.com/xyz">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Location Details (e.g. Room Name / Address)
                        </label>
                        <input type="text" name="location_details" id="meeting_location_details" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Conference Room 2B">
                    </div>
                </div>

                <!-- Tags -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Tags
                    </label>
                    <select name="tag_ids[]" id="meeting_tag_ids" multiple class="tom-select w-full">
                        @foreach ($meetingTags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Participants Section -->
                <div class="rounded-xl border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-bgray-900 dark:text-white">
                            Participants
                        </h4>
                        <button type="button" id="add_participant_btn" class="inline-flex items-center gap-1 rounded-lg bg-success-300 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-success-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Participant
                        </button>
                    </div>

                    <div id="meeting_participants_container" class="space-y-3">
                        <!-- Dynamic Participant Rows Inserted Here via JS -->
                    </div>
                </div>

                <!-- Description (Quill Editor) -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Description
                    </label>
                    <div id="meeting_description_editor" class="min-h-[120px] rounded-lg border border-bgray-300 bg-white text-sm text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></div>
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

<!-- Participant Row Template (Hidden) -->
<template id="participant_row_template">
    <div class="participant-row rounded-lg border border-bgray-200 bg-white p-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 space-y-3" data-index="{INDEX}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-700 dark:text-bgray-300 cursor-pointer">
                    <input type="radio" name="participants[{INDEX}][is_external]" value="0" class="participant-type-toggle text-success-300 focus:ring-0" checked data-index="{INDEX}">
                    Internal User
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-700 dark:text-bgray-300 cursor-pointer">
                    <input type="radio" name="participants[{INDEX}][is_external]" value="1" class="participant-type-toggle text-success-300 focus:ring-0" data-index="{INDEX}">
                    External Participant
                </label>
            </div>

            <button type="button" class="remove-participant-btn rounded p-1 text-red-500 hover:bg-red-50 dark:hover:bg-darkblack-400 transition" title="Remove Participant">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <!-- Internal User Selection -->
        <div class="internal-user-fields" data-index="{INDEX}">
            <select name="participants[{INDEX}][user_id]" class="tom-select-participant w-full">
                <option value="">Select Internal User</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- External Participant Fields -->
        <div class="external-user-fields hidden grid-cols-1 gap-2 sm:grid-cols-3" data-index="{INDEX}">
            <div>
                <input type="text" name="participants[{INDEX}][name]" class="w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Full Name">
            </div>
            <div>
                <input type="email" name="participants[{INDEX}][email]" class="w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Email Address">
            </div>
            <div>
                <input type="text" name="participants[{INDEX}][phone]" class="w-full rounded-md border border-bgray-300 px-3 py-1.5 text-xs text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Phone (Optional)">
            </div>
        </div>

        <!-- Send Email Option -->
        <div class="flex items-center gap-2">
            <input type="checkbox" name="participants[{INDEX}][send_email]" value="1" id="send_email_{INDEX}" class="rounded border-bgray-300 text-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <label for="send_email_{INDEX}" class="text-xs text-bgray-600 dark:text-bgray-300">
                Send Email Notification
            </label>
        </div>
    </div>
</template>
