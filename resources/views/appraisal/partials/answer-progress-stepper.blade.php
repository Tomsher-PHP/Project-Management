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
                @endphp

                <div class="relative flex flex-1 flex-col items-center {{ !$isLast ? 'min-w-[120px] sm:min-w-[140px]' : 'min-w-[90px]' }}">
                    {{-- Connecting Line Behind Bubble --}}
                    @if (!$isLast)
                        <div class="absolute top-4 left-[50%] z-0 h-0.5 w-full transition-colors duration-200 {{ $isCompleted ? 'bg-success-300 dark:bg-success-400' : 'bg-bgray-200 dark:bg-darkblack-400' }}"></div>
                    @endif

                    {{-- Step Circle Bubble --}}
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

                    {{-- Title and Subtitle Below Bubble --}}
                    <div class="mt-2.5 text-center w-full px-1">
                        <p class="text-xs font-bold leading-tight {{ $isCompleted ? 'text-bgray-900 dark:text-white' : ($isActive ? 'text-success-400 dark:text-success-300 font-extrabold' : 'text-bgray-600 dark:text-bgray-300') }}">
                            {{ $step['title'] }}
                        </p>
                        @if (!empty($step['subtitle']))
                            <p class="mt-0.5 text-[11px] font-medium truncate max-w-[130px] mx-auto {{ $isActive ? 'text-success-500 dark:text-success-400 font-semibold' : 'text-bgray-600 dark:text-bgray-300' }}" title="{{ $step['subtitle'] }}">
                                {{ $step['subtitle'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
