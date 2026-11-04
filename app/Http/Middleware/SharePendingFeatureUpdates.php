<?php

namespace App\Http\Middleware;

use App\Models\FeatureUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SharePendingFeatureUpdates
{
    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();

            $query = FeatureUpdate::query()
                ->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('publish_at')
                        ->orWhere('publish_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                });

            // Apply feature targeting.
            $query->where(function ($q) use ($user) {
                $q->where('target_type', 'all');

                if ($user->relationLoaded('roles') || method_exists($user, 'roles')) {
                    $roleNames = $user->getRoleNames()->toArray();

                    $q->orWhere(function ($roleQuery) use ($roleNames) {
                        $roleQuery->where('target_type', 'roles')
                            ->where(function ($targetQuery) use ($roleNames) {
                                foreach ($roleNames as $role) {
                                    $targetQuery->orWhereJsonContains(
                                        'target_roles',
                                        $role
                                    );
                                }
                            });
                    });
                }

                $q->orWhere(function ($userQuery) use ($user) {
                    $userQuery->where('target_type', 'users')
                        ->whereJsonContains(
                            'target_user_ids',
                            (int) $user->id
                        );
                });
            });

            // Exclude features already acknowledged by this user.
            $query->whereDoesntHave('userStatuses', function ($statusQuery) use ($user) {
                $statusQuery->where('user_id', $user->id);
            });

            $pendingFeatureUpdates = $query
                ->orderByDesc('priority')
                ->orderBy('publish_at')
                ->get();

            View::share(
                'pendingFeatureUpdates',
                $pendingFeatureUpdates
            );
        }

        return $next($request);
    }
}
