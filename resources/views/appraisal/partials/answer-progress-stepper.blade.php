@php
    $stepper = $answerData['stepper'] ?? [];
    $steps = $stepper['steps'] ?? [];
@endphp

@if (!empty($steps))
    <div class="w-full" data-appraisal-stepper-container>
        <div class="custom-scroll flex items-start justify-between gap-1 overflow-x-auto py-2">
            @foreach ($steps as $index => $step)
                @php
                    $isCompleted = $step['is_completed'] ?? false;
                    $isActive = $step['is_active'] ?? false;
                    $isLast = $loop->last;
                    $isAck = $step['is_acknowledgement'] ?? false;
                    $ackData = $step['acknowledgement_data'] ?? [];
                    $stepDate = !empty($step['completed_at']) ? $step['completed_at'] : null;
                    $ackDate = !empty($ackData['acknowledged_at']) ? $ackData['acknowledged_at'] : null;
                @endphp

                <div class="relative flex flex-1 flex-col items-center {{ !$isLast ? 'min-w-[130px] sm:min-w-[150px]' : 'min-w-[100px]' }}">
                    <!-- Connecting Line Behind Bubble -->
                    @if (!$isLast)
                        <div class="absolute top-4 left-[50%] z-0 h-0.5 w-full transition-colors duration-200 {{ $isCompleted ? 'bg-success-300 dark:bg-success-400' : 'bg-bgray-200 dark:border-darkblack-400' }}"></div>
                    @endif

                    <!-- Step Interactive Trigger or Circle -->
                    @if ($isAck)
                        <button type="button" class="relative z-10 flex flex-col items-center group cursor-pointer focus:outline-none transition-transform duration-150 hover:scale-105" data-appraisal-ack-trigger data-title="{{ $step['title'] }} Details" data-assignee-name="{{ $ackData['assignee_name'] ?? '--' }}" data-reviewer-name="{{ $ackData['reviewer_name'] ?? '--' }}" data-acknowledged-at="@if ($ackDate) @appDateTime($ackDate)@else-- @endif" data-acknowledgement-remark="{{ $ackData['acknowledgement_remark'] ?? '' }}" title="Click to view acknowledgement details" onclick="const modal = document.querySelector('[data-appraisal-ack-details-modal]'); if (modal) { const t = modal.querySelector('[data-appraisal-ack-modal-title]'); if(t) t.textContent = this.dataset.title; const a = modal.querySelector('[data-appraisal-ack-assignee-name]'); if(a) a.textContent = this.dataset.assigneeName; const r = modal.querySelector('[data-appraisal-ack-reviewer-name]'); if(r) r.textContent = this.dataset.reviewerName; const d = modal.querySelector('[data-appraisal-ack-datetime]'); if(d) d.textContent = this.dataset.acknowledgedAt; const rem = modal.querySelector('[data-appraisal-ack-remark]'); if(rem) rem.textContent = this.dataset.acknowledgementRemark || 'No remark provided.'; modal.classList.remove('hidden'); modal.classList.add('flex'); }">
                            <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all duration-200 {{ $isCompleted ? 'bg-success-300 text-white shadow-sm ring-2 ring-success-200 group-hover:ring-success-300 dark:bg-success-400 dark:ring-success-900/40' : ($isActive ? 'border-2 border-success-300 bg-white text-success-400 ring-4 ring-success-100 group-hover:ring-success-200 dark:bg-darkblack-600 dark:ring-success-900/30 font-extrabold' : 'border border-bgray-300 bg-bgray-100 text-bgray-500 group-hover:border-bgray-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400') }}">
                                @if ($isCompleted)
                                    <svg class="h-4 w-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    {{ $step['number'] }}
                                @endif

                                @if ($isActive)
                                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-300 opacity-75"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-success-400"></span>
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2.5 text-center w-full px-1">
                                <p class="text-xs font-bold leading-tight underline decoration-dotted underline-offset-2 {{ $isCompleted ? 'text-bgray-900 dark:text-white group-hover:text-success-400' : ($isActive ? 'text-success-400 dark:text-success-300 font-extrabold' : 'text-bgray-600 dark:text-bgray-300 group-hover:text-bgray-900') }}">
                                    {{ $step['title'] }}
                                </p>
                                @if (!empty($step['subtitle']))
                                    <p class="mt-0.5 text-[11px] font-medium truncate max-w-[130px] mx-auto {{ $isActive ? 'text-success-500 dark:text-success-400 font-semibold' : 'text-bgray-600 dark:text-bgray-300' }}" title="{{ $step['subtitle'] }}">
                                        {{ $step['subtitle'] }}
                                    </p>
                                @endif
                                <p class="mt-0.5 text-[10px] font-medium {{ $isCompleted ? 'text-bgray-700 dark:text-bgray-300' : ($isActive ? 'text-success-400 dark:text-success-300 font-semibold' : 'text-bgray-700 dark:text-bgray-300') }}">
                                    @if ($stepDate)
                                        @appDateTime($stepDate)
                                    @else
                                        --
                                    @endif
                                </p>
                            </div>
                        </button>
                    @else
                        <!-- Non-ack Step Circle -->
                        <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all duration-200 {{ $isCompleted ? 'bg-success-300 text-white shadow-sm dark:bg-success-400' : ($isActive ? 'border-2 border-success-300 bg-white text-success-400 ring-4 ring-success-100 dark:bg-darkblack-600 dark:ring-success-900/30 font-extrabold' : 'border border-bgray-300 bg-bgray-100 text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400') }}">
                            @if ($isCompleted)
                                <svg class="h-4 w-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                {{ $step['number'] }}
                            @endif

                            @if ($isActive)
                                <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-300 opacity-75"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-success-400"></span>
                                </span>
                            @endif
                        </div>

                        <!-- Title, Subtitle, and Date/Time Below Bubble -->
                        <div class="mt-2.5 text-center w-full px-1">
                            <p class="text-xs font-bold leading-tight {{ $isCompleted ? 'text-bgray-900 dark:text-white' : ($isActive ? 'text-success-400 dark:text-success-300 font-extrabold' : 'text-bgray-600 dark:text-bgray-300') }}">
                                {{ $step['title'] }}
                            </p>
                            @if (!empty($step['subtitle']))
                                <p class="mt-0.5 text-[11px] font-medium truncate max-w-[130px] mx-auto {{ $isActive ? 'text-success-500 dark:text-success-400 font-semibold' : 'text-bgray-600 dark:text-bgray-300' }}" title="{{ $step['subtitle'] }}">
                                    {{ $step['subtitle'] }}
                                </p>
                            @endif
                            <p class="mt-0.5 text-[10px] font-medium {{ $isCompleted ? 'text-bgray-700 dark:text-bgray-300' : ($isActive ? 'text-success-400 dark:text-success-300 font-semibold' : 'text-bgray-700 dark:text-bgray-300') }}">
                                @if ($stepDate)
                                    @appDateTime($stepDate)
                                @else
                                    --
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Acknowledgement Details Show Modal -->
    <div class="modal fixed inset-0 z-[95] hidden items-center justify-center overflow-y-auto" data-appraisal-ack-details-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" onclick="const m = this.closest('[data-appraisal-ack-details-modal]'); if(m) { m.classList.add('hidden'); m.classList.remove('flex'); }"></div>

        <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                    <h3 class="text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-ack-modal-title>Acknowledgement Details</h3>
                    <button type="button" class="text-2xl leading-none text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" onclick="const m = this.closest('[data-appraisal-ack-details-modal]'); if(m) { m.classList.add('hidden'); m.classList.remove('flex'); }" aria-label="Close">×</button>
                </div>

                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-3.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Assignee</p>
                            <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white" data-appraisal-ack-assignee-name>--</p>
                        </div>
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-3.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Target Reviewer</p>
                            <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white" data-appraisal-ack-reviewer-name>--</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-3.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Acknowledged Date & Time</p>
                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white" data-appraisal-ack-datetime>--</p>
                    </div>

                    <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-3.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Acknowledgement Remark</p>
                        <p class="mt-1 text-sm font-medium text-bgray-800 dark:text-bgray-100 whitespace-pre-line" data-appraisal-ack-remark>No remark provided.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                    <button type="button" class="rounded-lg bg-bgray-200 px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-300 dark:bg-darkblack-500 dark:text-bgray-100 dark:hover:bg-darkblack-400" onclick="const m = this.closest('[data-appraisal-ack-details-modal]'); if(m) { m.classList.add('hidden'); m.classList.remove('flex'); }">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
