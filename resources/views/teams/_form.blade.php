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
                    Team Name <x-red-star />
                </label>

                <input type="text" name="name" id="team_name" value="{{ old('name', $team->name ?? '') }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
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
        @php
            $oldMembers = session()->hasOldInput('members')
                ? collect(old('members', []))->values()
                : null;
            $memberUserLookup = $users->concat($teamUsers)->keyBy('id');
            $displayMembers = $oldMembers
                ? $oldMembers->map(function ($member) use ($memberUserLookup) {
                    $userId = (int) ($member['user_id'] ?? 0);
                    $user = $memberUserLookup->get($userId);

                    return (object) [
                        'user_id' => $userId,
                        'name' => $user->name ?? ('User #' . $userId),
                        'email' => $user->email ?? '',
                        'profile_image_url' => $user?->profile_image_url ?? asset(config('assets.images.default_avatar')),
                        'team_role' => $member['team_role'] ?? 'member',
                    ];
                })
                : $teamUsers->map(function ($teamUser) {
                    return (object) [
                        'user_id' => (int) $teamUser->pivot->user_id,
                        'name' => $teamUser->name,
                        'email' => $teamUser->email,
                        'profile_image_url' => $teamUser->profile_image_url,
                        'team_role' => $teamUser->pivot->team_role,
                    ];
                });
            $selectedMemberIds = $displayMembers->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->all();
            $availableUsers = $users->whereNotIn('id', $selectedMemberIds);
            $defaultTeamRole = $displayMembers->contains(fn ($member) => $member->team_role === 'team_leader')
                ? 'member'
                : 'team_leader';
        @endphp

        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 dark:border-darkblack-400 dark:text-white">
            Team Members
        </h3>

        <div id="members-table" class="grid grid-cols-1 gap-5 rounded-lg border p-5 dark:border-darkblack-400 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($displayMembers as $memberEntry)
                <div class="relative rounded-lg border border-bgray-200 bg-gray-50 p-4 team-member-card transition-shadow dark:border-darkblack-400 dark:bg-darkblack-500" data-member-id="{{ $memberEntry->user_id }}">
                    <span class="absolute right-4 top-4 inline-block rounded-full px-3 py-1 text-xs
                        @if ($memberEntry->team_role === 'team_leader') bg-purple-100 text-purple-600
                        @else bg-gray-200 text-gray-600 @endif">
                        {{ config('constants.team_roles.' . $memberEntry->team_role) ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memberEntry->team_role)) }}
                    </span>

                    <div class="pr-24">
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ $memberEntry->profile_image_url }}" class="h-10 w-10 rounded-full object-cover" alt="{{ $memberEntry->name }}">

                            <div class="min-w-0">
                                <h4 class="member-name truncate text-base font-bold text-bgray-900 dark:text-white">
                                    {{ $memberEntry->name }}
                                </h4>
                                <p class="member-email truncate text-sm text-gray-500 dark:text-bgray-50">
                                    {{ $memberEntry->email ?: '--' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" class="remove-member flex items-center gap-1 rounded-full border border-bgray-200 bg-error-50 px-3 py-1 text-xs font-medium text-error-300 shadow-sm transition duration-200 hover:border-bgray-300 hover:bg-bgray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Remove
                        </button>

                        <input type="hidden" class="input-user-id" name="members[{{ $memberEntry->user_id }}][user_id]" value="{{ $memberEntry->user_id }}">
                        <input type="hidden" class="input-team-role" name="members[{{ $memberEntry->user_id }}][team_role]" value="{{ $memberEntry->team_role }}">
                    </div>
                </div>
            @empty
                <div id="empty-row" class="col-span-full rounded-lg border border-dashed border-bgray-300 bg-white px-6 py-10 text-center text-gray-400 dark:border-darkblack-400 dark:bg-darkblack-600">
                    No team members added yet.
                </div>
            @endforelse
        </div>
        @error('members')
            <p class="mt-2 text-sm text-error-300">
                {{ $message }}
            </p>
        @enderror

        {{-- Add Member Section --}}
        <div class="mt-6 p-6 border rounded-lg dark:border-darkblack-400 bg-gray-50 dark:bg-darkblack-500">

            <div class="grid md:grid-cols-3 gap-6 items-end">

                {{-- Role --}}
                <div class="flex flex-col gap-2">
                    <label for="team_role" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Team Role
                    </label>

                    <select id="team_role" class="tom-select-no-search w-full">
                        <option value="">Select Role</option>

                        @foreach ($teamRoles as $key => $role)
                            <option value="{{ $key }}" @selected($key === $defaultTeamRole)>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- User --}}
                <div class="flex flex-col gap-2">
                    <label for="team_member" class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Team Member
                    </label>

                    <select id="team_member" class="tom-select-multiple w-full" multiple>
                        @foreach ($availableUsers as $user)
                            <option value="{{ $user->id }}" data-data='@json([
                                "email" => $user->email,
                                "profile_image_url" => $user->profile_image_url,
                            ])'>
                                {{ $user->name }}
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
    <div class="relative rounded-lg border border-bgray-200 bg-gray-50 p-4 team-member-card transition-shadow dark:border-darkblack-400 dark:bg-darkblack-500">
        <span class="member-role-badge absolute right-4 top-4 inline-block rounded-full px-3 py-1 text-xs"></span>

        <div class="pr-24">
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ asset(config('assets.images.default_avatar')) }}" class="member-avatar h-10 w-10 rounded-full object-cover" alt="">

                <div class="min-w-0">
                    <h4 class="member-name truncate text-base font-bold text-bgray-900 dark:text-white"></h4>
                    <p class="member-email truncate text-sm text-gray-500 dark:text-bgray-50"></p>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" class="remove-member flex items-center gap-1 rounded-full border border-bgray-200 bg-error-50 px-3 py-1 text-xs font-medium text-error-300 shadow-sm transition duration-200 hover:border-bgray-300 hover:bg-bgray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Remove
            </button>

            <input type="hidden" class="input-user-id" name="">
            <input type="hidden" class="input-team-role" name="">
        </div>
    </div>
</template>
