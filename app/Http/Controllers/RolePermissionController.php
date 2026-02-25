<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolePermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    protected $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Role & Permissions';
        view()->share('pageTitle', $this->pageTitle);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 4);

        $roles = Role::with('permissions')->where('user_type', '!=', 'super_admin')->paginate($perPage)->withQueryString();
        return view('roles.index', compact('roles', 'perPage'));
    }

    public function create()
    {
        $permissions = Permission::where('user_type', 'normal_user')->get();
        $userTypes = config('constants.user_types');
        unset($userTypes['super_admin']); // Remove super_admin from the list
        return view('roles.create', compact('permissions', 'userTypes'));
    }

    public function store(RolePermissionRequest $request)
    {
        DB::transaction(function () use ($request) {

            $role = Role::create(['name' => $request->name, 'user_type' => $request->user_type]);

            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);
        });

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::findById($id);
        $permissions = Permission::where('user_type', $role->user_type)->get();
        $userTypes = config('constants.user_types');
        unset($userTypes['super_admin']); // Remove super_admin from the list
        return view('roles.edit', compact('role', 'permissions', 'userTypes'));
    }


    public function update(RolePermissionRequest $request, $id)
    {
        $role = Role::findById($id);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);

            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();

            $role->syncPermissions($permissions);
        });

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $role = Role::findById($request->roleId);
        $role->status = !$role->status;
        $role->save();

        return response()->json([
            'success' => true,
            'status' => $role->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    public function getPermissionsByUserType(Request $request)
    {
        $userType = $request->user_type;
        $roleId = $request->role_id ?? null;
        $role = null;
        if ($roleId) {
            $role = Role::findById($roleId);
            if ($role->user_type !== $userType) {
                return response()->json(['error' => 'Role user type does not match the selected user type.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $allowed = config("constants.user_type_permissions.$userType", []);

        $permissions = Permission::where(function ($query) use ($allowed) {
            foreach ($allowed as $permission) {

                if ($permission === '*') {
                    return;
                }

                if (str_contains($permission, '*')) {
                    $query->orWhere('name', 'like', str_replace('*', '%', $permission));
                } else {
                    $query->orWhere('name', $permission);
                }
            }
        })
            ->where('user_type', $userType)
            ->get()
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            });

        return view('roles.permissions', compact('permissions', 'role'))->render();
    }
}
