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
        // Superadmin or view all users permission
        if ($authUser->is_super_admin || $authUser->can('user.view_all_users')) {
            return true;
        }

        // Creator
        if ($model->added_by === $authUser->id) {
            return true;
        }

        // Reporter or Manager
        if (
            optional($model->details)->reporter_id === $authUser->id ||
            optional($model->details)->manager_id === $authUser->id
        ) {
            return true;
        }

        // Otherwise deny
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
