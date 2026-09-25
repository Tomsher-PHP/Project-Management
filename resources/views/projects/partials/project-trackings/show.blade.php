<div id="project-tracking-history" class="overflow-x-auto">
    @if ($projectTrackings->isEmpty())

        <div class="p-5">
            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No project tracking entries available yet.
            </div>
        </div>
    @else
        <table class="w-full min-w-[900px]">
            <thead>
                <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-500">
                        Date
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-500">
                        Title
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-500">
                        Description
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-500">
                        Attachments
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-bgray-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">

                @foreach ($projectTrackings as $tracking)
                    <tr class="hover:bg-bgray-50 dark:hover:bg-darkblack-500">

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-bgray-700 dark:text-bgray-300">
                            {{ $tracking->date?->format('d M Y') }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="text-sm font-semibold text-bgray-900 dark:text-white">
                                {{ $tracking->title }}
                            </div>
                        </td>

                        <td class="max-w-[350px] px-5 py-4">
                            <div class="truncate text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $tracking->description ?: '--' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            @if ($tracking->attachments->count())
                                <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-400">
                                    {{ $tracking->attachments->count() }}
                                    {{ \Illuminate\Support\Str::plural('file', $tracking->attachments->count()) }}
                                </span>
                            @else
                                <span class="text-sm text-bgray-500 dark:text-bgray-400">
                                    --
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @can('project_tracking.view')
                                    <x-view-button :action="route('projects.trackings.show', [$project, $tracking])" data-project-tracking-view data-tracking-id="{{ $tracking->id }}" title="View" />
                                @endcan

                                @can('project_tracking.edit')
                                    <x-edit-button :action="route('projects.trackings.show', [$project, $tracking]) . '?mode=edit'" data-project-tracking-edit data-tracking-id="{{ $tracking->id }}" title="Edit" />
                                @endcan

                                @can('project_tracking.delete')
                                    <x-delete-form :action="route('projects.trackings.destroy', [$project, $tracking])" :id="$tracking->id" ajax="true" render-target="#project-tracking-history" render-mode="replace_outer" form-class="project-tracking-delete-form" confirm-title="Delete Project Tracking" confirm-message="Are you sure you want to delete this project tracking entry?" data-project-tracking-delete title="Delete" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>

    @endif
</div>
