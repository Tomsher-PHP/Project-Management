@foreach ($taskGroups as $group)
    @include('projects.partials.tasks.group-card', [
        'project' => $project,
        'group' => $group,
        'isOpen' => $group['key'] === ($initialGroupKey ?? null),
        'tasks' => $group['key'] === ($initialGroupKey ?? null) ? ($initialTasks ?? collect()) : collect(),
        'initialTasksPagination' => $group['key'] === ($initialGroupKey ?? null)
            ? ($initialTasksPagination ?? ['page' => 1, 'next_page' => null, 'has_more_pages' => false])
            : ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
    ])
@endforeach
