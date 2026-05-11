@extends('layouts.master')

@section('page-content')

<main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]">

    <!-- Filter Button -->
    <x-filters.button />

    <!-- Export Button -->
    <div class="flex justify-end mb-4">

       

    </div>

    <!-- Table Wrapper -->
    <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

        <div class="table-content w-full overflow-x-auto">

            <table class="w-full min-w-[1300px] text-sm">

                <!-- Table Head -->
                <thead class="bg-[#0F172A] text-white">

                    <tr>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            #
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Task
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Project
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Milestone
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Sprint
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Assignee
                        </th>

                        <th class="px-4 py-3 text-center whitespace-nowrap">
                            Estimated Hours
                        </th>

                        <th class="px-4 py-3 text-center whitespace-nowrap">
                            Actual Hours
                        </th>

                        <th class="px-4 py-3 text-left whitespace-nowrap">
                            Progress
                        </th>

                        <th class="px-4 py-3 text-center whitespace-nowrap">
                            Status
                        </th>

                    </tr>

                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-gray-200">

                    @forelse($tasks as $task)

                        <tr class="hover:bg-gray-50 transition">

                            <!-- Sl No -->
                            <td class="px-4 py-4">

                                {{ $tasks->firstItem() + $loop->index }}

                            </td>

                            <!-- Task -->
                            <td class="px-4 py-4 font-medium text-gray-800">

                                {{ $task->name }}

                            </td>

                            <!-- Project -->
                            <td class="px-4 py-4">

                                {{ $task->project->name ?? '-' }}

                            </td>

                            <!-- Milestone -->
                            <td class="px-4 py-4">

                                {{ $task->projectMilestone->name ?? '-' }}

                            </td>

                            <!-- Sprint -->
                            <td class="px-4 py-4">

                                {{ $task->projectSprint->name ?? '-' }}

                            </td>

                            <!-- Assignee -->
                            <td class="px-4 py-4">

                                {{ $task->currentAssignee->name ?? '-' }}

                            </td>

                            <!-- Estimated Hours -->
                            <td class="px-4 py-4 text-center">

                                {{ $task->estimated_hours }}

                            </td>

                            <!-- Actual Hours -->
                            <td class="px-4 py-4 text-center">

                                {{ $task->actual_hours }}

                            </td>

                            <!-- Progress -->
                            <td class="px-4 py-4 min-w-[180px]">

                                <div class="flex items-center gap-3">

                                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">

                                        <div class="bg-blue-500 h-2.5 rounded-full"
                                             style="width: {{ $task->progress_percentage }}%">
                                        </div>

                                    </div>

                                    <span class="text-xs font-medium text-gray-700 min-w-[36px]">

                                        {{ $task->progress_percentage }}%

                                    </span>

                                </div>

                            </td>

                            <!-- Status -->
                            <td class="px-4 py-4 text-center">

                                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $task->status_badge_class }}">

                                    {{ $task->status->name ?? 'No Status' }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="10"
                                class="text-center py-10 text-gray-500">

                                No tasks found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div class="mt-6">

            {{ $tasks->links() }}

        </div>

    </div>

</main>

<x-filters.drawer>
    <x-filters.input-search name="search" label="Task" />
    <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
    <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
    <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
    <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$assignees" />
    <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
   
    
</x-filters.drawer>
@endsection