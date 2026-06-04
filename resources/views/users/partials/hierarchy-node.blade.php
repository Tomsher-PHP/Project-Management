@php
    $user = $node['user'];
    $children = $node['children'] ?? [];
    $isDetached = (bool) ($node['is_detached'] ?? false);
    $roleName = $user->is_super_admin ? 'Super Admin' : $user->roles->first()?->name ?? 'No Role';
    $isAuthUser = (int) $user->id === (int) auth()->id();
    $designationName = $user->details?->designation?->name ?? $roleName;
@endphp

<li class="org-node {{ !empty($children) ? 'org-node--branch' : '' }}">
    <div class="org-card {{ $isAuthUser ? 'org-card--auth' : '' }}" data-user-card data-user-name="{{ $user->name }}" data-user-email="{{ $user->email ?? '-' }}" data-user-employee-id="{{ $user->details?->employee_id ?? '-' }}" data-user-role="{{ $roleName }}" data-user-designation="{{ $designationName }}" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="org-user-modal">
        <x-user-avatar :user="$user" class="org-avatar" />

        <div class="org-meta">
            <h4 class="org-name text-sm font-bold text-bgray-900 dark:text-white">{{ $user->name }}</h4>
            <p class="org-role mt-0.5 text-xs font-medium text-bgray-600 dark:text-bgray-300">{{ $roleName }}</p>
        </div>

        <div class="org-badges">
            @if ($isDetached)
                <span class="org-badge org-badge--warn">Review</span>
            @endif

            @if (!$user->is_active)
                <span class="org-badge org-badge--inactive">Off</span>
            @endif
        </div>
    </div>

    @if (!empty($children))
        <ul class="org-children">
            @foreach ($children as $childNode)
                @include('users.partials.hierarchy-node', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
