<div class="space-y-6">

    <section class="rounded-2xl bg-white px-6 py-5 shadow-sm dark:bg-darkblack-600">

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr class="border-b border-bgray-200 text-left dark:border-darkblack-400">
                        <th class="px-2 py-4 text-sm font-semibold text-bgray-700 dark:text-bgray-50">#</th>
                        <th class="px-4 py-4 text-sm font-semibold text-bgray-700 dark:text-bgray-50">
                            <x-sorting.sortable-column column="name" label="Name" />
                        </th>
                        <th class="px-4 py-4 text-sm font-semibold text-bgray-700 dark:text-bgray-50">Questions</th>
                        <th class="px-4 py-4 text-sm font-semibold text-bgray-700 dark:text-bgray-50">Is Active</th>
                        <th class="px-4 py-4 text-sm font-semibold text-bgray-700 dark:text-bgray-50">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $startNumber = ($checklists->currentPage() - 1) * $checklists->perPage();
                    @endphp

                    @forelse ($checklists as $checklist)
                        @php
                            $previewQuestions = $checklist->items->take(3);
                            $questionList = $checklist->items->pluck('question')->values();
                        @endphp
                        <tr class="border-b border-bgray-200 align-top last:border-b-0 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                            <td class="px-2 py-5 text-sm font-medium text-bgray-600 dark:text-bgray-50">
                                {{ $startNumber + $loop->iteration }}
                            </td>
                            <td class="px-4 py-5">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">{{ $checklist->name }}</p>
                                        @if ($checklist->is_system)
                                            <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                System
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-bgray-700 dark:text-bgray-300">
                                        {{ $checklist->items_count }} {{ \Illuminate\Support\Str::plural('question', $checklist->items_count) }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-5">
                                <div class="space-y-2">
                                    @forelse ($previewQuestions as $question)
                                        <div class="rounded-lg bg-bgray-50 px-3 py-2 text-sm text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                            {{ $question->question }}
                                        </div>
                                    @empty
                                        <p class="text-sm text-bgray-400 dark:text-bgray-300">No questions added.</p>
                                    @endforelse

                                    @if ($checklist->items_count > $previewQuestions->count())
                                        <p class="text-xs font-medium text-bgray-700 dark:text-bgray-300">
                                            +{{ $checklist->items_count - $previewQuestions->count() }} more questions
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-5">
                                <div class="flex items-center gap-3">
                                    <x-status-toggle :model="$checklist" route="settings.checklist.toggleStatus" entity="checklist template" permission="checklist_template.edit" />
                                </div>
                            </td>
                            <td class="px-4 py-5">
                                <div class="flex items-center gap-3">
                                    @can('checklist_template.edit')
                                        <button type="button" class="edit-record inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-modal="multi-step-modal" data-url="{{ route('settings.checklists.update', $checklist->id) }}" data-name="{{ $checklist->name }}" data-questions='@json($questionList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)' data-method="PUT" data-module="Checklist Template" aria-label="Edit checklist template">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                            </svg>
                                        </button>
                                    @endcan

                                    @can('checklist_template.delete')
                                        @if (! $checklist->is_system)
                                            <x-delete-form :action="route('settings.checklists.destroy', $checklist->id)" />
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-no-data :col-span="5" message="No checklist templates found." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            <x-pagination :paginator="$checklists" :per-page="$perPage" />
        </div>
    </section>
</div>
