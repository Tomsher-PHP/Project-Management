<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesignationRequest;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DesignationController extends Controller
{
    protected $pageTitle;
    protected $subTitle;
    public function __construct()
    {
        $this->pageTitle = 'Designations';
        $this->subTitle = 'Create and organize job roles for structured workforce management.';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $designations = Designation::orderBy('order', 'asc')->paginate($perPage)->withQueryString();

        return view('settings.designations.index', compact('designations', 'perPage'));
    }

    public function store(DesignationRequest $request)
    {
        $designation = Designation::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Designation created successfully.',
            'data' => $designation
        ]);
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        $designation->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Designation updated successfully.',
            'data' => $designation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function toggleStatus(Request $request)
    {
        $designation = Designation::findOrFail($request->id);
        $designation->status = !$designation->status;
        $designation->save();

        return response()->json([
            'success' => true,
            'status' => $designation->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
