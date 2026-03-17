<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;

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

        $users = User::filter($request->all())
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
        $departments = Department::orderBy('order', 'asc')->get();
        $designations = Designation::orderBy('order', 'asc')->get();

        return view('users.index', compact('users', 'perPage', 'roles', 'departments', 'designations'));
    }

    public function create()
    {
        //get roles
        $roles = Role::where('status', true)->get();

        //Department and Designation can be added later if needed
        $departments = Department::active()->orderBy('order', 'asc')->get();
        $designations = Designation::active()->orderBy('order', 'asc')->get();

        // Get reporter and managers
        $managers = User::active()->get();

        return view('users.create', compact('roles', 'departments', 'designations', 'managers'));
    }

    public function store(UserRequest $request, UserService $service)
    {
        $service->createUser($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(int $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where('status', true)->get();
        $departments = Department::active()->orderBy('order', 'asc')->get();
        $designations = Designation::active()->orderBy('order', 'asc')->get();
        $managers = User::active()->get();

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
            'status' => false,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'success' => true,
            'status' => $user->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
