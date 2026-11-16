@foreach ($taskGroups as $group)
    @php
        $isGroupOpen = !empty($allGroupsOpen) || $group['key'] === ($initialGroupKey ?? null);
        $groupTasks = $group['tasks'] ?? ($group['key'] === ($initialGroupKey ?? null) ? ($initialTasks ?? collect()) : collect());
        $groupPagination = $group['pagination'] ?? ($group['key'] === ($initialGroupKey ?? null) ? ($initialTasksPagination ?? ['page' => 1, 'next_page' => null, 'has_more_pages' => false]) : ['page' => 1, 'next_page' => null, 'has_more_pages' => false]);
    @endphp
    @include('projects.partials.tasks.group-card', [
        'project' => $project,
        'group' => $group,
        'isOpen' => $isGroupOpen,
        'tasks' => $groupTasks,
        'initialTasksPagination' => $groupPagination,
        'search' => $search ?? null,
    ])
@endforeach
