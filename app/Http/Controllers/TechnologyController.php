<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnologyRequest;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TechnologyController extends Controller
{

    protected $pageTitle;
    protected $subTitle;

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

        return view('settings.technologies.index', compact('technologies', 'perPage'));
    }

    public function store(TechnologyRequest $request)
    {
        $technology = activity()->withoutLogs(fn () => Technology::create($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Technology created successfully.',
            'data' => $technology
        ]);
    }

    public function update(TechnologyRequest $request, Technology $technology)
    {
        activity()->withoutLogs(fn () => $technology->update($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Technology updated successfully.',
            'data' => $technology
        ]);
    }

    public function destroy(Technology $technology)
    {
        if ($technology->default) {
            return redirect()
                ->route('settings.technologies.index')
                ->with('error', 'Default technology cannot be deleted.');
        }

        activity()->withoutLogs(fn () => $technology->delete());

        return redirect()
            ->route('settings.technologies.index')
            ->with('success', 'Technology deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $technology = Technology::findOrFail($request->id);
        $technology->status = !$technology->status;
        activity()->withoutLogs(fn () => $technology->save());

        return response()->json([
            'success' => true,
            'status' => $technology->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
