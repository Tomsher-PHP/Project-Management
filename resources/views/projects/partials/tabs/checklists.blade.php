<div class="space-y-4 p-4 sm:p-6">
    @if($users->isEmpty())
        <div class="rounded-xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </span>
            <h3 class="mt-4 text-lg font-medium text-bgray-900 dark:text-white">No Checklists Assigned</h3>
            <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">There are no checklists assigned to any team members in this project.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($users as $user)
                <!-- User Accordion -->
                <div class="rounded-2xl border border-bgray-200 bg-white overflow-hidden shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" x-data="{ expandedUser: false }">
                    <button type="button" @click="expandedUser = !expandedUser" class="flex w-full items-center justify-between bg-white px-6 py-4 transition hover:bg-bgray-50 dark:bg-darkblack-600 dark:hover:bg-darkblack-500">
                        <div class="flex items-center gap-4">
                            <img src="{{ $user->profile_image_url ?: asset('assets/images/avatar/avatar-1.png') }}" alt="{{ $user->name }}" class="h-12 w-12 rounded-full object-cover border border-bgray-200 dark:border-darkblack-400">
                            <div class="text-left">
                                <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">{{ $user->name }}</h4>
                                <p class="mt-0.5 text-sm font-medium text-bgray-500 dark:text-bgray-300">{{ $user->designation_name ?? $user->email }}</p>
                            </div>
                        </div>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-bgray-50 text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                            <svg class="h-5 w-5 transition-transform duration-200" :class="expandedUser ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>

                    <div x-show="expandedUser" x-collapse x-cloak class="border-t border-bgray-200 bg-bgray-50/30 dark:border-darkblack-400 dark:bg-darkblack-500/10">
                        <div class="p-4 sm:p-6 space-y-4">
                            @foreach($user->projectChecklists as $checklist)
                                <!-- Checklist Accordion -->
                                <div class="rounded-xl border border-bgray-200 bg-white overflow-hidden shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" x-data="{ expandedChecklist: false }">
                                    <button type="button" @click="expandedChecklist = !expandedChecklist" class="flex w-full items-center justify-between px-5 py-4 transition hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-500 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <h5 class="text-base font-semibold text-bgray-900 dark:text-white">{{ $checklist->title }}</h5>
                                                <p class="mt-0.5 text-xs text-bgray-500 dark:text-bgray-300">{{ $checklist->items->count() }} {{ $checklist->items->count() === 1 ? 'question' : 'questions' }}</p>
                                            </div>
                                        </div>
                                        <span class="text-bgray-400 transition-transform duration-200" :class="expandedChecklist ? 'rotate-180' : ''">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </button>

                                    <div x-show="expandedChecklist" x-collapse x-cloak class="border-t border-bgray-100 bg-bgray-50/60 p-5 dark:border-darkblack-400 dark:bg-darkblack-500/30">
                                        <div class="space-y-3">
                                            @foreach($checklist->items as $item)
                                                <div class="flex items-start gap-3 rounded-lg border border-bgray-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.02)] transition hover:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-600">
                                                    <div class="flex h-5 items-center mt-0.5">
                                                        <input type="checkbox" id="item-{{ $item->id }}" class="h-5 w-5 cursor-pointer rounded border-bgray-300 text-success-400 focus:ring-success-400 disabled:opacity-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:ring-offset-darkblack-600 dark:checked:bg-success-400" 
                                                            data-project-checklist-item-toggle 
                                                            data-url="{{ route('projects.checklists.toggleItem', ['project' => $project, 'itemId' => $item->id]) }}"
                                                            {{ $item->status ? 'checked' : '' }}
                                                            @disabled($user->id !== auth()->id())>
                                                    </div>
                                                    <label for="item-{{ $item->id }}" class="cursor-pointer text-sm font-medium leading-relaxed text-bgray-800 dark:text-bgray-100">
                                                        {{ $item->question }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
