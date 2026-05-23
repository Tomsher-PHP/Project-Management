<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolePermissionRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Role Management';
        $this->subTitle = 'Define user roles and system access';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $roles = Role::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        return view('roles.index', compact('roles', 'perPage'));
    }

    public function create()
    {
        $permissions = $this->getGroupedPermissions();

        $defaultCheckedPermissions = collect(config('system_permissions'))
            ->filter(fn($permission) => !empty($permission['default_checked']))
            ->pluck('name')
            ->toArray();

        return view('roles.create', compact('permissions', 'defaultCheckedPermissions'));
    }

    public function store(RolePermissionRequest $request)
    {
        DB::transaction(function () use ($request) {

            $role = Role::create(['name' => $request->name]);

            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);
        });

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::findById($id);

        $permissions = $this->getGroupedPermissions();

        return view('roles.edit', compact('role', 'permissions'));
    }

    private function getGroupedPermissions()
    {
        return Permission::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            });
    }

    public function update(RolePermissionRequest $request, $id)
    {
        $role = Role::findById($id);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);

            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();

            $role->syncPermissions($permissions);
        });

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $role = Role::findById($request->id);
        $role->is_active = !$role->is_active;
        $role->save();

        return response()->json([
            'success' => true,
            'is_active' => $role->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
