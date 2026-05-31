<!-- Running Tasks Card -->
<div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
    <!-- Card Header -->
    <div class="mb-6 border-b border-bgray-100 pb-4 dark:border-darkblack-500">
        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Running Tasks</h3>
    </div>

    <!-- Running Tasks Table -->
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">User Name</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Task Name</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Estimated Time</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Worked Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-500">
                @forelse($runningTasksData as $row)
                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                            <div class="flex items-center gap-2">
                                <x-user-avatar :user="$row['user']" size="sm" />
                                <span>{{ $row['user_name'] }}</span>
                            </div>
                        </td>
                        <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                            <a href="{{ route('tasks.edit', $row['task_id']) }}">
                                {{ $row['task_name'] }}
                            </a>
                        </td>
                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['estimated_time'] }}</td>
                        <td class="py-3.5 text-sm {{ $row['color_class'] }}">{{ $row['worked_time'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400 dark:bg-darkblack-500/50 dark:text-bgray-300 mb-3">
                                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-bgray-900 dark:text-white mb-1">No Running Tasks</h4>
                                <p class="text-xs text-bgray-500 dark:text-bgray-400">There are currently no tasks in progress.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
