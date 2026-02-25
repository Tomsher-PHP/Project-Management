<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" class="space-y-6">
    @csrf
    @if (isset($role))
        @method('PUT')
    @endif

    {{-- Role Name --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
            Role Name
        </label>

        <input type="text" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm
                      focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500
                      @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

        <input type="hidden" name="role_id" value="{{ $role->id ?? '' }}">

        @error('name')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- User Type --}}
    <div>
        <label for="user_type" class="block text-sm font-medium text-gray-700 mb-2">
            User Type
        </label>

        <select name="user_type" id="user_type" {{ isset($role) ? 'disabled' : '' }} required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white
                       focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500
                       @error('user_type') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">

            <option value="">Select User Type</option>

            @foreach ($userTypes as $key => $type)
                <option value="{{ $key }}" {{ old('user_type', $role->user_type ?? '') == $key ? 'selected' : '' }}>
                    {{ $type }}
                </option>
            @endforeach
        </select>

        @error('user_type')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        {{-- Hidden input if disabled --}}
        @if (isset($role))
            <input type="hidden" name="user_type" value="{{ $role->user_type }}">
        @endif
    </div>

    {{-- Permissions --}}
    <div id="permission-container" class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
        Select user type to load permissions.
    </div>

    {{-- Submit Button --}}
    <div class="mt-4 pt-4 border-t border-gray-200 flex justify-start">
        <button type="submit" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">

            <span>
                @if (isset($role))
                    Update Role
                @else
                    Create Role
                @endif
            </span>
        </button>
    </div>
</form>
