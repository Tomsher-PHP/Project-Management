<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRestoreService
{
    public function getDeletedUsers(int $perPage): LengthAwarePaginator
    {
        return User::withTrashed()
            ->orderByDesc('deleted_at')
            ->deleted()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findDeletedUserOrFail(int|string $id): User
    {
        return User::withTrashed()->deleted()->findOrFail($id);
    }

    public function hasActiveEmailConflict(User $user): bool
    {
        return User::where('email', $user->email)
            ->whereNull('deleted_at')
            ->where('delete_status', false)
            ->exists();
    }

    public function restoreDeletedUser(User $user): bool
    {
        if ($this->hasActiveEmailConflict($user)) {
            return false;
        }

        $this->markAsRestored($user);

        return true;
    }

    public function bulkRestoreUsers(array $userIds): array
    {
        $users = User::withTrashed()
            ->deleted()
            ->whereIn('id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            return [
                'selected_count' => 0,
                'restored_count' => 0,
                'conflicting_count' => 0,
            ];
        }

        $conflictingEmails = $this->getConflictingEmails($users);

        $conflictingUsers = $users->filter(function (User $user) use ($conflictingEmails) {
            return $conflictingEmails->contains($user->email);
        });

        $restorableUsers = $users->reject(function (User $user) use ($conflictingEmails) {
            return $conflictingEmails->contains($user->email);
        });

        $restorableUsers->each(function (User $user) {
            $this->markAsRestored($user);
        });

        return [
            'selected_count' => $users->count(),
            'restored_count' => $restorableUsers->count(),
            'conflicting_count' => $conflictingUsers->count(),
        ];
    }

    private function getConflictingEmails(Collection $users)
    {
        return User::query()
            ->whereIn('email', $users->pluck('email')->filter()->unique())
            ->whereNull('deleted_at')
            ->where('delete_status', false)
            ->pluck('email');
    }

    private function markAsRestored(User $user): void
    {
        $user->restore();
        $user->update([
            'delete_status' => false,
        ]);
    }
}
