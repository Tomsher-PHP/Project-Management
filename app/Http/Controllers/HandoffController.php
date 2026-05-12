<?php

namespace App\Http\Controllers;

use App\Http\Requests\HandoffFormRequest;
use App\Services\HandoffServices;
use Illuminate\Http\Request;

class HandoffController extends Controller
{
    protected HandoffServices $handoffServices;

    public function __construct(HandoffServices $handoffServices)
    {
        $this->handoffServices = $handoffServices;
        view()->share([
            'pageTitle' => 'Handoff Requests',
            'subTitle' => 'Manage and review handoff requests'
        ]);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 15));
        $handoffRequests = $this->handoffServices->getHandoffRequestsForList($request->user(), $perPage, $request->all());
        $filterOptions = $this->handoffServices->getFilterOptions($request->user());

        return view('handoff-requests.index', array_merge([
            'handoffRequests' => $handoffRequests,
            'perPage' => $perPage,
        ], $filterOptions));
    }

    public function store(HandoffFormRequest $request)
    {
        $validated = $request->validated();
        $handoffRequest = $this->handoffServices->createHandoffRequest(
            $validated,
            $request->user()->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Handoff request created successfully.',
            'data' => $handoffRequest
        ]);
    }
}
