@extends('layouts.master')
@section('page-content')
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('settings.leave-types.index') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-bgray-600 shadow-sm hover:bg-bgray-50 dark:bg-darkblack-600 dark:text-bgray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div>
                    <h2 class="text-2xl font-semibold text-bgray-900 dark:text-white">
                        Edit Leave Type
                    </h2>

                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                        Update the leave type details.
                    </p>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600">
            <form action="{{ route('settings.leave-types.update', $leaveType->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Leave Type Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $leaveType->name) }}"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm text-bgray-900 outline-none focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                required>
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div>
                            <label for="code" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Code
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="code" name="code" value="{{ old('code', $leaveType->code) }}"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm uppercase text-bgray-900 outline-none focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                required>
                            @error('code')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Color --}}
                        <div>
                            <label for="color" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Calendar Color
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color" id="color" name="color"
                                    value="{{ old('color', $leaveType->color ?? '#3B82F6') }}"
                                    class="h-11 w-16 cursor-pointer rounded-lg border border-bgray-200 bg-white p-1 dark:border-darkblack-400 dark:bg-darkblack-500"
                                    required>
                                <div>
                                    <p id="color-value" class="text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                        {{ strtoupper(old('color', $leaveType->color ?? '#3B82F6')) }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-bgray-500 dark:text-bgray-400">
                                        Used to identify this leave on the attendance calendar.
                                    </p>
                                </div>
                            </div>
                            @error('color')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label for="description"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Description
                            </label>
                            <textarea id="description" name="description" rows="4"
                                class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm text-bgray-900 outline-none focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ old('description', $leaveType->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- File Upload --}}
                        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                            <div class="flex items-start gap-3">
                                <input type="hidden" name="is_file_upload_required" value="0">
                                <input type="checkbox" id="is_file_upload_required" name="is_file_upload_required"
                                    value="1"
                                    {{ old('is_file_upload_required', $leaveType->is_file_upload_required) ? 'checked' : '' }}
                                    class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300">

                                <div>
                                    <label for="is_file_upload_required"
                                        class="text-sm font-medium text-bgray-800 dark:text-white">
                                        File Upload Required
                                    </label>
                                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                        Require an attachment when an employee applies for this leave type.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Paid Leave --}}
                        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                            <div class="flex items-start gap-3">
                                <input type="hidden" name="is_paid" value="0">
                                <input type="checkbox" id="is_paid" name="is_paid" value="1"
                                    {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}
                                    class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300">

                                <div>
                                    <label for="is_paid" class="text-sm font-medium text-bgray-800 dark:text-white">
                                        Paid Leave
                                    </label>
                                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                                        Mark this leave type as paid leave.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="md:col-span-2">
                            <label for="status"
                                class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Status
                            </label>

                            @php
                                $status = old('status', $leaveType->status ? '1' : '0');
                            @endphp

                            <select id="status"
                                    name="status"
                                    class="w-full rounded-lg border border-bgray-200 bg-white px-4 py-3 text-sm text-bgray-900 outline-none focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <option value="1" {{ $status == '1' ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="0" {{ $status == '0' ? 'selected' : '' }}>
                                    Inactive
                                </option>

                            </select>

                            @error('status')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div
                    class="flex items-center justify-end gap-3 border-t border-bgray-100 px-6 py-4 dark:border-darkblack-400">

                    <a href="{{ route('settings.leave-types.index') }}"
                        class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-200 dark:hover:bg-darkblack-500">
                        Cancel
                    </a>

                    @can('leave_types.edit')
                        <button type="submit"
                            class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-success-400">
                            Update Leave Type
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    {{-- Update Color Hex Value --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const colorInput = document.getElementById('color');
            const colorValue = document.getElementById('color-value');

            if (colorInput && colorValue) {

                colorInput.addEventListener('input', function() {
                    colorValue.textContent = this.value.toUpperCase();
                });

            }

        });
    </script>

@endsection
