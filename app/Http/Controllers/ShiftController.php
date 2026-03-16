<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Shift Management';
        $this->subTitle = 'Shift subtitle here...';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $shifts = Shift::filter($request->all())->orderBy('is_default', 'desc')->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        return view('settings.shifts.index', compact('shifts', 'perPage'));
    }

    public function create()
    {

        return view('settings.shifts.create');
    }

    public function store(ShiftRequest $request, ShiftService $service)
    {
        $service->createShift($request->validated());

        return redirect()->route('settings.shifts.index')->with('success', 'Shift created successfully.');
    }

    public function edit(int $id)
    {
        $shift = Shift::findOrFail($id);

        $editable = $shift->assignments->isNotEmpty() ? 'disabled' : '';

        return view('settings.shifts.edit', compact('shift', 'editable'));
    }

    public function update(ShiftRequest $request, Shift $shift, ShiftService $service)
    {
        $service->updateShifts($shift, $request->validated());

        return redirect()->route('settings.shifts.index')->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        DB::transaction(function () use ($shift) {
            $shift->weekends()->delete();
            $shift->delete();
        });

        return redirect()->back()->with('success', 'Shift deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $shift = Shift::findOrFail($request->id);
        $shift->status = !$shift->status;
        $shift->save();

        return response()->json([
            'success' => true,
            'status' => $shift->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    public function checkAssignment(Shift $shift)
    {
        return response()->json([
            'allocated' => $shift->assignments()->exists(),
            'message' => "This shift is allocated to users. Do you still want to delete it?"
        ]);
    }
}
