<div class="relative rounded-lg bg-gray-100 p-5 dark:bg-darkblack-500 team-member-card @if ($member->project_role === 'team_leader') border border-success-300 @endif">

    <!-- User Info -->
    <div>
        <div class="flex items-center gap-4 mb-4">

            <!-- Avatar -->
            <img src="{{ $member->user->profile_image_url ?? asset('images/default-avatar.png') }}" class="h-12 w-12 rounded-full object-cover" alt="{{ $member->user->name }}">

            <div>
                <h4 class="text-base font-bold text-bgray-900 dark:text-white member-name">
                    {{ $member->user->name }}
                </h4>

                <p class="text-sm text-gray-500 dark:text-bgray-50 member-role">
                    {{ $member->user->email }}
                </p>

                <p class="text-sm text-gray-500 dark:text-bgray-50 member-role">
                    {{ $member->user->designation_name }}
                </p>
            </div>
        </div>

        <!-- Role Badge -->
        <span class="inline-block text-xs px-3 py-1 rounded-full
            @if ($member->project_role === 'team_leader') bg-purple-100 text-purple-600
            @elseif($member->project_role === 'coordinator') bg-blue-100 text-blue-600
            @else bg-gray-200 text-gray-600 @endif">
            {{ config('constants.project_roles')[$member->project_role] }}
        </span>
    </div>

    <!-- Bottom Right Action -->
    @can('project.remove_team', $project)
        <div class="flex justify-end mt-4">
            <button type="button" class="remove-member flex items-center gap-1 text-sm text-red-500 hover:text-red-600 transition" data-id="{{ $member->user_id }}">
                <span class="text-sm text-red-500">Remove</span>
            </button>
        </div>
    @endcan

</div>
