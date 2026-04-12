@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'statuses',
            'label' => 'Statuses',
            'url' => route('settings.task-statuses.index'),
            'permission' => 'task_settings.view',
        ],
        [
            'key' => 'types',
            'label' => 'Types',
            'url' => route('settings.task-types.index'),
            'permission' => 'task_settings.view',
        ],
        [
            'key' => 'modes',
            'label' => 'Modes',
            'url' => route('settings.task-modes.index'),
            'permission' => 'task_settings.view',
        ],
    ];
@endphp

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can($createPermission)
                <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-module="{{ $entityLabel }}" data-url="{{ $storeRoute }}" data-method="POST" data-sort_order="{{ $nextSortOrder }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New {{ $entityLabel }}</span>
                </a>
            @endcan

            <x-filters.button />
        </div>

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="mb-6 flex flex-wrap gap-3 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                        @foreach ($tabs as $tab)
                            @can($tab['permission'])
                                @php
                                    $isActiveTab = $currentTab === $tab['key'];
                                @endphp
                                <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                    {{ $tab['label'] }}
                                </a>
                            @endcan
                        @endforeach
                    </div>

                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full relative">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="pr-6 py-5 whitespace-nowrap">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    @if ($currentTab === 'statuses')
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <x-sorting.sortable-column column="flow_type" label="Flow Type" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <x-sorting.sortable-column column="type" label="Type" />
                                            </div>
                                        </td>
                                    @endif

                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="sort_order" label="Sort Order" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                    </td>
                                    <td class="pl-6 py-5 whitespace-nowrap text-right">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($records->currentPage() - 1) * $records->perPage();
                                @endphp
                                @forelse ($records as $record)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="pr-6 py-5 whitespace-nowrap">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-start space-x-2.5">
                                                <span class="mt-1.5 inline-flex h-3 w-3 shrink-0 rounded-full border border-bgray-200 dark:border-darkblack-400" style="background-color: {{ $record->color ?: '#E5E7EB' }}"></span>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                            {{ $record->name }}
                                                        </p>
                                                        @if ($record->is_system)
                                                            <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-warning-600 dark:bg-warning-900/30 dark:text-warning-300">
                                                                System
                                                            </span>
                                                        @endif
                                                        @if ($record->is_default)
                                                            <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-success-600 dark:bg-success-900/30 dark:text-success-300">
                                                                Default
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-1 text-sm font-medium text-bgray-500 dark:text-bgray-300">
                                                        {{ $record->code }}
                                                    </p>
                                                    @if ($currentTab === 'modes')
                                                        <p class="mt-1.5 text-sm text-bgray-500 dark:text-bgray-300">
                                                            {{ \Illuminate\Support\Str::limit($record->description ?: 'No description added.', 50, '...') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        @if ($currentTab === 'statuses')
                                            <td class="px-6 py-5 whitespace-nowrap">
                                                <span class="text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                                    {{ $projectFlows[$record->flow_type] ?? \Illuminate\Support\Str::headline($record->flow_type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-5 whitespace-nowrap">
                                                <span class="text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                                    {{ $taskStatusTypes[$record->type] ?? \Illuminate\Support\Str::headline($record->type) }}
                                                </span>
                                            </td>
                                        @endif

                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex w-full items-center text-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $record->sort_order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$record" :route="$toggleRoute" entity="{{ \Illuminate\Support\Str::snake($entityLabel) }}" :permission="$togglePermission" />
                                            </div>
                                        </td>
                                        <td class="pl-6 py-5 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end space-x-3">
                                                @can($editPermission)
                                                    @php
                                                        // Pass dynamic data attributes based on mode
                                                        $editData = [
                                                            'name' => $record->name,
                                                            'code' => $record->code,
                                                            'color' => $record->color,
                                                            'sort_order' => $record->sort_order,
                                                            'is_default' => (int) $record->is_default,
                                                            'is_system' => (int) $record->is_system,
                                                        ];
                                                        if ($currentTab === 'statuses') {
                                                            $editData['flow_type'] = $record->flow_type;
                                                            $editData['type'] = $record->type;
                                                            $editData['is_completed'] = (int) $record->is_completed;
                                                        } elseif ($currentTab === 'modes') {
                                                            $editData['description'] = $record->description;
                                                            $editData['is_rework'] = (int) $record->is_rework;
                                                            $editData['is_productive'] = (int) $record->is_productive;
                                                            $editData['track_performance'] = (int) $record->track_performance;
                                                        }

                                                        $dataAttributes = '';
                                                        foreach ($editData as $k => $v) {
                                                            $dataAttributes .= ' data-' . $k . '="' . htmlspecialchars((string) $v) . '"';
                                                        }
                                                    @endphp
                                                    <a href="javascript:void(0)" class="edit-record" data-modal="multi-step-modal" data-url="{{ route($updateRouteName, $record->id) }}" {!! $dataAttributes !!} data-method="PUT" data-module="{{ $entityLabel }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 transition group-hover:text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can($deletePermission)
                                                    @if (!$record->is_system)
                                                        <x-delete-form :action="route($destroyRouteName, $record->id)" />
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data :col-span="($currentTab === 'statuses') ? 7 : 5" :message="'No ' . strtolower($entityPluralLabel) . ' found.'" />
                                @endforelse
                            </table>
                        </div>

                        <x-pagination :paginator="$records" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
    </main>

    <x-form-modal modalId="multi-step-modal" :module="$entityLabel" formId="taskSettingsForm" :action="$storeRoute" :button="'Create ' . $entityLabel">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-source required>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Code <x-red-star /></label>
            <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-target required>
        </div>

        @if ($currentTab === 'statuses')
            <!-- Project Flow -->
            <div>
                <label for="flow_type" class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-600 dark:text-bgray-50">
                    <span>Project Flow <x-red-star /></span>
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            Choose whether this status is used for linear projects or agile projects.
                        </span>
                    </span>
                </label>
                <select name="flow_type" id="flow_type" class="tom-select-no-search w-full">
                    @foreach ($projectFlows as $key => $flow)
                        <option value="{{ $key }}" {{ $key === 'agile' ? 'selected' : '' }}>{{ $flow }}</option>
                    @endforeach
                </select>
                @error('flow_type')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
            <div>
                <label for="type" class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-500 dark:text-bgray-50">
                    <span>Type <x-red-star /></span>
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            Choose how this task status behaves in the workflow: open, in progress, or closed.
                        </span>
                    </span>
                </label>
                <select name="type" id="type" class="tom-select-no-search w-full" required>
                    @foreach ($taskStatusTypes as $key => $label)
                        <option value="{{ $key }}" @selected($loop->first)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        @endif

        @if ($currentTab === 'modes')
            <div>
                <div class="mb-2.5 flex items-center justify-between gap-3">
                    <label class="block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                    <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-modal-description-count>0</span>/250</span>
                </div>
                <textarea name="description" rows="3" maxlength="250" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
            </div>
        @endif

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
            <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
        </div>

        <div>
            <label class="mb-2.5 flex items-center gap-1.5 text-left text-sm text-bgray-500 dark:text-bgray-50">
                <span>Sort Order <x-red-star /></span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        Lower numbers appear earlier in lists and selection menus.
                    </span>
                </span>
            </label>
            <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
        </div>

        <label for="is_default" class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="is_default" id="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                <span>Is Default</span>
                <span class="group relative inline-flex cursor-help">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                        The default option is preselected when creating a new task.
                    </span>
                </span>
            </span>
        </label>

        @if ($currentTab === 'statuses')
            <label for="is_completed" class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_completed" id="is_completed" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                    <span>Marks Task as Completed?</span>
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            Enable this when tasks in this status should be treated as completed across the system.
                        </span>
                    </span>
                </span>
            </label>
        @endif

        @if ($currentTab === 'modes')
            <div class="grid grid-cols-2 gap-4">
                <label for="is_rework" class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_rework" id="is_rework" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Is Rework?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Mark this mode when the work represents revisions or corrections to previous work.
                            </span>
                        </span>
                    </span>
                </label>

                <label for="is_productive" class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_productive" id="is_productive" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Is Productive?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Enable this when time logged under this mode should count as productive work.
                            </span>
                        </span>
                    </span>
                </label>

                <label for="track_performance" class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="track_performance" id="track_performance" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Track Performance?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Enable this when tasks using this mode should be included in performance tracking.
                            </span>
                        </span>
                    </span>
                </label>
            </div>
        @endif
    </x-form-modal>

    <x-filters.drawer>
        @if ($currentTab === 'statuses')
            <x-filters.input-search name="search" label="Status Name" />
            <x-filters.select name="flow_type" label="Flow Type" :options="$projectFlows" />
            <x-filters.select name="type" label="Type" :options="$taskStatusTypes" />
        @else
            <x-filters.input-search name="search" :label="$entityLabel . ' Name'" />
        @endif

        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
@endsection
