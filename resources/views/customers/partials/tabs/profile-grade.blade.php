@php
    $profileGrade = $customer->profileGrade;
    $gradeColor = $profileGrade && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $profileGrade->color)
        ? $profileGrade->color
        : '#22C55E';
@endphp

<section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-customer-profile-grade-content>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h3 class="text-base font-bold text-bgray-900 dark:text-white">Customer Profile Grade</h3>
            <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">Profile classification and customer-specific description points.</p>
        </div>

        @can('customer.edit')
            <button type="button" data-customer-profile-grade-open class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M14.166 2.5C14.385 2.28103 14.645 2.10732 14.9311 1.98879C15.2173 1.87026 15.524 1.8092 15.8338 1.8092C16.1435 1.8092 16.4503 1.87026 16.7364 1.98879C17.0225 2.10732 17.2823 2.28103 17.5013 2.5C17.7202 2.71897 17.8939 2.97874 18.0125 3.26487C18.131 3.551 18.1921 3.85768 18.1921 4.16746C18.1921 4.47723 18.131 4.78391 18.0125 5.07004C17.8939 5.35617 17.7202 5.61594 17.5013 5.83491L6.25033 17.0858L1.66602 18.3341L2.91435 13.7498L14.166 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>{{ $profileGrade ? 'Edit' : 'Assign Grade' }}</span>
            </button>
        @endcan
    </div>

    <div class="min-h-[260px] p-5">
        @if (! $profileGrade)
            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-10 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No profile grade has been assigned to this customer yet.
            </div>
        @else
            <div class="rounded-xl border p-5" style="border-color: {{ $gradeColor }}">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($profileGrade->badge_svg)
                        <span class="inline-flex h-10 w-10 items-center justify-center [&_svg]:h-8 [&_svg]:w-8" style="color: {{ $gradeColor }}">
                            {!! $profileGrade->badge_svg !!}
                        </span>
                    @else
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white" style="background-color: {{ $gradeColor }}">
                            {{ strtoupper(substr($profileGrade->name, 0, 1)) }}
                        </span>
                    @endif

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-lg font-bold text-bgray-900 dark:text-white">{{ $profileGrade->name }}</h4>
                            @if (! $profileGrade->is_active)
                                <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">Inactive</span>
                            @endif
                        </div>
                        <p class="text-xs font-medium uppercase tracking-wide" style="color: {{ $gradeColor }}">{{ $profileGrade->code }}</p>
                    </div>
                </div>

                <div class="mt-5 border-t border-bgray-100 pt-5 dark:border-darkblack-400">
                    <h5 class="text-sm font-semibold text-bgray-900 dark:text-white">Description Points</h5>

                    @if ($customer->profileDescriptions->isEmpty())
                        <p class="mt-3 text-sm text-bgray-600 dark:text-bgray-300">No description points have been added for this customer.</p>
                    @else
                        <ul class="mt-3 grid gap-3 md:grid-cols-2">
                            @foreach ($customer->profileDescriptions as $description)
                                <li class="flex items-start gap-2 rounded-lg bg-bgray-50 px-3 py-2.5 text-sm text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $gradeColor }}"></span>
                                    <span class="break-words">{{ $description->description }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
