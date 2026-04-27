<div>
    @can('project.add_team', $project)
        @include('projects.partials.teams-form')
    @endcan

    <div id="members-container" class="mt-5 grid grid-cols-1 gap-5 pb-10 sm:grid-cols-2 2xl:grid-cols-3 2xl:gap-8">
        @forelse ($project->members as $member)
            @include('projects.partials.member-card')
        @empty
            <div id="empty-row" class="col-span-full py-10 text-center text-gray-400">
                No members added yet.
            </div>
        @endforelse
    </div>
</div>
