@php
    $tabs = [
        [
            'key' => 'categories',
            'label' => 'Categories',
            'url' => route('settings.project-categories.index'),
            'permission' => 'project_category.view',
        ],
        [
            'key' => 'statuses',
            'label' => 'Statuses',
            'url' => route('settings.project-statuses.index'),
            'permission' => 'project_status.view',
        ],
        [
            'key' => 'stages',
            'label' => 'Stages',
            'url' => route('settings.project-stages.index'),
            'permission' => 'project_stage.view',
        ],
        [
            'key' => 'milestones',
            'label' => 'Milestones',
            'url' => route('settings.agile-milestones.index'),
            'permission' => 'agile_milestone.view',
        ],
        [
            'key' => 'sprints',
            'label' => 'Sprints',
            'url' => route('settings.agile-sprints.index'),
            'permission' => 'agile_sprint.view',
        ],
    ];
@endphp

<div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
    @foreach ($tabs as $tab)
        @can($tab['permission'])
            @php
                $isActiveTab = $currentTab === $tab['key'];
            @endphp
            <a href="{{ $tab['url'] }}"
                class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                {{ $tab['label'] }}
            </a>
        @endcan
    @endforeach
</div>
