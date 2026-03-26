<form id="project-team-form" class="mt-6 p-6 border rounded-lg dark:border-darkblack-400 bg-gray-50 dark:bg-darkblack-500">

    <div class="grid md:grid-cols-3 gap-6 items-end">

        <!-- User -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                Users
            </label>

            <select name="user_id" id="team_member" class="tom-select w-full">
                <option value="">Select User</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" data-subtype="{{ $user->email }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Role -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
                Project Role
            </label>

            <select name="project_role" id="project_role" class="tom-select-no-search w-full">
                <option value="">Select Role</option>
                @foreach ($projectRoles as $key => $role)
                    <option value="{{ $key }}">{{ $role }}</option>
                @endforeach
            </select>
        </div>

        <!-- Submit -->
        <div class="flex md:justify-end col-span-full md:col-span-1">
            <button type="button" id="add-member-btn" class="px-4 py-2 text-sm rounded-md bg-success-300 text-white font-medium hover:bg-success-400 transition">
                + Add
            </button>
        </div>

    </div>

</form>
