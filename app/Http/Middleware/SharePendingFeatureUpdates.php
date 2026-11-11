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


        $unreadFeatureUpdatesCount = FeatureUpdate::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('publish_at')
                ->orWhere('publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
            })
            ->whereDoesntHave('userStatuses', function ($statusQuery) use ($user) {
                $statusQuery->where('user_id', $user->id)
                    ->whereNotNull('acknowledged_at');
            })
            ->count();

        View::share('unreadFeatureUpdatesCount', $unreadFeatureUpdatesCount);



            // Keep the old variable available if any existing view uses it.
            View::share(
                'skippedFeatureUpdatesCount',
                $unreadFeatureUpdatesCount
            );

            /*
             * Popup announcements.
             */
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

            // Apply existing feature targeting.
            $query->where(function ($q) use ($user) {
                $q->where('target_type', 'all');

                $roleNames = $user->getRoleNames()->toArray();

                if (!empty($roleNames)) {
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

            /*
             * Exclude announcements already acknowledged or skipped
             * by this user.
             */

            $query->whereDoesntHave('userStatuses', function ($statusQuery) use ($user) {
                $statusQuery->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNotNull('acknowledged_at')
                        ->orWhereNotNull('skipped_at');
                    });
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
