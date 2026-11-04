<?php

namespace App\Http\Controllers;

use App\Models\FeatureUpdate;
use App\Models\FeatureUpdateUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureUpdateUserStatusController extends Controller
{
    public function acknowledge(
        Request $request,
        FeatureUpdate $featureUpdate
    ): JsonResponse {
        $user = $request->user();

        // Only acknowledge currently published and active features.
        abort_unless(
            $featureUpdate->status === 'published'
            && (!$featureUpdate->publish_at || $featureUpdate->publish_at->lte(now()))
            && (!$featureUpdate->expires_at || $featureUpdate->expires_at->gte(now())),
            404
        );

        // Check whether this feature is intended for this user.
        $isTargeted = $featureUpdate->target_type === 'all';

        if ($featureUpdate->target_type === 'roles') {
            $isTargeted = !empty(array_intersect(
                $user->getRoleNames()->toArray(),
                $featureUpdate->target_roles ?? []
            ));
        }

        if ($featureUpdate->target_type === 'users') {
            $isTargeted = in_array(
                (int) $user->id,
                array_map('intval', $featureUpdate->target_user_ids ?? []),
                true
            );
        }

        abort_unless($isTargeted, 403);

        FeatureUpdateUserStatus::firstOrCreate([
            'feature_update_id' => $featureUpdate->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature acknowledged.',
        ]);
    }
}
