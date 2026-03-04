<form action="{{ isset($team) ? route('teams.update', $team->id) : route('teams.store') }}" method="POST" class="space-y-10" enctype="multipart/form-data">

    @csrf
    @if (isset($team))
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

        {{-- LEFT SIDE - IMAGE --}}
        <div class="lg:col-span-1">
            <div class="flex justify-center lg:justify-start pb-8 lg:pb-0">

                <div id="drop-area" class="relative flex h-28 w-28 items-center justify-center rounded-md border-2 border-dashed border-gray-300 overflow-hidden cursor-pointer">

                    <!-- Preview Image -->
                    <img id="preview" class="absolute inset-0 h-full w-full object-cover rounded-md {{ isset($team->teamAvatarUrl) ? '' : 'hidden' }}" alt="Preview" src="{{ $team->teamAvatarUrl ?? '' }}" />

                    <!-- Remove Button -->
                    <button type="button" id="remove-btn" class="absolute -top-2 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 {{ isset($team->teamAvatarUrl) ? '' : 'hidden' }}">
                        ✕
                    </button>

                    <!-- Upload Placeholder -->
                    <div id="placeholder" class="flex items-center justify-center text-sm text-gray-600 {{ isset($team->teamAvatarUrl) ? 'hidden' : '' }}">
                        <label for="profile-image" class="cursor-pointer text-indigo-600">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                                <path d="M19.9997 13.3333V26.6666M26.6663 19.9999H13.333M19.9997 36.6666C29.2044 36.6666 36.6663 29.2047 36.6663 19.9999C36.6663 10.7952 29.2044 3.33325 19.9997 3.33325C10.7949 3.33325 3.33301 10.7952 3.33301 19.9999C3.33301 29.2047 10.7949 36.6666 19.9997 36.6666Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <input id="profile-image" name="profile_image" type="file" class="hidden" accept="image/*" />
                            <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                        </label>
                    </div>
                </div>
                @error('profile_image')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        {{-- RIGHT SIDE - FORM --}}
        <div class="lg:col-span-3">
            <div class="flex flex-col gap-2">
                <label for="team_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Team Name
                </label>

                <input type="text" name="name" id="team_name" value="{{ old('name', $team->name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-4 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

    </div>

    {{-- ================= Team Members Information ================= --}}
    <div>

        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Team Members
        </h3>

        {{-- Members Table --}}
        <div class="overflow-x-auto rounded-lg border dark:border-darkblack-400">
            <table class="w-full text-left">
                <thead class="bg-gray-100 dark:bg-darkblack-500">
                    <tr>
                        <th class="p-4 text-sm font-semibold">User</th>
                        <th class="p-4 text-sm font-semibold">Role</th>
                        <th class="p-4 text-sm font-semibold text-center">Action</th>
                    </tr>
                </thead>

                <tbody id="members-table" class="divide-y dark:divide-darkblack-400">

                    @forelse ($teamUsers as $teamUser)
                        <tr class="border-t team-member-row">
                            <td class="p-4 member-name">{{ $teamUser->name }}</td>
                            <td class="p-4 member-role">{{ config('constants.team_roles')[$teamUser->pivot->team_role] }}</td>
                            <td class="p-4 text-center">
                                <button type="button" class="remove-member text-error-300 hover:underline">
                                    Remove
                                </button>

                                <input type="hidden" class="input-user-id" name="members[{{ $teamUser->pivot->user_id }}][user_id]" value="{{ $teamUser->pivot->user_id }}">
                                <input type="hidden" class="input-team-role" name="members[{{ $teamUser->pivot->user_id }}][team_role]" value="{{ $teamUser->pivot->team_role }}">
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="3" class="p-6 text-center text-gray-400">
                                No team members added yet.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- Add Member Section --}}
        {{-- @dd($teamUsers->pluck('id')->toArray(), $users) --}}
        @php
            $teamUsersIds = $teamUsers->pluck('id')->toArray();
            $users = $users->whereNotIn('id', $teamUsersIds);
        @endphp
        <div class="mt-6 p-6 border rounded-lg dark:border-darkblack-400 bg-gray-50 dark:bg-darkblack-500">

            <div class="grid md:grid-cols-3 gap-6 items-end">

                {{-- User --}}
                <div class="flex flex-col gap-2">
                    <label for="team_member" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Team Member
                    </label>

                    <select id="team_member" class="w-full rounded-lg border border-gray-300 p-3 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                        <option value="">Select Member</option>

                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Role --}}
                <div class="flex flex-col gap-2">
                    <label for="team_role" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Team Role
                    </label>

                    <select id="team_role" class="w-full rounded-lg border border-gray-300 p-3 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                        <option value="">Select Role</option>

                        @foreach ($teamRoles as $key => $role)
                            <option value="{{ $key }}">
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Add Button --}}
                <div class="flex md:justify-end col-span-full md:col-span-1">
                    <button type="button" id="add-member-btn" class="px-4 py-2 text-sm rounded-md bg-success-300 text-white font-medium hover:bg-success-400 transition">
                        + Add Member
                    </button>
                </div>

            </div>

        </div>

    </div>

    {{-- ================= SUBMIT ================= --}}
    <div class="pt-6 border-t flex justify-end dark:border-darkblack-400">
        <button type="submit" class="px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition">

            @if (isset($team))
                Update Team
            @else
                Create Team
            @endif
        </button>
    </div>

</form>

<template id="member-row-template">
    <tr class="border-t team-member-row">
        <td class="p-4 member-name"></td>
        <td class="p-4 member-role"></td>
        <td class="p-4 text-center">
            <button type="button" class="remove-member text-error-300 hover:underline">
                Remove
            </button>

            <input type="hidden" class="input-user-id" name="">
            <input type="hidden" class="input-team-role" name="">
        </td>
    </tr>
</template>
