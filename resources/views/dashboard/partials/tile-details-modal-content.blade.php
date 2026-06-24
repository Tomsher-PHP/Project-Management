<div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
    <h3 class="text-xl font-bold text-bgray-900 dark:text-white">{{ $title }}</h3>
    <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
</div>

<div class="custom-scroll flex-1 overflow-y-auto p-6">
    @if(count($records) > 0)
        <div class="space-y-4">
            @foreach($records as $record)
                @if($isProject)
                    <div class="flex items-center justify-between rounded-lg border border-bgray-200 p-4 transition-colors hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">
                        <div class="flex flex-col gap-1">
                            <a href="{{ route('projects.edit', $record->id) }}" class="text-sm font-bold text-success-300 hover:text-success-400 transition-colors">
                                {{ $record->project_code }} - {{ $record->name }}
                            </a>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-bgray-500 dark:text-bgray-400">
                                Customer: 
                                @if($record->customer)
                                    <x-profile-grade-badge :grade="$record->customer->profileGrade" size="sm" />
                                @endif
                                {{ $record->customer?->name ?? 'None' }}
                            </span>
                        </div>
                        <div class="shrink-0 ml-4">
                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $record->projectStatus->color ?? '#CBD5E1' }}"></span>
                                {{ $record->projectStatus->name ?? 'No Status' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between rounded-lg border border-bgray-200 p-4 transition-colors hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">
                        <div class="flex flex-col gap-1">
                            <a href="{{ route('tasks.edit', $record->id) }}" class="text-sm font-bold text-success-300 hover:text-success-400 transition-colors">
                                {{ $record->code }} - {{ $record->name }}
                            </a>
                            <span class="text-xs font-semibold text-bgray-500 dark:text-bgray-400">
                                Assignee: {{ $record->currentAssignee?->name ?? 'Unassigned' }}
                            </span>
                        </div>
                        <div class="shrink-0 ml-4">
                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $record->status->color ?? '#CBD5E1' }}"></span>
                                {{ $record->status->name ?? 'No Status' }}
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12">
            <svg class="mb-4 h-12 w-12 text-bgray-300 dark:text-bgray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="text-sm font-semibold text-bgray-500 dark:text-bgray-400">No recent records found.</p>
        </div>
    @endif
</div>

<div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
    <a href="{{ $viewAllUrl }}" class="inline-flex items-center justify-center rounded-lg bg-success-300 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 focus:ring-offset-2 dark:focus:ring-offset-darkblack-600">
        View All
    </a>
</div>
