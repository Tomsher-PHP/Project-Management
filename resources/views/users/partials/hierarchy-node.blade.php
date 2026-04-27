@php
    $user = $node['user'];
    $children = $node['children'] ?? [];
    $isDetached = (bool) ($node['is_detached'] ?? false);
    $initials = \Illuminate\Support\Str::of($user->name)
        ->trim()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $hasAvatar = filled($user->primaryAttachment?->file_path ?? null);
    $roleName = $user->is_super_admin
        ? 'Super Admin'
        : ($user->roles->first()?->name ?? 'No Role');
@endphp

<li class="org-node">
    <div class="org-card">
        <div class="org-avatar">
            @if ($hasAvatar)
                <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}">
            @else
                <span>{{ $initials }}</span>
            @endif
        </div>

        <div class="org-meta">
            <h4 class="org-name text-sm font-bold text-bgray-900 dark:text-white">{{ $user->name }}</h4>
            <p class="org-role mt-0.5 text-xs font-medium text-bgray-600 dark:text-bgray-300">{{ $roleName }}</p>
        </div>

        @if ($isDetached)
            <span class="org-badge org-badge--warn">Review</span>
        @elseif (!empty($children))
            <span class="org-badge org-badge--count">{{ count($children) }}</span>
        @endif

        @if (! $user->is_active)
            <span class="org-badge org-badge--inactive">Off</span>
        @endif
    </div>

    @if (!empty($children))
        <ul class="org-children">
            @foreach ($children as $childNode)
                @include('users.partials.hierarchy-node', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
