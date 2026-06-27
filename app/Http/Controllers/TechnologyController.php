<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnologyRequest;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TechnologyController extends Controller
{

    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Technologies';
        $this->subTitle = 'Manage your project technologies';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $technologies = Technology::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) Technology::max('sort_order')) + 1;

        return view('settings.technologies.index', compact('technologies', 'perPage', 'nextSortOrder'));
    }

    public function store(TechnologyRequest $request)
    {
        $technology = Technology::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Technology created successfully.',
            'data' => $technology
        ]);
    }

    public function update(TechnologyRequest $request, Technology $technology)
    {
        $technology->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Technology updated successfully.',
            'data' => $technology
        ]);
    }

    public function destroy(Technology $technology)
    {
        if ($technology->is_system) {
            return redirect()
                ->route('settings.technologies.index')
                ->with('error', 'System technology cannot be deleted.');
        }

        $technology->delete();

        return redirect()
            ->route('settings.technologies.index')
            ->with('success', 'Technology deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $technology = Technology::findOrFail($request->id);
        $technology->is_active = !$technology->is_active;
        $technology->save();

        return response()->json([
            'success' => true,
            'is_active' => $technology->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
