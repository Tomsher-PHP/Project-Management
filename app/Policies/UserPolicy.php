<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $authUser, User $model): bool
    {
        $reporterHierarchyUserIds = User::getReporterHierarchyUserIds($authUser->id);

        if ($model->added_by === $authUser->id) {
            return true;
        }

        if (in_array($model->id, $reporterHierarchyUserIds)) {
            return true;
        }

        if (optional($model->details)->manager_id === $authUser->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $authUser): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $authUser, User $model): bool
    {
        return $this->view($authUser, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $authUser, User $model): bool
    {
        if ($model->is_super_admin) {
            return false;
        }

        if ($authUser->id === $model->id) {
            return false;
        }

        return $this->view($authUser, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $authUser, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $authUser, User $model): bool
    {
        return false;
    }
}
