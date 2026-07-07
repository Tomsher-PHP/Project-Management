<?php

namespace App\Services\Reports\Concerns;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesTeamUserFilters
{
    public function getSelectedUserIds(Request $request): array
    {
        return $this->resolveFilterIds($request, ['user_id', 'staff_id', 'users']);
    }

    public function getFilterTeams(Request $request): Collection
    {
        $accessibleUserIds = $this->getAccessibleUserIds($request->user());

        if ($accessibleUserIds === []) {
            return collect();
        }

        return Team::query()
            ->with(['users' => function ($query) use ($accessibleUserIds) {
                $query
                    ->select('users.id', 'users.name')
                    ->whereIn('users.id', $accessibleUserIds)
                    ->orderBy('users.name');
            }])
            ->whereHas('users', function ($query) use ($accessibleUserIds) {
                $query->whereIn('users.id', $accessibleUserIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function getScopedUserIds(Request $request): array
    {
        $accessibleUserIds = $this->getAccessibleUserIds($request->user());
        $selectedUserIds = $this->getSelectedUserIds($request);

        if ($this->hasExplicitUserFilter($request)) {
            return array_values(array_intersect($accessibleUserIds, $selectedUserIds));
        }

        if ($request->has('user_filter_applied')) {
            return $accessibleUserIds;
        }

        if ($this->getSelectedTeamIds($request) === []) {
            return $accessibleUserIds;
        }

        return $this->getTeamScopedAccessibleUserIds($request);
    }

    protected function getUserIdsForExcludedUserFilter(Request $request): array
    {
        if ($this->hasExplicitUserFilter($request)) {
            return $this->getAccessibleUserIds($request->user());
        }

        if ($request->has('user_filter_applied')) {
            return $this->getAccessibleUserIds($request->user());
        }

        return $this->getTeamScopedAccessibleUserIds($request);
    }

    protected function getTeamScopedAccessibleUserIds(Request $request): array
    {
        $accessibleUserIds = $this->getAccessibleUserIds($request->user());
        $selectedTeamIds = $this->getSelectedTeamIds($request);

        if ($selectedTeamIds === []) {
            return $accessibleUserIds;
        }

        $selectedTeamUserIds = $this->getSelectedTeamUserIds($request);

        return array_values(array_intersect($accessibleUserIds, $selectedTeamUserIds));
    }

    protected function resolveFilterIds(Request $request, array $keys): array
    {
        return collect($keys)
            ->flatMap(function (string $key) use ($request) {
                $value = $request->input($key, []);

                if (! is_array($value)) {
                    $value = [$value];
                }

                return $value;
            })
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function getSelectedTeamUserIds(Request $request): array
    {
        $teamIds = $this->getSelectedTeamIds($request);

        if ($teamIds === []) {
            return [];
        }

        return Team::query()
            ->whereIn('id', $teamIds)
            ->with('users:id')
            ->get(['id'])
            ->pluck('users')
            ->flatten()
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function getSelectedTeamIds(Request $request): array
    {
        return $this->resolveFilterIds($request, ['teams', 'team_id']);
    }

    protected function hasExplicitUserFilter(Request $request): bool
    {
        return $this->getSelectedUserIds($request) !== [];
    }
}
