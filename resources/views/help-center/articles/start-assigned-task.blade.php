<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Step 1: Open the Workspace</h4>
    <p class="mt-2">Navigate to Workspace and locate your assigned task on the Kanban board. Tasks ready to begin are typically in the <strong>Open</strong> or <strong>To Do</strong> column.</p>
</section>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Step 2: Move the task to Progressing</h4>
    <p class="mt-2">Drag the task card from Open or To Do to <strong>Progressing</strong>.</p>
    <aside class="mt-4 rounded-xl border-l-4 border-warning-300 bg-warning-50 px-4 py-3 text-bgray-800 dark:bg-warning-300/10 dark:text-bgray-100">
        <strong>Note:</strong> Tasks cannot be started while they remain in the Open or To Do column.
    </aside>
</section>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Step 3: Start the task timer</h4>
    <p class="mt-2">Click the <strong>Play (▶)</strong> button on the task card. Once started:</p>
    <ul class="mt-2 list-disc space-y-1 pl-6">
        <li>The running timer is displayed on the task card.</li>
        <li>The active timer is also shown in the top navigation bar.</li>
    </ul>
</section>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Step 4: Work on the task</h4>
    <p class="mt-2">The timer records your working time while the task is in progress. If you need to take a break, stop the timer manually and resume it later.</p>
</section>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Step 5: Complete or pause the task</h4>
    <p class="mt-2">When you finish or temporarily stop working, move the task to:</p>
    <ul class="mt-2 list-disc space-y-1 pl-6">
        <li>Completed</li>
        <li>On Hold</li>
    </ul>
    <p class="mt-2">The timer automatically stops when the task moves to either status.</p>
</section>

<section>
    <h4 class="font-semibold text-bgray-900 dark:text-white">Things to remember</h4>
    <ul class="mt-2 list-disc space-y-1 pl-6">
        <li>Always move the task to Progressing before starting work.</li>
        <li>Start the timer whenever you begin working.</li>
        <li>Time is tracked only while the timer is running.</li>
        <li>You can switch between Agile and Linear project workflows depending on project settings.</li>
    </ul>
</section>

<div class="grid gap-4 sm:grid-cols-2">
    @foreach (['Workspace Kanban Board', 'Running Task Timer'] as $screenshot)
        <figure class="flex min-h-40 items-center justify-center rounded-xl border-2 border-dashed border-bgray-200 bg-bgray-50 p-6 text-center dark:border-darkblack-400 dark:bg-darkblack-500">
            <figcaption>
                <svg class="mx-auto stroke-bgray-400" width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.5" />
                    <circle cx="8.5" cy="9" r="1.5" stroke-width="1.5" />
                    <path d="m4 17 4.5-4 3.5 3 2.5-2 5.5 4" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
                <span class="mt-2 block text-xs font-semibold uppercase tracking-wide text-bgray-500">Screenshot placeholder</span>
                <span class="block text-sm text-bgray-700 dark:text-bgray-200">{{ $screenshot }}</span>
            </figcaption>
        </figure>
    @endforeach
</div>
