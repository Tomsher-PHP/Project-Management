<?php

namespace App\Http\Controllers;

use App\Models\FeatureUpdate;
use App\Models\FeatureUpdateUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureUpdateUserStatusController extends Controller
{
    /**
     * User clicked "Got it".
     * The announcement is considered read.
     */
    public function acknowledge(
        Request $request,
        FeatureUpdate $featureUpdate
    ): JsonResponse {
        $user = $request->user();

        $this->validateFeatureUpdate($featureUpdate, $user);

        FeatureUpdateUserStatus::updateOrCreate(
            [
                'feature_update_id' => $featureUpdate->id,
                'user_id' => $user->id,
            ],
            [
                'acknowledged_at' => now(),
                'skipped_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Feature marked as read.',
        ]);
    }

    /**
     * User clicked "Skip for now" or closed the modal.
     * The announcement is dismissed from the popup but remains unread.
     */
    public function skip(
        Request $request,
        FeatureUpdate $featureUpdate
    ): JsonResponse {
        $user = $request->user();

        $this->validateFeatureUpdate($featureUpdate, $user);

        FeatureUpdateUserStatus::updateOrCreate(
            [
                'feature_update_id' => $featureUpdate->id,
                'user_id' => $user->id,
            ],
            [
                'skipped_at' => now(),
                'acknowledged_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Feature dismissed.',
        ]);
    }

    /**
     * Validate publication dates and user targeting.
     */
    private function validateFeatureUpdate(
        FeatureUpdate $featureUpdate,
        $user
    ): void {
        abort_unless(
            $featureUpdate->status === 'published'
            && (!$featureUpdate->publish_at || $featureUpdate->publish_at->lte(now()))
            && (!$featureUpdate->expires_at || $featureUpdate->expires_at->gte(now())),
            404
        );

        $isTargeted = false;

        if ($featureUpdate->target_type === 'all') {
            $isTargeted = true;
        } elseif ($featureUpdate->target_type === 'roles') {
            $isTargeted = !empty(array_intersect(
                $user->getRoleNames()->toArray(),
                $featureUpdate->target_roles ?? []
            ));
        } elseif ($featureUpdate->target_type === 'users') {
            $isTargeted = in_array(
                (int) $user->id,
                array_map('intval', $featureUpdate->target_user_ids ?? []),
                true
            );
        }

        abort_unless($isTargeted, 403);
    }
}
