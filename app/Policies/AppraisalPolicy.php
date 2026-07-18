<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\User;

class AppraisalPolicy
{
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    public function view(User $authUser, Appraisal $model): bool
    {
        return false;
    }

    public function viewAnswer(User $authUser, Appraisal $model): bool
    {
        // 1. The Appraisal Assignee.
        if ((int) $model->user_id === (int) $authUser->id) {
            return true;
        }

        // 2. Any assigned Reviewer of the appraisal (records from appraisal_reviewers).
        if ($model->reviewers()->where('reviewer_user_id', $authUser->id)->exists()) {
            return true;
        }

        // 3. Any upper-level user of the Assignee
        $upperLevelUserIds = User::getReporterChainUserIds($model->user_id);
        if (in_array($authUser->id, $upperLevelUserIds)) {
            return true;
        }

        return false;
    }
}
