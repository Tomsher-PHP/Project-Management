<form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST" class="space-y-10">

    @csrf
    @if (isset($role))
        @method('PUT')
    @endif

    @php
        $permissionModules = $permissions->keys()->map(
            fn($milestone) => [
                'key' => $milestone,
                'id' => 'permission-module-' . \Illuminate\Support\Str::slug($milestone),
                'label' => ucfirst(str_replace('_', ' ', $milestone)),
            ],
        );
        $submitLabel = isset($role) ? 'Update Role' : 'Create Role';
        $submitButtonClasses = 'px-6 py-2.5 rounded-lg bg-success-300 text-white font-semibold hover:bg-success-400 transition';
    @endphp

    <!-- ================= BASIC ROLE INFORMATION ================= -->
    <div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Role Name -->
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

            <!-- Permission Module Index -->
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-dashed border-gray-500 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h4 class="text-base font-semibold text-bgray-700 dark:text-bgray-50">
                            Permission Milestones
                        </h4>
                        <span class="text-xs font-medium text-bgray-700 dark:text-bgray-300">
                            Click to jump
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($permissionModules as $milestone)
                            <a href="#{{ $milestone['id'] }}" class="rounded-full border border-bgray-200 px-3 py-1.5 text-xs font-semibold text-bgray-600 transition hover:border-success-300 hover:bg-success-50 hover:text-success-400 focus:border-success-300 focus:outline-none focus:ring-2 focus:ring-success-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-600 dark:hover:text-success-300" data-permission-index-link data-target="{{ $milestone['id'] }}">
                                {{ $milestone['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ================= PERMISSIONS ================= -->
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

    <!-- ================= SUBMIT ================= -->
    <div class="sticky bottom-0 z-20 -mx-1 flex justify-end border-t border-bgray-200 bg-white/95 px-4 py-4 shadow-[0_-8px_24px_rgba(0,0,0,0.06)] backdrop-blur dark:border-darkblack-400 dark:bg-darkblack-600/95">
        <button type="submit" class="{{ $submitButtonClasses }}">
            {{ $submitLabel }}
        </button>
    </div>

</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-permission-index-link]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const target = document.getElementById(link.dataset.target);

                    if (!target) {
                        return;
                    }

                    event.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    target.focus({
                        preventScroll: true
                    });
                });
            });
        });
    </script>
@endpush
