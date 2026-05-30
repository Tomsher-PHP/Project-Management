<?php

namespace App\Http\Controllers;

use App\Services\DashboardServices;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('dashboard.view');
    }

    public function summary(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();
        $data = $dashboardServices->getDashboardSummary($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

