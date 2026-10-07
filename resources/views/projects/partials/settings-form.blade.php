@php
    $canEdit = auth()->user()->can('project.edit') && !$project->trashed();
    $isDeletedProjectView = $project->trashed();
@endphp

<!-- ================= Project Timelines ================= -->
<div class="flex flex-col md:flex-row gap-8 pb-8 dark:border-darkblack-400 dark:text-white items-start md:items-center w-full">
    <div class="flex-1 w-full" id="project-timelines-container">
        @include('projects.partials.tabs.settings.timelines-list')
    </div>
</div>

@if ($canEdit)
    <!-- Timeline Modals -->
    @include('projects.partials.tabs.settings.timeline-modals')
@endif
