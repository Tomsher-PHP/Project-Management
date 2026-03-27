@if ($permissions->isEmpty())
    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-700 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300">
        No permissions available.
    </div>
@else
    <!-- Select All Permissions Checkbox -->
    <div class="mb-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="select-all-permissions" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="text-sm text-gray-700 dark:text-bgray-50 font-semibold">
                Select All Permissions
            </span>
        </label>
    </div>

    <div class="space-y-6">

        @foreach ($permissions as $module => $modulePermissions)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-darkblack-500 dark:border-darkblack-400">

                <!-- Module Header with Module Select All -->
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl dark:bg-darkblack-600 dark:border-darkblack-400 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-800 tracking-wide uppercase dark:text-white">
                        {{ ucfirst(str_replace('_', ' ', $module)) }}
                    </h3>

                    <!-- Select All for this module -->
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 dark:text-bgray-300">
                        <input type="checkbox" class="module-select-all h-5 w-5 rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-module="{{ $module }}">
                        Select All
                    </label>
                </div>

                <!-- Permissions Grid -->
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                        @foreach ($modulePermissions as $permission)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="permission-checkbox h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600" data-module="{{ $module }}" {{ isset($role) && $role->hasPermissionTo($permission->id) ? 'checked' : '' }}>
                                <span class="text-sm
                                             text-gray-700
                                             group-hover:text-indigo-600
                                             dark:text-bgray-50
                                             dark:group-hover:text-success-300
                                             transition">
                                    {{ ucfirst(str_replace('_', ' ', explode('.', $permission->name)[1])) }}
                                </span>
                            </label>
                        @endforeach

                    </div>
                </div>

            </div>
        @endforeach

    </div>

@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAllGlobal = document.getElementById('select-all-permissions');
            const moduleSelectAlls = document.querySelectorAll('.module-select-all');
            const checkboxes = document.querySelectorAll('.permission-checkbox');

            // Global Select All
            selectAllGlobal.addEventListener('change', (e) => {
                checkboxes.forEach(cb => cb.checked = e.target.checked);
                moduleSelectAlls.forEach(cb => cb.checked = e.target.checked);
            });

            // Module Select All
            moduleSelectAlls.forEach(moduleCheckbox => {
                const module = moduleCheckbox.dataset.module;
                moduleCheckbox.addEventListener('change', (e) => {
                    document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`)
                        .forEach(cb => cb.checked = e.target.checked);

                    // Update global select all
                    selectAllGlobal.checked = document.querySelectorAll('.permission-checkbox:checked').length === checkboxes.length;
                });
            });

            // Individual checkboxes update module & global select all
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const module = cb.dataset.module;

                    // Update module checkbox
                    const moduleCheckbox = document.querySelector(`.module-select-all[data-module="${module}"]`);
                    const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
                    moduleCheckbox.checked = Array.from(moduleCheckboxes).every(c => c.checked);

                    // Update global checkbox
                    selectAllGlobal.checked = Array.from(checkboxes).every(c => c.checked);
                });
            });
        });
    </script>
@endpush
