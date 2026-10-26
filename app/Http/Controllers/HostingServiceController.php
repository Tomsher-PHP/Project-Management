<?php

namespace App\Http\Controllers;

use App\Http\Requests\HostingServiceRequest;
use App\Models\HostingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class HostingServiceController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Server & Hosting Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $hostingServices = HostingService::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $nextSortOrder = ((int) HostingService::max('sort_order')) + 1;

        return view('settings.hosting-services.index', compact('hostingServices', 'perPage', 'nextSortOrder'));
    }

    public function store(HostingServiceRequest $request)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $hostingService = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults();
            }

            return HostingService::create($data);
        });

        return response()->json([
            'status' => true,
            'message' => 'Hosting service created successfully.',
            'data' => $hostingService,
        ]);
    }

    public function update(HostingServiceRequest $request, HostingService $hostingService)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $hostingService = DB::transaction(function () use ($hostingService, $data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults($hostingService->id);
            }

            $hostingService->update($data);

            return $hostingService->refresh();
        });

        return response()->json([
            'status' => true,
            'message' => 'Hosting service updated successfully.',
            'data' => $hostingService,
        ]);
    }

    public function destroy(HostingService $hostingService)
    {
        if ($hostingService->is_system) {
            return redirect()
                ->route('settings.hosting-services.index')
                ->with('error', 'System hosting service cannot be deleted.');
        }

        $hostingService->delete();

        return redirect()
            ->route('settings.hosting-services.index')
            ->with('success', 'Hosting service deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $hostingService = HostingService::findOrFail($request->id);
        $hostingService->is_active = !$hostingService->is_active;
        $hostingService->save();

        return response()->json([
            'success' => true,
            'is_active' => $hostingService->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(?int $exceptId = null): void
    {
        $query = HostingService::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
