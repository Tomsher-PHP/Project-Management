<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
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
        $projectStatuses = ProjectStatus::active()->orderBy('sort_order')->get();
        $projectCategories = ProjectCategory::active()->orderBy('sort_order')->get();
        $customers = Customer::active()->orderBy('name')->get();

        $viewData = array_merge($notificationCounts, [
            'workedTimeData' => $workedTimeData,
            'runningTasksData' => $runningTasksData,
            'projectStatuses' => $projectStatuses,
            'projectCategories' => $projectCategories,
            'customers' => $customers,
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

    public function tileDetails(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();
        $type = $request->query('type');
        
        $data = $dashboardServices->getTileDetails($user, $type);
        
        return response()->json([
            'success' => true,
            'html' => view('dashboard.partials.tile-details-modal-content', $data)->render(),
        ]);
    }

    public function projectsCount(Request $request, DashboardServices $dashboardServices)
    {
        $user = $request->user();

        $projectFlow = $request->input('project_flow');

        $categoryIds = $request->input('project_category_ids') ?? $request->input('project_category_id');
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }
        if (is_array($categoryIds)) {
            $categoryIds = array_filter(array_map('intval', $categoryIds));
        } else {
            $categoryIds = [];
        }

        $customerId = $request->input('customer_id');
        $customerId = ($customerId !== null && is_numeric($customerId)) ? (int) $customerId : null;

        $statusIds = $request->input('status_id') ?? $request->input('status_ids');
        if (is_string($statusIds)) {
            $statusIds = explode(',', $statusIds);
        }
        if (is_array($statusIds)) {
            $statusIds = array_filter(array_map('intval', $statusIds));
        } else {
            $statusIds = [];
        }

        $month = $request->input('month');
        $month = ($month !== null && is_numeric($month)) ? (int) $month : null;

        $chartData = $dashboardServices->getProjectsCountChartData(
            $user,
            $statusIds,
            $month,
            $projectFlow,
            $categoryIds,
            $customerId
        );

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }
}

