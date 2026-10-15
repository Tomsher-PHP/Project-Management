<?php

namespace App\Http\Controllers;

use App\Http\Requests\HostingProviderRequest;
use App\Models\HostingProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class HostingProviderController extends Controller
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

        $hostingProviders = HostingProvider::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $nextSortOrder = ((int) HostingProvider::max('sort_order')) + 1;

        return view('settings.hosting-providers.index', compact('hostingProviders', 'perPage', 'nextSortOrder'));
    }

    public function store(HostingProviderRequest $request)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $hostingProvider = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults();
            }

            return HostingProvider::create($data);
        });

        return response()->json([
            'status' => true,
            'message' => 'Hosting provider created successfully.',
            'data' => $hostingProvider,
        ]);
    }

    public function update(HostingProviderRequest $request, HostingProvider $hostingProvider)
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        $hostingProvider = DB::transaction(function () use ($hostingProvider, $data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults($hostingProvider->id);
            }

            $hostingProvider->update($data);

            return $hostingProvider->refresh();
        });

        return response()->json([
            'status' => true,
            'message' => 'Hosting provider updated successfully.',
            'data' => $hostingProvider,
        ]);
    }

    public function destroy(HostingProvider $hostingProvider)
    {
        if ($hostingProvider->is_system) {
            return redirect()
                ->route('settings.hosting-providers.index')
                ->with('error', 'System hosting provider cannot be deleted.');
        }

        $hostingProvider->delete();

        return redirect()
            ->route('settings.hosting-providers.index')
            ->with('success', 'Hosting provider deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $hostingProvider = HostingProvider::findOrFail($request->id);
        $hostingProvider->is_active = !$hostingProvider->is_active;
        $hostingProvider->save();

        return response()->json([
            'success' => true,
            'is_active' => $hostingProvider->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(?int $exceptId = null): void
    {
        $query = HostingProvider::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
