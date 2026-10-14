<!--page-title-->
<div class="flex items-center gap-3">
    <div class="space-y-1">
        <h3 class="text-lg font-bold leading-tight text-bgray-900 dark:text-bgray-50 lg:text-[28px]">
            {{ $pageTitle ?? 'Dashboard' }}
        </h3>
    </div>

    @if ((request()->routeIs('user.workspace') || request()->routeIs('user.analytics')) && $workspaceSelectableUsers->isNotEmpty())
        <div class="min-w-[240px] max-w-[320px]" data-workspace-user-select-root>
            <label for="workspace-user-select" class="sr-only">Workspace user</label>
            <select id="workspace-user-select" class="tom-select-no-search w-full" data-workspace-user-select>
                <option value="" @selected($workspaceSelectedUserId === '')>Select Users</option>
                @foreach ($workspaceSelectableUsers as $workspaceSelectableUser)
                    <option value="{{ $workspaceSelectableUser->id }}" @selected($workspaceSelectedUserId === (string) $workspaceSelectableUser->id)>
                        {{ $workspaceSelectableUser->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif


    @if ((request()->routeIs('user.workspace') || request()->routeIs('user.analytics')) && ($pendingHandoffCount ?? 0) > 0)
        
<a
    href="{{ route('handoff_requests.index') }}"
    class="group inline-flex items-center gap-2.5  bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-800 transition-all duration-200 hover:border-amber-300 hover:bg-amber-100 hover:shadow-md dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
    title="View pending handoff requests"
>

            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500 opacity-60"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-amber-100 dark:ring-darkblack-600"></span>
            </span>

            <span class="whitespace-nowrap">
                You have
                <span class="font-bold">{{ $pendingHandoffCount }}</span>
                {{ \Illuminate\Support\Str::plural('handoff', $pendingHandoffCount) }}
            </span>

            

            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    @endif

</div>
