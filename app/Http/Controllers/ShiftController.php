<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Models\Department;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $shifts = Shift::paginate($perPage)->withQueryString();

        return view('settings.shifts.index', compact('shifts', 'perPage'));
    }

    public function create()
    {

        //Department and Designation can be added later if needed
        $departments = Department::where('status', true)->get();

        return view('settings.shifts.create', compact('departments'));
    }

    public function store(ShiftRequest $request, ShiftService $service)
    {
        $service->createShift($request->validated());

        return redirect()->route('settings.shifts.index')->with('success', 'Shift created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
