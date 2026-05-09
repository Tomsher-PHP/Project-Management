@extends('layouts.master')

@push('styles')
    @vite(['resources/css/modules/user-timeline.css', 'resources/css/modules/kanban.css'])
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

            @include('workspace.partials.daily-timeline')
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
                                <h3 class="text-[17px] font-extrabold tracking-normal text-bgray-800 dark:text-bgray-50">Work Board</h3>
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
    </main>
@endsection

@push('scripts')
    <script>
        const initialFlowType = @json($selectedFlowType);
    </script>
    @vite('resources/js/modules/workspace/user-timeline.js')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
@endpush
