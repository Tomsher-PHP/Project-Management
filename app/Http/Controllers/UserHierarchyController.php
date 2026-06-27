<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserHierarchyController extends Controller
{
    public function __construct()
    {
        view()->share([
            'pageTitle' => 'User Hierarchy',
            'subTitle' => 'Understand reporting lines across your team',
        ]);
    }

    public function index(): View
    {
        $superAdmins = User::query()
            ->with([
                'details:id,user_id,employee_id,designation_id',
                'details.designation:id,name',
                'primaryAttachment',
                'roles:id,name',
            ])
            ->where('is_super_admin', true)
            ->where('delete_status', false)
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->with([
                'details:id,user_id,reporter_id,manager_id,department_id,designation_id,employee_id',
                'details.department:id,name',
                'details.designation:id,name',
                'details.manager:id,name',
                'details.reporter:id,name',
                'primaryAttachment',
                'roles:id,name',
            ])
            ->where('is_super_admin', false)
            ->where('delete_status', false)
            ->orderBy('name')
            ->get();

        $fallbackRootId = (int) ($superAdmins->first()?->id ?? 0);
        $validReporterIds = $users->pluck('id')
            ->merge($superAdmins->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->flip();

        $childrenByReporterId = $users->groupBy(function (User $user) use ($fallbackRootId, $validReporterIds) {
            $reporterId = (int) ($user->details?->reporter_id ?? 0);

            if ($reporterId > 0 && $reporterId !== (int) $user->id && $validReporterIds->has($reporterId)) {
                return $reporterId;
            }

            return $fallbackRootId;
        });

        $visited = [];
        $roots = $superAdmins->map(function (User $superAdmin) use ($childrenByReporterId, &$visited) {
            return [
                'user' => $superAdmin,
                'children' => $this->buildHierarchyNodes($childrenByReporterId, (int) $superAdmin->id, $visited),
                'is_virtual' => false,
            ];
        })->values();

        if ($roots->isEmpty()) {
            $roots = collect([[
                'user' => null,
                'label' => 'Super Admin',
                'children' => $this->buildHierarchyNodes($childrenByReporterId, 0, $visited),
                'is_virtual' => true,
            ]]);
        }

        $detachedNodes = $this->buildDetachedNodes($users, $childrenByReporterId, $visited);

        if ($detachedNodes !== []) {
            $firstRoot = $roots->first();
            $firstRoot['children'] = [...$firstRoot['children'], ...$detachedNodes];
            $roots[0] = $firstRoot;
        }

        return view('users.tree-view', [
            'roots' => $roots,
            'totalUsers' => $users->count(),
            'superAdminCount' => max($superAdmins->count(), 1),
            'usersWithoutReporterCount' => $users->filter(fn (User $user) => blank($user->details?->reporter_id))->count(),
            'hasDetachedNodes' => $detachedNodes !== [],
        ]);
    }

    private function buildHierarchyNodes(Collection $childrenByReporterId, int $reporterId, array &$visited): array
    {
        return $childrenByReporterId
            ->get($reporterId, collect())
            ->map(function (User $user) use ($childrenByReporterId, &$visited) {
                $userId = (int) $user->id;

                if (isset($visited[$userId])) {
                    return null;
                }

                $visited[$userId] = true;

                return [
                    'user' => $user,
                    'children' => $this->buildHierarchyNodes($childrenByReporterId, $userId, $visited),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildDetachedNodes(Collection $users, Collection $childrenByReporterId, array &$visited): array
    {
        $nodes = [];

        foreach ($users as $user) {
            $userId = (int) $user->id;

            if (isset($visited[$userId])) {
                continue;
            }

            $visited[$userId] = true;
            $nodes[] = [
                'user' => $user,
                'children' => $this->buildHierarchyNodes($childrenByReporterId, $userId, $visited),
                'is_detached' => true,
            ];
        }

        return $nodes;
    }
}
