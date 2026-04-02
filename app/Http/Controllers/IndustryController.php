<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndustryRequest;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IndustryController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Industries';
        $this->subTitle = 'Manage your industries';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $industries = Industry::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $parentIndustries = Industry::where('parent_id', null)->orderBy('sort_order', 'asc')->get();

        return view('settings.industries.index', compact('industries', 'perPage', 'parentIndustries'));
    }

    public function store(IndustryRequest $request)
    {
        $industry = activity()->withoutLogs(fn () => Industry::create($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Industry created successfully.',
            'data' => $industry
        ]);
    }

    public function update(IndustryRequest $request, Industry $industry)
    {
        activity()->withoutLogs(fn () => $industry->update($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Industry updated successfully.',
            'data' => $industry
        ]);
    }

    public function destroy(Industry $industry)
    {
        if ($industry->default) {
            return redirect()
                ->route('settings.industries.index')
                ->with('error', 'Default industry cannot be deleted.');
        }
        // Check if this industry has children
        $hasChildren = Industry::where('parent_id', $industry->id)->exists();

        if ($hasChildren) {
            return redirect()
                ->route('settings.industries.index')
                ->with('error', 'This industry has child, cannot be deleted.');
        }

        activity()->withoutLogs(fn () => $industry->delete());

        return redirect()
            ->route('settings.industries.index')
            ->with('success', 'Industry deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $industry = Industry::findOrFail($request->id);
        $industry->status = !$industry->status;
        activity()->withoutLogs(fn () => $industry->save());

        return response()->json([
            'success' => true,
            'status' => $industry->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
