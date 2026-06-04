<div class="space-y-3">
    @foreach ($templates as $template)
        @php
            $isSelected = in_array($template['id'], $selectedTemplateIds);
            $preview = array_slice($template['questions'] ?? [], 0, 3);
            $questionsCount = count($template['questions'] ?? []);
            $isExpanded = $template['isExpanded'] ?? false;
        @endphp
        <article class="rounded-2xl border {{ $isSelected ? 'border-success-300 bg-success-50/60 dark:border-success-900/40 dark:bg-darkblack-600' : 'border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600' }} p-4 shadow-sm transition duration-200" {!! $isSelected ? '' : 'draggable="true"' !!} data-project-checklist-library-item data-template-id="{{ $template['id'] }}">
            <div class="flex items-start justify-between gap-3 cursor-pointer group" data-project-checklist-library-toggle data-template-id="{{ $template['id'] }}">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h5 class="truncate text-sm font-semibold text-bgray-900 group-hover:text-success-500 transition-colors dark:text-white">{{ $template['name'] }}</h5>
                        @if ($isSelected)
                            <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-success-600 dark:bg-darkblack-500 dark:text-success-300">Selected</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">{{ $questionsCount }} {{ $questionsCount === 1 ? 'question' : 'questions' }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center text-bgray-400 transition-transform duration-200 {{ $isExpanded ? 'rotate-180' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $isSelected ? 'bg-success-100 text-success-500 dark:bg-darkblack-500 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }} transition duration-200 hover:bg-success-50 hover:text-success-500" data-project-checklist-library-add data-template-id="{{ $template['id'] }}" {{ $isSelected ? 'disabled' : '' }} aria-label="Add checklist template">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-3 space-y-2 {{ $isExpanded ? '' : 'hidden' }}">
                @foreach ($preview as $index => $question)
                    <div class="rounded-xl bg-bgray-50 px-3 py-2 text-xs text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                        {{ $index + 1 }}. {{ $question['question'] }}
                    </div>
                @endforeach
                @if ($questionsCount > count($preview))
                    <p class="text-xs text-bgray-400 dark:text-bgray-300">+{{ $questionsCount - count($preview) }} more questions</p>
                @endif
            </div>
        </article>
    @endforeach
</div>
