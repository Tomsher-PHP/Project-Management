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
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Display a listing of leave types.
     */
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $leaveTypes = LeaveType::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();

        $nextSortOrder = ((int) LeaveType::max('sort_order')) + 1;

        return view('settings.leave_types.index', compact('leaveTypes', 'perPage', 'nextSortOrder'));
    }

    /**
     * Store a newly created leave type.
     */
    public function store(LeaveTypeRequest $request)
    {
        $leaveType = LeaveType::create([
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

        return response()->json([
            'status' => true,
            'message' => 'Leave type created successfully.',
            'data' => $leaveType
        ]);
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
     * Update the specified leave type.
     */
    public function update(LeaveTypeRequest $request, LeaveType $leaveType)
    {
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

        return response()->json([
            'status' => true,
            'message' => 'Leave Type updated successfully.',
            'data' => $leaveType
        ]);
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
