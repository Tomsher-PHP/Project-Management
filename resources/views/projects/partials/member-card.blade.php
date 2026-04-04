<div class="relative rounded-lg p-5 team-member-card transition-shadow
    @if ($member->pivot->is_active) bg-gray-100 dark:bg-darkblack-500 border-t border-success-300 @else bg-gray-50 dark:bg-darkblack-600 opacity-70 @endif
    @if ($member->pivot->project_role === 'team_leader') border border-success-300 @endif">

    <!-- User Info -->
    <div>
        <div class="flex items-center gap-4 mb-4">
            <!-- Avatar -->
            <img src="{{ $member->profile_image_url ?? asset('images/default-avatar.png') }}" class="h-12 w-12 rounded-full object-cover" alt="{{ $member->name }}">

            <div>
                <h4 class="text-base font-bold text-bgray-900 dark:text-white member-name">
                    {{ $member->name }}
                </h4>

                <p class="text-sm text-gray-500 dark:text-bgray-50 member-role">
                    {{ $member->email }}
                </p>

                <p class="text-sm text-gray-500 dark:text-bgray-50 member-role">
                    {{ $member->designation_name }}
                </p>
            </div>
        </div>

        <!-- Role Badge -->
        <span class="inline-block text-xs px-3 py-1 rounded-full
            @if ($member->pivot->project_role === 'team_leader') bg-purple-100 text-purple-600
            @elseif($member->pivot->project_role === 'coordinator') bg-blue-100 text-blue-600
            @else bg-gray-200 text-gray-600 @endif">
            {{ config('project_constants.project_roles')[$member->pivot->project_role] }}
        </span>
    </div>

    <!-- Bottom Right Actions -->
    @can('project.remove_team', $project)
        <div class="flex justify-end mt-4 gap-2">

            <!-- Enable/Disable Button -->
            <button type="button" class="toggle-member flex items-center gap-1 text-sm font-medium px-3 py-1 rounded-full transition 
                       {{ $member->pivot->is_active ? 'bg-gray-200 text-gray-700 hover:text-bgray-900' : 'bg-success-50 hover:text-success-300' }}" data-id="{{ $member->id }}" data-active="{{ $member->pivot->is_active ? 1 : 0 }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    @if ($member->pivot->is_active)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    @endif
                </svg>
                {{ $member->pivot->is_active ? 'Disable' : 'Enable' }}
            </button>

            <!-- Remove Button -->
            <button type="button" class="remove-member flex items-center gap-1 text-sm font-medium px-3 py-1 rounded-full bg-error-50 hover:text-error-300 transition shadow-sm" data-id="{{ $member->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Remove
            </button>

        </div>
    @endcan

</div>
