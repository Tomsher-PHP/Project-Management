<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Leave Types';
        $this->subTitle = 'Manage the different types of leave available in the organization.';
    }

    /**
     * Display a listing of leave types.
     */
    public function index(): View
    {
        $leaveTypes = LeaveType::query()
            ->latest()
            ->paginate(10);

        return view('settings.leave_types.index', [
            'pageTitle' => $this->pageTitle,
            'subTitle' => $this->subTitle,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Show the form for creating a new leave type.
     */
    public function create(): View
    {
        return view('settings.leave_types.create', [
            'pageTitle' => 'Create Leave Type',
            'subTitle' => 'Add a new leave type to the system.',
        ]);
    }

    /**
     * Store a newly created leave type.
     */
    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'color' => $request->color,
            'description' => $request->description,
            'is_file_upload_required' => $request->boolean('is_file_upload_required'),
            'is_paid' => $request->boolean('is_paid'),
            'status' => $request->boolean('status'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('settings.leave-types.index')
            ->with('success', 'Leave type created successfully.');
    }

    /**
     * Display the specified leave type.
     */
    public function show(string $id): View
    {
        $leaveType = LeaveType::findOrFail($id);

        return view('settings.leave_types.edit', [
            'pageTitle' => 'Leave Type',
            'subTitle' => 'View leave type details.',
            'leaveType' => $leaveType,
        ]);
    }

    /**
     * Show the form for editing the specified leave type.
     */
    public function edit(string $id): View
    {
        $leaveType = LeaveType::findOrFail($id);

        return view('settings.leave_types.edit', [
            'pageTitle' => 'Edit Leave Type',
            'subTitle' => 'Update leave type details.',
            'leaveType' => $leaveType,
        ]);
    }

    /**
     * Update the specified leave type.
     */
    public function update(
        LeaveTypeRequest $request,
        string $id
    ): RedirectResponse {
        $leaveType = LeaveType::findOrFail($id);

        $leaveType->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'color' => $request->color,
            'description' => $request->description,
            'is_file_upload_required' => $request->boolean('is_file_upload_required'),
            'is_paid' => $request->boolean('is_paid'),
            'status' => $request->boolean('status'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('settings.leave-types.index')
            ->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified leave type.
     */
    public function destroy(string $id): RedirectResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        /*
         * Do not delete a leave type that is already being used
         * by leave balances or leave requests.
         */
        if (
            $leaveType->balances()->exists() ||
            $leaveType->leaveRequests()->exists()
        ) {
            return redirect()
                ->route('settings.leave-types.index')
                ->with(
                    'error',
                    'This leave type cannot be deleted because it is already being used.'
                );
        }

        $leaveType->delete();

        return redirect()
            ->route('settings.leave-types.index')
            ->with('success', 'Leave type deleted successfully.');
    }
}
