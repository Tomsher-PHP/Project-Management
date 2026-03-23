<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" class="space-y-10">

    @csrf
    @if (isset($role))
        @method('PUT')
    @endif

    {{-- ================= BASIC ROLE INFORMATION ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 
                   dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Role Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {{-- Role Name --}}
            <div class="flex flex-col gap-2">
                <label for="name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Role Name <x-red-star />
                </label>

                <input type="text" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required class="w-full rounded-lg border border-gray-300 p-2
                              focus:border focus:border-success-300 focus:ring-0
                              dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400
                              @error('name') border border-red-500 @enderror">

                <input type="hidden" name="role_id" value="{{ $role->id ?? '' }}">

                @error('name')
                    <p class="mt-2 text-sm text-error-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ================= PERMISSIONS ================= --}}
    <div>
        <h3 class="text-xl font-bold text-gray-800 border-b pb-4 mb-6 
                   dark:border-darkblack-400 dark:text-white dark:border-darkblack-400">
            Permissions
        </h3>

        <div id="permission-container" class="rounded-lg border border-dashed border-gray-300 p-6 text-sm
                    bg-white
                    dark:bg-darkblack-500
                    dark:border-darkblack-400
                    dark:text-bgray-50">

            @include('roles.permissions')
        </div>
    </div>

    {{-- ================= SUBMIT ================= --}}
    <div class="pt-6 border-t flex justify-end 
                dark:border-darkblack-400">
        <button type="submit" class="px-6 py-2.5 rounded-lg
                       bg-success-300 text-white font-semibold
                       hover:bg-success-400 transition">

            @if (isset($role))
                Update Role
            @else
                Create Role
            @endif
        </button>
    </div>

</form>
