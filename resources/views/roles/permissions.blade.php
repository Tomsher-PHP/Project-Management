@if ($permissions->isEmpty())
    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-700">
        No permissions available.
    </div>
@else
    <div class="space-y-6">

        @foreach ($permissions as $module => $modulePermissions)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

                {{-- Module Header --}}
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                    <h3 class="text-sm font-semibold text-gray-800 tracking-wide uppercase">
                        {{ ucfirst($module) }}
                    </h3>
                </div>

                {{-- Permissions Grid --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                        @foreach ($modulePermissions as $permission)
                            <label class="flex items-center gap-3 cursor-pointer group">

                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ isset($role) && $role->hasPermissionTo($permission->id) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600
                                              focus:ring-2 focus:ring-indigo-500">

                                <span class="text-sm text-gray-700 group-hover:text-indigo-600 transition">
                                    {{ ucfirst(explode('.', $permission->name)[1]) }}
                                </span>

                            </label>
                        @endforeach

                    </div>
                </div>

            </div>
        @endforeach

    </div>

@endif
