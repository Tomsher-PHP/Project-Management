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
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Role Management';
        $this->subTitle = 'Define user roles and system access';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $roles = Role::paginate($perPage)->withQueryString();
        return view('roles.index', compact('roles', 'perPage'));
    }

    public function create()
    {
        $permissions = Permission::get()
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            });
        return view('roles.create', compact('permissions'));
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

        $permissions = Permission::get()
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            });
        return view('roles.edit', compact('role', 'permissions'));
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
        $role->status = !$role->status;
        $role->save();

        return response()->json([
            'success' => true,
            'status' => $role->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    // Commented === Not usable for now ===
    // public function getPermissionsByUserType(Request $request)
    // {
    //     $userType = $request->user_type;
    //     $roleId = $request->role_id ?? null;
    //     $role = null;
    //     if ($roleId) {
    //         $role = Role::findById($roleId);
    //         if ($role->user_type !== $userType) {
    //             return response()->json(['error' => 'Role user type does not match the selected user type.'], Response::HTTP_BAD_REQUEST);
    //         }
    //     }

    //     $allowed = config("constants.user_type_permissions.$userType", []);

    //     $permissions = Permission::where(function ($query) use ($allowed) {
    //         foreach ($allowed as $permission) {

    //             if ($permission === '*') {
    //                 return;
    //             }

    //             if (str_contains($permission, '*')) {
    //                 $query->orWhere('name', 'like', str_replace('*', '%', $permission));
    //             } else {
    //                 $query->orWhere('name', $permission);
    //             }
    //         }
    //     })
    //         ->get()
    //         ->groupBy(function ($permission) {
    //             return explode('.', $permission->name)[0];
    //         });

    //     return view('roles.permissions', compact('permissions', 'role'))->render();
    // }
}
