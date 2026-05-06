<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiRequest;
use App\Models\Kpi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KPIController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'KPIs';
        $this->subTitle = 'Manage your KPIs';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $kpis = Kpi::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();

        return view('settings.kpis.index', compact('kpis', 'perPage'));
    }

    public function store(KpiRequest $request)
    {
        $kpi = Kpi::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'KPI created successfully.',
            'data' => $kpi
        ]);
    }

    public function update(KpiRequest $request, Kpi $kpi)
    {
        $kpi->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'KPI updated successfully.',
            'data' => $kpi
        ]);
    }

    public function destroy(Kpi $kpi)
    {
        if ($kpi->is_system) {
            return redirect()
                ->route('settings.kpis.index')
                ->with('error', 'System KPI cannot be deleted.');
        }

        $kpi->delete();

        return redirect()
            ->route('settings.kpis.index')
            ->with('success', 'KPI deleted successfully.');
    }

    public function toggleStatusKPI(Request $request)
    {
        $kpi = Kpi::findOrFail($request->id);
        $kpi->is_active = !$kpi->is_active;
        $kpi->save();

        return response()->json([
            'success' => true,
            'is_active' => $kpi->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
