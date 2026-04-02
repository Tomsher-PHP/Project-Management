<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'User Management';
        $this->subTitle = 'Keep your team organized and secure';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $users = User::accessibleBy(auth()->user())
            ->filter($request->all())
            ->sort($request->all())
            ->with([
                'details',
                'details.department',
                'details.designation',
                'primaryAttachment'
            ])
            ->where('is_super_admin', false)
            ->where('delete_status', false)
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::get();
        $departments = Department::orderBy('sort_order', 'asc')->get();
        $designations = Designation::orderBy('sort_order', 'asc')->get();

        return view('users.index', compact('users', 'perPage', 'roles', 'departments', 'designations'));
    }

    public function create()
    {
        //get roles
        $roles = Role::active()->get();

        //Department and Designation can be added later if needed
        $departments = Department::active()->orderBy('sort_order', 'asc')->get();
        $designations = Designation::active()->orderBy('sort_order', 'asc')->get();

        // Get reporter and managers
        $managers = app(UserService::class)->getAccessibleUsers(auth()->user());

        return view('users.create', compact('roles', 'departments', 'designations', 'managers'));
    }

    public function store(UserRequest $request, UserService $service)
    {
        $service->createUser($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::active()->get();
        $departments = Department::active()->orderBy('sort_order', 'asc')->get();
        $designations = Designation::active()->orderBy('sort_order', 'asc')->get();
        $managerIds = collect([
            $user->details?->reporter_id,
            $user->details?->manager_id,
        ])->filter()->unique()->values()->all();

        $managers = app(UserService::class)->getAccessibleUsers(auth()->user(), [], $managerIds);

        return view('users.edit', compact('user', 'roles', 'departments', 'designations', 'managers'));
    }

    public function update(UserRequest $request, User $user, UserService $service)
    {
        $service->updateUser($user, $request->validated());

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting super admin
        if ($user->is_super_admin) {
            return back()->with('error', 'Super Admin cannot be deleted.');
        }

        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->update([
            'delete_status' => true,
            'is_active' => false,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
