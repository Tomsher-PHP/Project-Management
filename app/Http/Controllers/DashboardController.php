<?php

namespace App\Http\Controllers;

use App\Services\DashboardServices;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();
        $notificationCounts = $dashboardServices->getRequestNotificationCounts($user);

        return view('dashboard.view', $notificationCounts);
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

