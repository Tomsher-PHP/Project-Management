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

        $timezone = config('constants.timezone', 'UTC');
        $todayStr = now($timezone)->toDateString();
        $workedTimeData = $dashboardServices->getUsersTaskWorkedTime($user, $todayStr);
        $runningTasksData = $dashboardServices->getRunningTasks($user);

        $viewData = array_merge($notificationCounts, [
            'workedTimeData' => $workedTimeData,
            'runningTasksData' => $runningTasksData,
        ]);

        return view('dashboard.view', $viewData);
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

    public function workedTime(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();
        $timezone = config('constants.timezone', 'UTC');
        $date = $request->query('date', now($timezone)->toDateString());

        $workedTimeData = $dashboardServices->getUsersTaskWorkedTime($user, $date);

        return response()->json([
            'success' => true,
            'data' => $workedTimeData,
        ]);
    }

    public function runningTasks(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();
        $runningTasksData = $dashboardServices->getRunningTasks($user, 5);

        return response()->json([
            'success' => true,
            'data' => $runningTasksData->items(),
            'current_page' => $runningTasksData->currentPage(),
            'has_more_pages' => $runningTasksData->hasMorePages(),
            'next_page' => $runningTasksData->hasMorePages() ? $runningTasksData->currentPage() + 1 : null,
            'total' => $runningTasksData->total(),
        ]);
    }
}

