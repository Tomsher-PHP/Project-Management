<div class="relative rounded-lg p-4 team-member-card transition-shadow
    @if ($member->pivot->is_active) bg-gray-100 dark:bg-darkblack-500 border-t border-success-300 @else bg-gray-50 dark:bg-darkblack-600 opacity-70 @endif
    @if ($member->pivot->project_role === 'team_leader') border border-success-300 @endif" data-member-id="{{ $member->id }}" data-project-role="{{ $member->pivot->project_role }}">

    <span class="absolute right-4 top-4 inline-block rounded-full px-3 py-1 text-xs
        @if ($member->pivot->project_role === 'team_leader') bg-purple-100 text-purple-600
        @elseif($member->pivot->project_role === 'coordinator') bg-blue-100 text-blue-600
        @else bg-gray-200 text-gray-600 @endif">
        {{ config('project_constants.project_roles')[$member->pivot->project_role] }}
    </span>

    <!-- User Info -->
    <div class="pr-24">
        <div class="mb-3 flex items-center gap-3">
            <!-- Avatar -->
            <img src="{{ $member->profile_image_url ?? asset('images/default-avatar.png') }}" class="h-10 w-10 rounded-full object-cover" alt="{{ $member->name }}">

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
    </div>

    <!-- Bottom Right Actions -->
    @can('project.remove_team', $project)
        <div class="mt-4 space-y-2">
            <div class="flex flex-wrap gap-2">
                @if ($member->pivot->is_active && $member->pivot->project_role !== 'team_leader')
                    <button type="button" class="set-project-role flex items-center gap-1 rounded-full border border-bgray-200 bg-white px-3 py-1 text-xs font-medium text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-300" data-id="{{ $member->id }}" data-role="team_leader" data-role-label="Team Leader">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Set as Team Leader
                    </button>
                @endif

                @if ($member->pivot->is_active && $member->pivot->project_role !== 'coordinator')
                    <button type="button" class="set-project-role flex items-center gap-1 rounded-full border border-bgray-200 bg-white px-3 py-1 text-xs font-medium text-blue-500 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-300" data-id="{{ $member->id }}" data-role="coordinator" data-role-label="Coordinator">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Set as Coordinator
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <!-- Enable/Disable Button -->
                <button type="button" class="toggle-member flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium shadow-sm transition duration-200
                           {{ $member->pivot->is_active ? 'border-bgray-200 bg-white text-bgray-700 hover:border-success-300 hover:bg-bgray-100 hover:text-success-300' : 'border-success-100 bg-success-50 text-success-400 hover:border-success-300 hover:bg-success-50 hover:text-success-300' }}" data-id="{{ $member->id }}" data-active="{{ $member->pivot->is_active ? 1 : 0 }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @if ($member->pivot->is_active)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        @endif
                    </svg>
                    {{ $member->pivot->is_active ? 'Disable' : 'Enable' }}
                </button>

                <!-- Remove Button -->
                <button type="button" class="remove-member flex items-center gap-1 rounded-full border border-bgray-200 bg-error-50 px-3 py-1 text-xs font-medium text-error-300 shadow-sm transition duration-200 hover:border-bgray-300 hover:bg-bgray-100" data-id="{{ $member->id }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Remove
                </button>
            </div>
        </div>
    @endcan

</div>
