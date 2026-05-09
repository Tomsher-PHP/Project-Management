@php
    $selectedDateLabel = \Illuminate\Support\Carbon::parse($selectedDateValue)->format('d M Y');
@endphp

<section class="rounded-[18px] border border-[var(--workspace-border)] bg-white px-5 py-5 shadow-[var(--workspace-panel-shadow)] dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-7 sm:py-6" data-user-timeline-root data-user-timeline-url="{{ route('user.workspace') }}" data-user-timeline-selected-date="{{ $selectedDateValue }}" data-user-timeline-today="{{ $todayDate }}" aria-busy="false">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex items-start justify-center xl:justify-start">
            @if (!empty($workspaceGreetingLabel))
                <div>
                    <h2 class="text-[25px] font-extrabold leading-tight tracking-normal text-[#172033] dark:text-bgray-50">{{ $workspaceGreetingLabel }}</h2>
                    @if (!empty($workspaceGreetingDayName))
                        <p class="mt-1 text-sm font-semibold text-[#6b7280] dark:text-bgray-300">{{ $workspaceGreetingDayName }}</p>
                    @endif
                </div>
            @else
                <h2 class="text-[25px] font-extrabold leading-tight tracking-normal text-[#172033] dark:text-bgray-50">Daily Timeline</h2>
            @endif
        </div>

        <div class="flex justify-center xl:flex-1">
            <div class="flex flex-wrap items-center justify-center gap-2 rounded-md border border-bgray-400 bg-white px-1 py-1 dark:border-darkblack-300 dark:bg-darkblack-500">
                <button type="button" data-user-timeline-prev class="rounded-md px-3 py-2 text-sm font-medium text-bgray-700 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                    Previous
                </button>

                <button type="button" data-user-timeline-today class="rounded-md px-3 py-2 text-sm font-semibold text-success-400 transition hover:bg-white hover:text-success-500 dark:text-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-200">
                    Today
                </button>

                <div class="relative">
                    <button type="button" data-user-timeline-picker-button class="flex h-10 w-10 items-center justify-center rounded-md text-bgray-600 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Open calendar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                        </svg>
                    </button>

                    <input type="text" value="{{ $selectedDateValue }}" class="datepicker absolute left-0 top-0 h-0 w-0 opacity-0 pointer-events-none" data-user-timeline-picker data-format="Y-m-d" aria-label="Select daily timeline date" readonly>
                </div>

                <span class="min-w-[100px] px-1 text-center text-base font-semibold text-bgray-800 dark:text-bgray-50" data-user-timeline-date-label>
                    {{ $selectedDateLabel }}
                </span>

                <button type="button" data-user-timeline-next class="rounded-md px-3 py-2 text-sm font-medium text-bgray-700 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-black">
                    Next
                </button>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 text-center xl:justify-items-end">
            <div>
                <p class="text-[26px] font-extrabold leading-none" style="color: color-mix(in srgb, {{ $assignedShift['color_code'] ?? '#f3f4f6' }} 78%, #000 22%);">{{ $shiftSummaryDuration ?? '--' }}</p>
                <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280] dark:text-bgray-300">
                    <span class="h-2.5 w-2.5 rounded-sm" style="background-color: color-mix(in srgb, {{ $assignedShift['color_code'] ?? '#f3f4f6' }} 78%, #000 22%);"></span>
                    Shift
                </p>
            </div>

            <div>
                <p class="text-[26px] font-extrabold leading-none text-[#4f5bff]">{{ $workedSummaryDuration ?? '0m' }}</p>
                <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280] dark:text-bgray-300">
                    <span class="h-2.5 w-2.5 rounded-sm bg-[#4f5bff]"></span>
                    Worked
                </p>
            </div>

            <div>
                <p class="text-[26px] font-extrabold leading-none text-[#d78900]">{{ $breakSummaryDuration ?? '0m' }}</p>
                <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280] dark:text-bgray-300">
                    <span class="h-2.5 w-2.5 rounded-sm bg-[#d78900]"></span>
                    Break
                </p>
            </div>
        </div>
    </div>

    <p class="mt-3 hidden text-center text-xs font-semibold text-[#d14343] dark:text-red-300 xl:text-left" data-user-timeline-error></p>

    <div class="daily-timeline-scroll overflow-x-auto pb-1">
        <div class="daily-timeline min-w-[980px]">
            <div class="daily-timeline__rail">
                <div class="daily-timeline__ticks" aria-hidden="true"></div>

                <!-- Worked Task Start-->
                @foreach ($workedTaskSegments ?? [] as $segment)
                    <button type="button" class="daily-timeline__segment daily-timeline__segment--work" style="left: calc({{ $segment['left'] }}% + 0px); width: calc({{ $segment['width'] }}% - 0px);" data-tooltip-label="{{ $segment['task_name'] }} | {{ $segment['start_label'] }} - {{ $segment['end_label'] }} | {{ $segment['duration_label'] }}" aria-label="{{ $segment['task_name'] }} {{ $segment['duration_label'] }}">
                    </button>
                @endforeach
                <!-- Worked Task End-->

                <!-- Break Start-->
                @foreach ($breakTaskSegments ?? [] as $segment)
                    <button type="button" class="daily-timeline__segment daily-timeline__segment--break" style="left: calc({{ $segment['left'] }}% + 0px); width: calc({{ $segment['width'] }}% - 0px);" data-tooltip-label="{{ $segment['tooltip_label'] }}" aria-label="Break {{ $segment['start_label'] }} {{ $segment['end_label'] }} {{ $segment['duration_label'] }}">
                    </button>
                @endforeach
                <!-- Break End-->

                <!-- Allocated Shift Start-->
                @foreach ($assignedShift['timeline_segments'] ?? [] as $shiftSegment)
                    <div class="daily-timeline__shift daily-timeline__shift--bottom" style="left: {{ $shiftSegment['left'] }}%; width: {{ $shiftSegment['width'] }}%;{{ !empty($shiftSegment['color_code']) ? ' --shift-accent: ' . $shiftSegment['color_code'] . ';' : '' }}" data-tooltip-label="{{ $shiftSegment['tooltip_label'] }}" aria-label="{{ $shiftSegment['tooltip_label'] }}" tabindex="0">
                        @if (!empty($shiftSegment['start_label']))
                            <span>{{ $shiftSegment['start_label'] }}</span>
                        @endif
                        @if (!empty($shiftSegment['end_label']))
                            <span>{{ $shiftSegment['end_label'] }}</span>
                        @endif
                    </div>
                @endforeach
                <!-- Allocated Shift End-->
            </div>

            <div class="daily-timeline__labels" aria-label="24 hour labels">
                <span>00</span><span>01</span><span>02</span><span>03</span><span>04</span><span>05</span>
                <span>06</span><span>07</span><span>08</span><span>09</span><span>10</span><span>11</span>
                <span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span>
                <span>18</span><span>19</span><span>20</span><span>21</span><span>22</span><span>23</span><span>00</span>
            </div>
        </div>
    </div>
</section>
