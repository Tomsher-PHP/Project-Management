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

    public function __construct()
    {
        $this->pageTitle = 'User Management';
        view()->share('pageTitle', $this->pageTitle);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $users = User::with([
            'details',
            'details.department',
            'details.designation',
            'primaryAttachment'
        ])->where('user_type', '!=', 'super_admin')->where('delete_status', false)->paginate($perPage)->withQueryString();

        return view('users.index', compact('users', 'perPage'));
    }

    public function create()
    {
        //get roles
        $roles = Role::where('user_type', '!=', 'super_admin')->where('status', true)->get();

        //Department and Designation can be added later if needed
        $departments = Department::where('status', true)->get();
        $designations = Designation::where('status', true)->get();

        // Get reporter and managers
        $managers = User::whereNotIn('user_type', ['super_admin', 'normal_user', 'tester'])->where('status', true)->get();

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
        $roles = Role::where('user_type', '!=', 'super_admin')->where('status', true)->get();
        $departments = Department::where('status', true)->get();
        $designations = Designation::where('status', true)->get();
        $managers = User::whereNotIn('user_type', ['super_admin', 'normal_user', 'tester'])->where('status', true)->get();

        return view('users.edit', compact('user', 'roles', 'departments', 'designations', 'managers'));
    }

    public function update(UserRequest $request, User $user, UserService $service)
    {
        $service->updateUser($user, $request->validated());

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(string $id)
    {
        
    }

    public function toggleStatus(Request $request)
    {
        $user = User::findOrFail($request->userId);
        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'success' => true,
            'status' => $user->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
