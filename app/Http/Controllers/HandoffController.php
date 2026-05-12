<?php

namespace App\Http\Controllers;

use App\Http\Requests\HandoffFormRequest;
use App\Services\HandoffServices;

class HandoffController extends Controller
{
    protected HandoffServices $handoffServices;

    public function __construct(HandoffServices $handoffServices)
    {
        $this->handoffServices = $handoffServices;
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
