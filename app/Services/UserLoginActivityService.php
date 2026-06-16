<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserLoginActivityService
{
    public function getActivities(User $authUser, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = UserLoginSession::query()
            ->with([
                'user' => fn ($query) => $query
                    ->select(['id', 'name', 'email'])
                    ->with('primaryAttachment'),
            ])
            ->whereIn('user_id', $this->visibleUsersQuery($authUser)->select('id'));

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('login_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAccessibleUsers(User $authUser): Collection
    {
        return $this->visibleUsersQuery($authUser)
            ->whereHas('loginSessions')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleUsersQuery(User $authUser): Builder
    {
        return User::query()
            ->whereKey($authUser->id)
            ->orWhereIn('id', User::query()->accessibleBy($authUser)->select('id'));
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $userIds = collect($filters['user_id'] ?? [])->filter()->all();

        if ($userIds !== []) {
            $query->whereIn('user_id', $userIds);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('login_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('login_at', '<=', $filters['date_to']);
        }
    }
}
