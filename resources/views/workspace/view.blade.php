@extends('layouts.master')

@push('styles')
    <style>
        :root {
            --workspace-ink: #111653;
            --workspace-muted: #6677a7;
            --workspace-blue: #0866ff;
            --workspace-border: #edf1f7;
            --workspace-panel-shadow: 0 10px 28px rgba(18, 25, 95, 0.06);
            --workspace-soft-shadow: 0 6px 18px rgba(18, 25, 95, 0.04);
        }

        [data-user-workspace] {
            color: var(--workspace-ink);
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        [data-user-workspace] button,
        [data-user-workspace] input,
        [data-user-workspace] select {
            letter-spacing: 0;
        }

        .kanban-ghost {
            background: transparent !important;
            border: 1px dashed rgba(166, 167, 168, 0.8);
            box-sizing: border-box;
            color: transparent !important;
        }

        .kanban-ghost * {
            visibility: hidden !important;
        }

        .kanban-chosen {
            transform: scale(1.02);
        }

        .kanban-drag {
            transform: rotate(2deg);
        }
    </style>
@endpush

@push('navbar-actions')
    <div id="running-task-bar" class="hidden min-w-[520px] items-center gap-4 rounded-[14px] border border-[#edf1f7] bg-white px-4 py-3 shadow-[0_8px_24px_rgba(18,25,95,0.08)]">
        <span class="h-3.5 w-3.5 shrink-0 rounded-full bg-[#0866ff]"></span>
        <div class="min-w-0 flex-1">
            <p id="running-task-project" class="truncate text-[12px] font-extrabold uppercase leading-none text-[#0866ff]">WORKING ACTIVITY</p>
            <h2 id="running-task-name" class="mt-2 truncate text-[15px] font-extrabold leading-none text-[#111653]"></h2>
        </div>
        <p id="running-task-timer" class="whitespace-nowrap px-5 text-[16px] font-extrabold leading-none text-[#111653]">00:00:00</p>
        <button id="running-task-pause" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f1f3f7] text-[#111653] transition duration-200 hover:bg-[#e7ecf5]" aria-label="Resume task">
            <span aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 3.5V12.5L12 8L5 3.5Z" fill="currentColor" />
                </svg>
            </span>
        </button>
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
            <span class="h-4 w-4 rounded-[3px] bg-red-600"></span>
        </span>
    </div>
@endpush
@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] sm:px-5 xl:px-4" data-user-workspace>
        <div class="space-y-2.5">

            <section class="rounded-[18px] border border-[var(--workspace-border)] bg-white px-5 py-5 shadow-[var(--workspace-panel-shadow)] sm:px-7 sm:py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-[25px] font-extrabold leading-tight tracking-normal text-[#172033]">Daily Timeline</h2>
                    </div>

                    <div class="grid grid-cols-3 gap-6 text-center">
                        <div>
                            <p class="text-[26px] font-extrabold leading-none" style="color: color-mix(in srgb, {{ $assignedShift['color_code'] ?? '#f3f4f6' }} 78%, #000 22%);">{{ $shiftSummaryDuration ?? '--' }}</p>
                            <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280]">
                                <span class="h-2.5 w-2.5 rounded-sm" style="background-color: color-mix(in srgb, {{ $assignedShift['color_code'] ?? '#f3f4f6' }} 78%, #000 22%);"></span>
                                Shift
                            </p>
                        </div>

                        <div>
                            <p class="text-[26px] font-extrabold leading-none text-[#4f5bff]">{{ $workedSummaryDuration ?? '0m' }}</p>
                            <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280]">
                                <span class="h-2.5 w-2.5 rounded-sm bg-[#4f5bff]"></span>
                                Worked
                            </p>
                        </div>

                        <div>
                            <p class="text-[26px] font-extrabold leading-none text-[#d78900]">{{ $breakSummaryDuration ?? '0m' }}</p>
                            <p class="mt-2 flex items-center justify-center gap-1.5 text-[12px] font-extrabold uppercase tracking-wide text-[#6b7280]">
                                <span class="h-2.5 w-2.5 rounded-sm bg-[#d78900]"></span>
                                Break
                            </p>
                        </div>
                    </div>
                </div>

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

                        <!-- Timeline Labels -->
                        <div class="daily-timeline__labels" aria-label="24 hour labels">
                            <span>00</span><span>01</span><span>02</span><span>03</span><span>04</span><span>05</span>
                            <span>06</span><span>07</span><span>08</span><span>09</span><span>10</span><span>11</span>
                            <span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span>
                            <span>18</span><span>19</span><span>20</span><span>21</span><span>22</span><span>23</span><span>00</span>
                        </div>
                    </div>
                </div>
            </section>
            @php
                $boardTaskTotal = collect($tasksByStatus ?? [])->sum(fn($column) => (int) ($column['total'] ?? 0));
                $boardStatusTotal = $boardStatuses->count();
            @endphp

            <section class="space-y-6" data-project-tasks-root data-project-task-response-mode="reload">
                <div class="overflow-hidden rounded-[14px] border border-[var(--workspace-border)] bg-white shadow-[var(--workspace-panel-shadow)] dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="border-b border-[#edf1f7] bg-white px-4 py-3 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex h-6 w-6 items-center justify-center text-[#111653]">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM4 13h7v7H4v-7Zm9-3h7v10h-7V10Z" />
                                    </svg>
                                </span>
                                <h3 class="text-[17px] font-extrabold tracking-normal text-[#111653] dark:text-white">Work Board</h3>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-[#e7ecf5] bg-white px-4 text-sm font-extrabold text-[#111653] shadow-[var(--workspace-soft-shadow)] transition hover:border-[#d7e3f6] hover:bg-[#fbfdff]">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 0 1 1-1h12a1 1 0 0 1 .8 1.6L12 11v4a1 1 0 0 1-.553.894l-2 1A1 1 0 0 1 8 16v-5L3.2 4.6A1 1 0 0 1 3 4Z" />
                                    </svg>
                                    <span>All Tasks</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg bg-[#0866ff] px-4 text-sm font-extrabold text-white shadow-[0_10px_20px_rgba(8,102,255,0.2)] transition hover:bg-[#075ce6]">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                                    </svg>
                                    <span>Add Task</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="custom-scroll overflow-x-auto bg-white dark:bg-darkblack-700">
                        <div id="kanban-container" class="flex h-[calc(100vh-620px)] min-h-[410px] min-w-max gap-3.5 p-3.5">
                            @include('tasks.kanban._board', ['boardStatuses' => $boardStatuses])
                        </div>
                    </div>
                </div>

                <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
                    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

                    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                        <div class="relative z-10 w-full max-w-7xl" data-project-task-detail-content></div>
                    </div>
                </div>
            </section>

        </div>
        <style>
            .daily-timeline-scroll {
                scrollbar-width: thin;
                scrollbar-color: #a9c9e4 transparent;
            }

            .daily-timeline-scroll::-webkit-scrollbar {
                height: 8px;
            }

            .daily-timeline-scroll::-webkit-scrollbar-track {
                background: transparent;
            }

            .daily-timeline-scroll::-webkit-scrollbar-thumb {
                background: #a9c9e4;
                border-radius: 9999px;
            }

            .daily-timeline {
                position: relative;
            }

            .daily-timeline__rail {
                position: relative;
                height: 110px;
                overflow: visible;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .daily-timeline__rail::before,
            .daily-timeline__rail::after {
                content: "";
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
            }

            .daily-timeline__rail::before {
                height: 60px;
                background-image: repeating-linear-gradient(90deg,
                        rgba(194, 203, 216, 0.86) 0 1px,
                        transparent 1px 0.6944%);
                -webkit-mask-image: linear-gradient(to top, #000 0%, rgba(0, 0, 0, 0.78) 72%, rgba(0, 0, 0, 0.42) 100%);
                mask-image: linear-gradient(to top, #000 0%, rgba(0, 0, 0, 0.78) 72%, rgba(0, 0, 0, 0.42) 100%);
            }

            .daily-timeline__rail::after {
                height: 70px;
                background-image: repeating-linear-gradient(90deg,
                        rgba(43, 50, 67, 0.88) 0 2px,
                        transparent 2px 4.1667%);
                -webkit-mask-image: linear-gradient(to top, #000 0%, rgba(0, 0, 0, 0.84) 72%, rgba(0, 0, 0, 0.48) 100%);
                mask-image: linear-gradient(to top, #000 0%, rgba(0, 0, 0, 0.84) 72%, rgba(0, 0, 0, 0.48) 100%);
            }

            .daily-timeline__ticks {
                position: absolute;
                left: 0;
                right: 0;
                top: 34px;
                height: 35px;
                border-radius: 6px;
                background: #e9e9e9;
                pointer-events: none;
                z-index: 3;
                opacity: 60%;
            }

            .daily-timeline__ticks::after {
                display: none;
            }

            .daily-timeline__segment {
                position: absolute;
                top: 34px;
                z-index: 2;
                display: inline-flex;
                height: 35px;
                /* min-width: 36px; */
                align-items: center;
                justify-content: center;
                /* gap: 6px; */
                border: 0;
                border-radius: 0px;
                color: #fff;
                font-size: 0;
                font-weight: 800;
                line-height: 1;
                box-shadow: 0 8px 14px rgba(8, 102, 255, 0.12);
                white-space: nowrap;
                transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
                z-index: 4;
                opacity: 70%;
            }

            .daily-timeline__segment::before {
                content: attr(data-tooltip-label);
                position: absolute;
                left: 50%;
                top: -31px;
                display: inline-flex;
                height: 23px;
                /* min-width: 48px; */
                transform: translateX(-50%);
                align-items: center;
                justify-content: center;
                border-radius: 5px;
                background: #0757d6;
                padding: 0 8px;
                color: #fff;
                font-size: 11px;
                font-weight: 800;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.16s ease, visibility 0.16s ease;
            }

            .daily-timeline__segment::after {
                content: "";
                position: absolute;
                left: 50%;
                top: -8px;
                transform: translateX(-50%);
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 6px solid #0757d6;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.16s ease, visibility 0.16s ease;
            }

            .daily-timeline__segment--break {
                z-index: 3;
            }

            .daily-timeline__segment:hover {
                filter: brightness(1.04);
                transform: translateY(-1px);
                box-shadow: 0 10px 18px rgba(18, 25, 95, 0.1);
            }

            .daily-timeline__segment:hover::before,
            .daily-timeline__segment:hover::after,
            .daily-timeline__segment:focus-visible::before,
            .daily-timeline__segment:focus-visible::after {
                opacity: 1;
                visibility: visible;
            }

            .daily-timeline__segment--work {
                background: #0866ff;
            }

            .daily-timeline__segment--break {
                background: #ee9802;
                color: #fff;
                box-shadow: 0 8px 14px rgba(224, 154, 0, 0.16);
            }

            .daily-timeline__segment--break::before {
                background: #ee9802;
            }

            .daily-timeline__segment--break::after {
                border-top-color: #ee9802;
            }

            .daily-timeline__shift {
                --shift-accent: #0866ff;
                position: absolute;
                z-index: 1;
                display: flex;
                height: 25px;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                border: 2px solid var(--shift-accent);
                border-radius: 8px;
                background:
                    linear-gradient(90deg, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0.08) 100%),
                    color-mix(in srgb, var(--shift-accent) 88%, #000 12%);
                padding: 0 0.35rem;
                color: var(--shift-accent);
                font-size: 0.68rem;
                font-weight: 600;
                box-shadow: 0 8px 14px rgba(3, 119, 201, 0.14);
                opacity: 100%;
            }

            .daily-timeline__shift::before {
                content: attr(data-tooltip-label);
                position: absolute;
                left: 50%;
                top: calc(100% + 10px);
                display: inline-flex;
                height: 23px;
                min-width: 48px;
                transform: translateX(-50%);
                align-items: center;
                justify-content: center;
                border-radius: 5px;
                background: color-mix(in srgb, var(--shift-accent) 82%, #000 18%);
                padding: 0 8px;
                color: #fff;
                font-size: 11px;
                font-weight: 800;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.16s ease, visibility 0.16s ease;
            }

            .daily-timeline__shift::after {
                content: "";
                position: absolute;
                left: 50%;
                top: calc(100% + 4px);
                transform: translateX(-50%);
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-bottom: 6px solid color-mix(in srgb, var(--shift-accent) 82%, #000 18%);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.16s ease, visibility 0.16s ease;
            }

            .daily-timeline__shift--bottom {
                top: 75px;
            }

            .daily-timeline__shift>span {
                border: 0;
                border-radius: 6px;
                background: transparent;
                padding: 0 0.45rem;
                color: #172033;
                font-size: 0.68rem;
                line-height: 1;
            }

            .daily-timeline__shift:hover::before,
            .daily-timeline__shift:hover::after,
            .daily-timeline__shift:focus-visible::before,
            .daily-timeline__shift:focus-visible::after {
                opacity: 1;
                visibility: visible;
            }

            .daily-timeline__labels {
                display: flex;
                justify-content: space-between;
                margin-top: 6px;
                padding: 0;
                color: #6b7280;
                font-size: 0.85rem;
                font-weight: 800;
            }

            .daily-timeline__labels span {
                display: flex;
                width: 0;
                flex: 0 0 0;
                justify-content: center;
                white-space: nowrap;
            }

            .daily-timeline__labels span:first-child {
                justify-content: flex-start;
            }

            .daily-timeline__labels span:last-child {
                justify-content: flex-end;
            }

            .timeline-card {
                position: relative;
            }

            .timeline-chart-wrap {
                position: relative;
                display: flex;
                justify-content: center;
                overflow-x: auto;
                padding-bottom: 0.25rem;
            }

            .timeline-chart {
                position: relative;
                width: 1280px;
                flex: 0 0 auto;
                margin-inline: auto;
                padding: 0.75rem 0 0.25rem;
            }

            .timeline-grid {
                display: grid;
                gap: 0.35rem;
                align-items: end;
            }

            .timeline-slot {
                position: relative;
                height: 168px;
                border-radius: 16px;
                background: linear-gradient(180deg, #f6f7f9 0%, #edf1f5 100%);
                overflow: hidden;
                transition: background-color 0.18s ease, box-shadow 0.18s ease;
            }

            .timeline-slot.is-highlighted {
                background: linear-gradient(180deg, #eff5ff 0%, #e5eefc 100%);
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.12);
            }

            .timeline-slot__top {
                position: absolute;
                inset: 0.35rem 0.2rem 3.25rem;
                border-radius: 12px 12px 0 0;
                background: rgba(255, 255, 255, 0.72);
            }

            .timeline-slot__worked-fill {
                position: absolute;
                left: 0.2rem;
                right: 0.2rem;
                bottom: 3.25rem;
                appearance: none;
                border: 0;
                padding: 0;
                border-radius: 10px 10px 0 0;
                background: #84c400;
                cursor: pointer;
                transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
            }

            .timeline-slot__worked-fill.is-active {
                transform: translateY(-2px);
                box-shadow: 0 10px 18px rgba(15, 23, 42, 0.14);
            }

            .timeline-track {
                position: absolute;
                left: 0;
                right: 0;
                pointer-events: none;
            }

            .timeline-track--activity {
                bottom: 2.05rem;
                height: 20px;
                background: #dbe2ea;
                border-radius: 0;
            }

            .timeline-track--shift {
                bottom: 1rem;
                height: 12px;
                background: #dbe2ea;
                border-radius: 0;
            }

            .timeline-shift-indicator {
                position: absolute;
                bottom: 1rem;
                height: 12px;
                background: #2563eb;
                border-radius: 0;
                pointer-events: none;
            }

            .timeline-slot__label {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0.2rem;
                text-align: center;
                color: #7a8795;
                font-size: 0.95rem;
                font-weight: 500;
            }

            .timeline-segment {
                position: absolute;
                appearance: none;
                border: 0;
                padding: 0;
                background-clip: padding-box;
                cursor: pointer;
                transition: transform 0.16s ease, opacity 0.16s ease, box-shadow 0.16s ease;
            }

            .timeline-segment--shift {
                background: #16a34a;
            }

            .timeline-segment--offline {
                background: #ef4444;
            }

            .timeline-segment.is-active {
                transform: translateY(-2px);
                box-shadow: 0 10px 18px rgba(15, 23, 42, 0.14);
            }

            .timeline-segment--activity {
                bottom: 2.05rem;
                height: 20px;
                border-radius: 0;
                min-width: 4px;
            }

            .timeline-segment--activity.timeline-segment--shift {
                background: #84c400;
            }

            .timeline-segment--activity.timeline-segment--offline {
                background: #ef4444;
            }

            .timeline-tooltip {
                position: absolute;
                z-index: 4;
                width: 220px;
                border-radius: 16px;
                border: 1px solid #d9e3ef;
                background: rgba(255, 255, 255, 0.96);
                padding: 0.85rem 0.95rem;
                box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
                pointer-events: none;
                backdrop-filter: blur(10px);
            }

            .timeline-tooltip__label {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #64748b;
            }

            .timeline-tooltip__dot {
                height: 8px;
                width: 8px;
                border-radius: 9999px;
            }

            .timeline-tooltip__title {
                margin-top: 0.55rem;
                color: #111827;
                font-size: 0.95rem;
                font-weight: 700;
                line-height: 1.3;
            }

            .timeline-tooltip__meta {
                margin-top: 0.35rem;
                color: #607080;
                font-size: 0.8rem;
                line-height: 1.45;
            }

            .work-time-dial {
                position: relative;
                width: min(100%, 18rem);
                aspect-ratio: 1;
                margin-inline: auto;
            }

            .work-time-dial__ticks {
                position: absolute;
                inset: 0;
                border-radius: 9999px;
                background:
                    repeating-conic-gradient(from -90deg, rgba(100, 116, 139, 0.72) 0deg 1.35deg, transparent 1.35deg 30deg),
                    repeating-conic-gradient(from -90deg, rgba(148, 163, 184, 0.58) 0deg 0.5deg, transparent 0.5deg 6deg);
                -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 28px), #000 calc(100% - 28px) calc(100% - 10px), transparent calc(100% - 10px));
                mask: radial-gradient(farthest-side, transparent calc(100% - 28px), #000 calc(100% - 28px) calc(100% - 10px), transparent calc(100% - 10px));
            }

            .work-time-dial__face {
                position: absolute;
                inset: 2.15rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.95);
                text-align: center;
            }

            .work-time-dial__label {
                color: #27364a;
                font-size: 1.05rem;
                font-weight: 500;
                line-height: 1.4;
            }

            .work-time-dial__value {
                margin-top: 0.55rem;
                color: #6ea400;
                font-size: clamp(2rem, 4vw, 2.6rem);
                font-weight: 700;
                letter-spacing: -0.04em;
                line-height: 1;
            }

            .task-list {
                background: linear-gradient(180deg, #fffdfa 0%, #fff 100%);
            }

            .task-row {
                border-bottom: 1px solid #ece4d8;
                transition: background-color 0.2s ease, opacity 0.2s ease;
            }

            .task-row:last-child {
                border-bottom: 0;
            }

            .task-row--selected,
            .task-row[data-running="true"] {
                background: #f3efe7;
            }

            .task-row[data-completed="true"] {
                opacity: 0.58;
            }

            .task-row.is-undo-pending {
                opacity: 1;
                background: inherit;
            }

            .task-row__inner {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem 1.5rem;
            }

            .task-row__title {
                color: #414141;
                font-size: 1.05rem;
                font-weight: 500;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            .task-row[data-completed="true"] .task-row__title {
                text-decoration: line-through;
            }

            .task-check,
            .task-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 14px;
                border: 1px solid #ddd3c7;
                background: #fff;
                transition: all 0.2s ease;
            }

            .task-check {
                height: 24px;
                width: 24px;
                flex-shrink: 0;
                border-radius: 8px;
                color: transparent;
            }

            .task-check.is-hidden {
                visibility: hidden;
            }

            .task-check svg,
            .task-action-btn svg {
                height: 16px;
                width: 16px;
            }

            .task-row[data-completed="true"] .task-check {
                border-color: #96d6c5;
                background: #8dd1bf;
                color: #fff;
            }

            .task-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                padding: 0.35rem 0.8rem;
                font-size: 0.82rem;
                font-weight: 500;
                line-height: 1;
            }

            .task-pill--project-amber {
                background: #f7ece2;
                color: #8f3b18;
            }

            .task-pill--project-violet {
                background: #eceafb;
                color: #5652b3;
            }

            .task-pill--project-teal {
                background: #dff1eb;
                color: #0d6a60;
            }

            .task-pill--project-stone {
                background: #ddd8cf;
                color: #5a5651;
            }

            .task-pill--project-blue {
                background: #e9eef9;
                color: #6782b9;
            }

            .task-pill--priority-high {
                background: #fbeceb;
                color: #a0372f;
            }

            .task-pill--priority-medium {
                background: #f6ead7;
                color: #8c5b12;
            }

            .task-pill--priority-low {
                background: #e5f2ec;
                color: #5f927f;
            }

            .task-row__actions {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                margin-left: auto;
                flex-shrink: 0;
            }

            .task-row__time {
                min-width: 3.9rem;
                text-align: right;
                color: #47454b;
                font-size: 1.05rem;
                font-weight: 600;
                letter-spacing: 0.04em;
            }

            .task-action-btn {
                height: 42px;
                min-width: 42px;
                gap: 0.4rem;
                padding: 0 0.8rem;
            }

            .task-action-btn--play {
                width: auto;
                color: #346eb9;
                white-space: nowrap;
            }

            .task-action-btn--play [data-task-play-label] {
                color: #6e675f;
                font-weight: 500;
            }

            .task-action-btn--play.is-running {
                border-color: #c8d7ef;
                background: #eef5ff;
                color: #285d9d;
            }

            .task-action-btn--play.is-running [data-task-play-label] {
                color: #6e675f;
            }

            .task-action-btn--done {
                width: 42px;
                padding: 0;
                color: #0d7c5b;
            }

            .task-action-btn--undo {
                width: auto;
                padding: 0 0.95rem;
                color: #6e675f;
                font: inherit;
            }

            .task-action-btn[disabled] {
                cursor: default;
                color: #bfb7ac;
            }

            .task-action-btn.is-hidden {
                display: none;
            }

            .task-summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                border-top: 1px solid #d7d0c5;
                background: #f4f1ea;
                padding: 1rem 1.5rem;
                color: #6e675f;
                font-size: 0.95rem;
                font-weight: 500;
            }

            .task-summary__item {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
            }

            .task-summary__dot {
                height: 12px;
                width: 12px;
                border-radius: 9999px;
            }

            .task-summary__dot--running {
                background: #3a87dd;
            }

            .task-summary__dot--done {
                background: #27a86f;
            }

            .task-summary__dot--pending {
                background: #d8d4cf;
            }

            @media (max-width: 768px) {
                .timeline-chart {
                    width: 1180px;
                }

                .task-row__inner {
                    flex-wrap: wrap;
                }

                .task-row__actions {
                    width: 100%;
                    justify-content: flex-end;
                }

                .task-summary {
                    flex-wrap: wrap;
                }
            }
        </style>
        <script>
            document.title = 'Tomsher Pmt | Project Tasks';

            document.addEventListener('DOMContentLoaded', () => {
                const stickyBar = document.getElementById('running-task-bar');
                const stickyProject = document.getElementById('running-task-project');
                const stickyName = document.getElementById('running-task-name');
                const stickyTimer = document.getElementById('running-task-timer');
                const stickyPauseButton = document.getElementById('running-task-pause');
                const totalBadge = document.getElementById('task-total-badge');
                const runningCount = document.getElementById('task-running-count');
                const doneCount = document.getElementById('task-done-count');
                const pendingCount = document.getElementById('task-pending-count');
                const timelineEl = document.getElementById('task-timeline');
                const timelineTooltip = document.getElementById('task-timeline-tooltip');
                const taskItems = Array.from(document.querySelectorAll('[data-task-item]'));
                const taskButtons = taskItems
                    .map((task) => task.querySelector('[data-task-play-toggle]'))
                    .filter(Boolean);
                let activeButton = null;
                let timerIntervalId = null;
                let stickyDisplayButton = null;
                let selectedTask = taskItems.find((task) => task.dataset.selected === 'true') || null;
                const undoTimers = new Map();
                const undoDelayMs = 4000;
                const timelineSlots = Array.from({
                    length: 24
                }, (_, index) => {
                    const hour = index % 12 === 0 ? 12 : index % 12;
                    const suffix = index < 12 ? 'AM' : 'PM';

                    return {
                        label: `${hour} ${suffix}`,
                        start: index * 60,
                        end: (index + 1) * 60
                    };
                });
                const timelineSegments = [{
                        type: 'shift',
                        title: 'Resolve campaign blocker',
                        project: 'Q2 Marketing',
                        details: 'Sprint push for campaign blocker resolution.',
                        start: 540,
                        end: 590,
                        height: 76
                    },
                    {
                        type: 'offline',
                        title: 'Offline break',
                        project: 'Status update',
                        details: 'Connectivity drop during morning sync.',
                        start: 590,
                        end: 600,
                        height: 76
                    },
                    {
                        type: 'shift',
                        title: 'Review API v3 auth spec',
                        project: 'API v3',
                        details: 'Security review and token validation pass.',
                        start: 600,
                        end: 655,
                        height: 118
                    },
                    {
                        type: 'offline',
                        title: 'Offline handoff',
                        project: 'Status update',
                        details: 'Short offline period while waiting for stakeholder feedback.',
                        start: 655,
                        end: 667,
                        height: 118
                    },
                    {
                        type: 'shift',
                        title: 'Update design token file',
                        project: 'Design System',
                        details: 'Token cleanup and spacing variable updates.',
                        start: 667,
                        end: 750,
                        height: 132
                    },
                    {
                        type: 'offline',
                        title: 'Offline interruption',
                        project: 'Status update',
                        details: 'Brief interruption before lunch wrap-up.',
                        start: 750,
                        end: 765,
                        height: 132
                    },
                    {
                        type: 'shift',
                        title: 'Write Q2 retro notes',
                        project: 'Internal',
                        details: 'Retro draft and action item capture.',
                        start: 765,
                        end: 830,
                        height: 120
                    },
                    {
                        type: 'shift',
                        title: 'Schedule design review',
                        project: 'Design System',
                        details: 'Review scheduling and attendee confirmation.',
                        start: 850,
                        end: 915,
                        height: 132
                    },
                    {
                        type: 'offline',
                        title: 'Offline review gap',
                        project: 'Status update',
                        details: 'Brief away period before the afternoon push.',
                        start: 915,
                        end: 925,
                        height: 132
                    },
                    {
                        type: 'shift',
                        title: 'Resolve campaign blocker',
                        project: 'Q2 Marketing',
                        details: 'Final fixes and stakeholder follow-up.',
                        start: 925,
                        end: 1035,
                        height: 138
                    },
                    {
                        type: 'offline',
                        title: 'Offline check-in',
                        project: 'Status update',
                        details: 'Short break before handoff updates.',
                        start: 1035,
                        end: 1048,
                        height: 138
                    },
                    {
                        type: 'shift',
                        title: 'Review API v3 auth spec',
                        project: 'API v3',
                        details: 'Late-day verification and summary notes.',
                        start: 1048,
                        end: 1080,
                        height: 124
                    }
                ];
                const timelineShiftWindow = {
                    start: 540,
                    end: 1110
                };

                const playIcon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.5V12.5L12 8L5 3.5Z" fill="currentColor" /></svg>';
                const pauseIcon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3H7V13H5V3Z" fill="currentColor" /><path d="M9 3H11V13H9V3Z" fill="currentColor" /></svg>';

                const minutesToLabel = (minutes) => {
                    const hours24 = Math.floor(minutes / 60);
                    const mins = String(minutes % 60).padStart(2, '0');
                    const suffix = hours24 >= 12 ? 'PM' : 'AM';
                    const hours12 = hours24 % 12 === 0 ? 12 : hours24 % 12;

                    return `${hours12}:${mins} ${suffix}`;
                };

                const formatDuration = (totalSeconds) => {
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                    const seconds = String(totalSeconds % 60).padStart(2, '0');

                    return `${hours}:${minutes}:${seconds}`;
                };

                const clearTimelineHighlight = () => {
                    timelineEl?.querySelectorAll('.timeline-slot').forEach((slot) => {
                        slot.classList.remove('is-highlighted');
                    });

                    timelineEl?.querySelectorAll('.timeline-segment, .timeline-slot__worked-fill').forEach((segment) => {
                        segment.classList.remove('is-active');
                    });
                };

                const hideTimelineTooltip = () => {
                    timelineTooltip?.classList.add('hidden');
                    clearTimelineHighlight();
                };

                const showTimelineTooltip = (segment, event) => {
                    if (!timelineEl || !timelineTooltip) {
                        return;
                    }

                    clearTimelineHighlight();

                    timelineEl.querySelectorAll(`[data-segment-id="${segment.id}"]`).forEach((entry) => {
                        entry.classList.add('is-active');
                    });

                    timelineEl.querySelectorAll('.timeline-slot').forEach((slot) => {
                        const slotStart = Number(slot.dataset.slotStart);
                        const slotEnd = Number(slot.dataset.slotEnd);
                        if (segment.start < slotEnd && segment.end > slotStart) {
                            slot.classList.add('is-highlighted');
                        }
                    });

                    const dotColor = segment.type === 'shift' ? '#16a34a' : '#ef4444';
                    timelineTooltip.innerHTML = `
                  <div class="timeline-tooltip__label">
                      <span class="timeline-tooltip__dot" style="background:${dotColor}"></span>
                      <span>${segment.type}</span>
                  </div>
                  <p class="timeline-tooltip__title">${segment.title}</p>
                  <p class="timeline-tooltip__meta">${segment.project}</p>
                  <p class="timeline-tooltip__meta">${minutesToLabel(segment.start)} - ${minutesToLabel(segment.end)}</p>
                  <p class="timeline-tooltip__meta">${segment.details}</p>
              `;

                    const timelineRect = timelineEl.getBoundingClientRect();
                    const x = event.clientX - timelineRect.left + 16;
                    const y = event.clientY - timelineRect.top - 22;

                    timelineTooltip.classList.remove('hidden');
                    const tooltipWidth = timelineTooltip.offsetWidth;
                    const tooltipHeight = timelineTooltip.offsetHeight;
                    const maxLeft = timelineRect.width - tooltipWidth - 8;
                    const left = Math.max(8, Math.min(x, maxLeft));
                    const top = Math.max(8, y - tooltipHeight);

                    timelineTooltip.style.left = `${left}px`;
                    timelineTooltip.style.top = `${top}px`;
                };

                const renderTimeline = () => {
                    if (!timelineEl) {
                        return;
                    }

                    const timelineStart = 0;
                    const timelineEnd = 24 * 60;
                    const totalMinutes = timelineEnd - timelineStart;
                    const gridColumns = timelineSlots.map((slot) => `${slot.end - slot.start}fr`).join(' ');

                    const slotMarkup = timelineSlots.map((slot, index) => {
                        const overlappingShiftSegments = timelineSegments
                            .map((segment, segmentIndex) => ({
                                ...segment,
                                id: segmentIndex
                            }))
                            .filter((segment) => segment.type === 'shift' && segment.start < slot.end && segment.end > slot.start);

                        const workedMinutes = overlappingShiftSegments.reduce((total, segment) => {
                            const overlapStart = Math.max(segment.start, slot.start);
                            const overlapEnd = Math.min(segment.end, slot.end);
                            return total + Math.max(0, overlapEnd - overlapStart);
                        }, 0);

                        const workedHeight = Math.max(0, Math.min(100, (workedMinutes / (slot.end - slot.start)) * 100));
                        const primarySegment = overlappingShiftSegments[0];

                        return `
                      <div class="timeline-slot" data-slot-index="${index}" data-slot-start="${slot.start}" data-slot-end="${slot.end}">
                          <div class="timeline-slot__top"></div>
                          ${primarySegment ? `
                                                                      <button
                                                                          type="button"
                                                                          class="timeline-slot__worked-fill"
                                                                          style="height:${workedHeight}%;"
                                                                          data-segment-id="${primarySegment.id}"
                                                                          aria-label="${primarySegment.title} from ${minutesToLabel(primarySegment.start)} to ${minutesToLabel(primarySegment.end)}"
                                                                      ></button>
                                                                  ` : ''}
                          <span class="timeline-slot__label">${slot.label}</span>
                      </div>
                  `;
                    }).join('');

                    const activityMarkup = timelineSegments.map((segment, index) => {
                        const left = ((segment.start - timelineStart) / totalMinutes) * 100;
                        const width = ((segment.end - segment.start) / totalMinutes) * 100;

                        return `
                      <button
                          type="button"
                          class="timeline-segment timeline-segment--activity timeline-segment--${segment.type}"
                          style="left:${left}%;width:${width}%;"
                          data-segment-id="${index}"
                          aria-label="${segment.title} from ${minutesToLabel(segment.start)} to ${minutesToLabel(segment.end)}"
                      ></button>
                  `;
                    }).join('');

                    const shiftLeft = ((timelineShiftWindow.start - timelineStart) / totalMinutes) * 100;
                    const shiftWidth = ((timelineShiftWindow.end - timelineShiftWindow.start) / totalMinutes) * 100;
                    const shiftIndicatorMarkup = `
                  <div
                      class="timeline-shift-indicator"
                      style="left:${shiftLeft}%;width:${shiftWidth}%;"
                      aria-hidden="true"
                  ></div>
              `;

                    timelineEl.innerHTML = `
                  <div class="timeline-grid" style="grid-template-columns:${gridColumns};">${slotMarkup}</div>
                  <div class="timeline-track timeline-track--activity" aria-hidden="true"></div>
                  <div class="timeline-track timeline-track--shift" aria-hidden="true"></div>
                  ${activityMarkup}
                  ${shiftIndicatorMarkup}
              `;

                    timelineEl.querySelectorAll('.timeline-segment, .timeline-slot__worked-fill').forEach((node) => {
                        const segment = timelineSegments[Number(node.dataset.segmentId)];
                        segment.id = Number(node.dataset.segmentId);

                        node.addEventListener('mouseenter', (event) => {
                            showTimelineTooltip(segment, event);
                        });

                        node.addEventListener('mousemove', (event) => {
                            showTimelineTooltip(segment, event);
                        });

                        node.addEventListener('mouseleave', () => {
                            hideTimelineTooltip();
                        });
                    });

                    timelineEl.querySelectorAll('.timeline-slot').forEach((slot) => {
                        slot.addEventListener('mouseenter', () => {
                            clearTimelineHighlight();
                            slot.classList.add('is-highlighted');
                        });
                        slot.addEventListener('mouseleave', () => {
                            clearTimelineHighlight();
                        });
                    });

                    timelineEl.addEventListener('mouseleave', () => {
                        hideTimelineTooltip();
                    });
                };

                const formatTaskTime = (totalSeconds) => {
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = String(totalSeconds % 60).padStart(2, '0');

                    if (hours > 0) {
                        return `${hours}:${String(minutes).padStart(2, '0')}:${seconds}`;
                    }

                    return `${minutes}:${seconds}`;
                };

                const getTaskDetails = (button) => {
                    const article = button.closest('[data-task-item]');
                    const name = article?.querySelector('[data-task-name]')?.textContent.trim() || 'Task';
                    const project = article?.querySelector('[data-task-project]')?.textContent.trim() || 'Project';

                    return {
                        name,
                        project
                    };
                };

                const getElapsedSeconds = (button) => {
                    const baseElapsed = Number(button.dataset.elapsedSeconds || '0');
                    const startedAt = Number(button.dataset.startedAt || '0');

                    if (button.dataset.running === 'true' && startedAt > 0) {
                        return baseElapsed + Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
                    }

                    return baseElapsed;
                };

                const renderTaskTime = (button) => {
                    const article = button.closest('[data-task-item]');
                    const timeLabel = article?.querySelector('[data-task-time]');

                    if (timeLabel) {
                        timeLabel.textContent = formatTaskTime(getElapsedSeconds(button));
                    }
                };

                const updateSummary = () => {
                    let running = 0;
                    let done = 0;
                    let pending = 0;
                    let totalElapsed = 0;

                    taskButtons.forEach((button) => {
                        const article = button.closest('[data-task-item]');
                        const isCompleted = article?.dataset.completed === 'true';
                        const isRunning = button.dataset.running === 'true';
                        const elapsed = getElapsedSeconds(button);

                        totalElapsed += elapsed;
                        renderTaskTime(button);

                        if (isCompleted) {
                            done += 1;
                            return;
                        }

                        if (isRunning) {
                            running += 1;
                            return;
                        }

                        pending += 1;
                    });

                    if (totalBadge) totalBadge.textContent = `Total: ${formatDuration(totalElapsed)}`;
                    if (runningCount) runningCount.textContent = String(running);
                    if (doneCount) doneCount.textContent = String(done);
                    if (pendingCount) pendingCount.textContent = String(pending);
                };

                const refreshSelection = () => {
                    taskItems.forEach((task) => {
                        const button = task.querySelector('[data-task-play-toggle]');
                        const isCompleted = task.dataset.completed === 'true';
                        const isUndoPending = task.dataset.undoPending === 'true';
                        const isRunning = button?.dataset.running === 'true';
                        const isSelected = selectedTask === task && !isCompleted && !isUndoPending && !isRunning;

                        task.classList.toggle('task-row--selected', isSelected);
                    });
                };

                const clearUndoTimer = (article) => {
                    const existingTimer = undoTimers.get(article);

                    if (existingTimer) {
                        window.clearTimeout(existingTimer);
                        undoTimers.delete(article);
                    }
                };

                const syncStickyBar = (button, elapsedSeconds = 0) => {
                    if (!button) {
                        stickyDisplayButton = null;
                        stickyBar?.classList.add('hidden');
                        return;
                    }

                    const {
                        name,
                        project
                    } = getTaskDetails(button);
                    stickyDisplayButton = button;

                    if (stickyProject) stickyProject.textContent = project;
                    if (stickyName) stickyName.textContent = name;
                    if (stickyTimer) stickyTimer.textContent = formatDuration(elapsedSeconds);
                    stickyBar?.classList.remove('hidden');
                };

                const syncStickyAction = (button) => {
                    if (!stickyPauseButton) {
                        return;
                    }

                    const icon = stickyPauseButton.querySelector('span');
                    const isRunning = button?.dataset.running === 'true';

                    stickyPauseButton.setAttribute('aria-label', isRunning ? 'Pause task' : 'Resume task');

                    if (icon) {
                        icon.innerHTML = isRunning ? pauseIcon : playIcon;
                    }
                };

                const clearRunningTimer = () => {
                    if (timerIntervalId) {
                        window.clearInterval(timerIntervalId);
                        timerIntervalId = null;
                    }
                };

                const persistElapsed = (button) => {
                    if (!button) {
                        return;
                    }

                    if (button.dataset.running === 'true') {
                        button.dataset.elapsedSeconds = String(getElapsedSeconds(button));
                    }

                    delete button.dataset.startedAt;
                };

                const applyTaskState = (button) => {
                    const article = button.closest('[data-task-item]');
                    const completeToggle = article?.querySelector('[data-task-complete-toggle]');
                    const doneButton = article?.querySelector('[data-task-done-button]');
                    const undoButton = article?.querySelector('[data-task-undo-button]');
                    const icon = button.querySelector('[data-task-play-icon]');
                    const label = button.querySelector('[data-task-play-label]');
                    const isCompleted = article?.dataset.completed === 'true';
                    const isUndoPending = article?.dataset.undoPending === 'true';
                    const isRunning = button.dataset.running === 'true' && !isCompleted;
                    const taskName = article?.querySelector('[data-task-name]')?.textContent.trim() || 'task';

                    article?.setAttribute('data-running', isRunning ? 'true' : 'false');
                    article?.classList.toggle('is-undo-pending', isUndoPending);
                    button.classList.toggle('is-running', isRunning);
                    button.classList.toggle('is-hidden', isCompleted || isUndoPending);
                    completeToggle?.classList.toggle('is-hidden', isUndoPending);
                    doneButton?.classList.toggle('is-hidden', isCompleted || isUndoPending);
                    undoButton?.classList.toggle('is-hidden', !isUndoPending);

                    if (doneButton) {
                        doneButton.disabled = isCompleted || isUndoPending;
                    }

                    if (completeToggle) {
                        completeToggle.setAttribute('aria-pressed', isCompleted ? 'true' : 'false');
                    }

                    button.setAttribute('aria-label', `${isRunning ? 'Pause' : 'Start'} ${taskName}`);

                    if (icon) {
                        icon.innerHTML = isRunning ? pauseIcon : playIcon;
                    }

                    if (label) {
                        label.textContent = isRunning ? 'Pause' : 'Play';
                    }

                    refreshSelection();
                    updateSummary();
                };

                const pauseTask = (button) => {
                    if (!button) {
                        return;
                    }

                    persistElapsed(button);
                    button.dataset.running = 'false';
                    button.setAttribute('aria-pressed', 'false');
                    applyTaskState(button);

                    if (activeButton === button) {
                        activeButton = null;
                        clearRunningTimer();
                        syncStickyBar(button, Number(button.dataset.elapsedSeconds || '0'));
                    }

                    syncStickyAction(button);
                };

                const finalizeCompletedTask = (article) => {
                    if (!article) {
                        return;
                    }

                    article.dataset.undoPending = 'false';
                    clearUndoTimer(article);

                    const button = article.querySelector('[data-task-play-toggle]');
                    if (button) {
                        applyTaskState(button);
                    }
                };

                const startTask = (button) => {
                    const article = button.closest('[data-task-item]');

                    if (article?.dataset.completed === 'true' || article?.dataset.undoPending === 'true') {
                        return;
                    }

                    if (activeButton && activeButton !== button) {
                        pauseTask(activeButton);
                    }

                    clearRunningTimer();

                    button.dataset.running = 'true';
                    button.dataset.startedAt = String(Date.now());
                    button.setAttribute('aria-pressed', 'true');
                    selectedTask = article || null;
                    applyTaskState(button);

                    activeButton = button;

                    const renderTimer = () => {
                        const liveElapsed = getElapsedSeconds(button);

                        syncStickyBar(button, liveElapsed);
                        renderTaskTime(button);
                        updateSummary();
                    };

                    renderTimer();
                    syncStickyAction(button);
                    timerIntervalId = window.setInterval(renderTimer, 1000);
                };

                const undoTask = (article) => {
                    if (!article) {
                        return;
                    }

                    clearUndoTimer(article);
                    article.dataset.completed = 'false';
                    article.dataset.undoPending = 'false';
                    selectedTask = article;

                    const button = article.querySelector('[data-task-play-toggle]');
                    if (button) {
                        button.dataset.running = 'false';
                        button.setAttribute('aria-pressed', 'false');
                        applyTaskState(button);
                    }

                    syncStickyBar(activeButton, activeButton ? getElapsedSeconds(activeButton) : 0);
                    syncStickyAction(activeButton);
                };

                const completeTask = (article) => {
                    if (!article || article.dataset.completed === 'true') {
                        return;
                    }

                    const button = article.querySelector('[data-task-play-toggle]');

                    if (button?.dataset.running === 'true') {
                        pauseTask(button);
                    }

                    article.dataset.completed = 'true';
                    article.dataset.undoPending = 'true';
                    article.dataset.selected = 'false';
                    if (selectedTask === article) {
                        selectedTask = null;
                    }

                    if (button) {
                        button.dataset.running = 'false';
                        button.setAttribute('aria-pressed', 'false');
                        applyTaskState(button);
                    }

                    clearUndoTimer(article);
                    undoTimers.set(article, window.setTimeout(() => {
                        finalizeCompletedTask(article);
                    }, undoDelayMs));

                    updateSummary();
                    syncStickyBar(activeButton, activeButton ? getElapsedSeconds(activeButton) : 0);
                    syncStickyAction(activeButton);
                };

                taskItems.forEach((task) => {
                    const button = task.querySelector('[data-task-play-toggle]');
                    const completeToggle = task.querySelector('[data-task-complete-toggle]');
                    const doneButton = task.querySelector('[data-task-done-button]');
                    const actions = task.querySelector('.task-row__actions');

                    if (!button) {
                        return;
                    }

                    if (actions && !task.querySelector('[data-task-undo-button]')) {
                        const undoButton = document.createElement('button');
                        undoButton.type = 'button';
                        undoButton.className = 'task-action-btn task-action-btn--undo is-hidden';
                        undoButton.setAttribute('data-task-undo-button', '');
                        undoButton.textContent = 'Undo';
                        actions.appendChild(undoButton);
                    }

                    const undoButton = task.querySelector('[data-task-undo-button]');

                    if (!button.dataset.elapsedSeconds) {
                        button.dataset.elapsedSeconds = '0';
                    }

                    button.dataset.running = 'false';
                    button.setAttribute('aria-pressed', 'false');
                    task.dataset.undoPending = 'false';
                    applyTaskState(button);

                    button.addEventListener('click', () => {
                        if (button.dataset.running === 'true') {
                            pauseTask(button);
                            return;
                        }

                        startTask(button);
                    });

                    completeToggle?.addEventListener('click', () => {
                        completeTask(task);
                    });

                    doneButton?.addEventListener('click', () => {
                        completeTask(task);
                    });

                    undoButton?.addEventListener('click', () => {
                        undoTask(task);
                    });
                });

                stickyPauseButton?.addEventListener('click', () => {
                    if (!stickyDisplayButton) {
                        return;
                    }

                    if (stickyDisplayButton.dataset.running === 'true') {
                        pauseTask(stickyDisplayButton);
                        return;
                    }

                    startTask(stickyDisplayButton);
                });

                refreshSelection();
                updateSummary();
                renderTimeline();
                syncStickyBar(null);
                syncStickyAction(null);
            });
        </script>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
@endpush
