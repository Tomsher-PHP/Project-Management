<?php

namespace App\Http\Controllers;

use App\Models\DatabaseNotification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected string $pageTitle;

    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Notifications';
        $this->subTitle = 'View and manage your notifications';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count', 10));
        $selectedStatus = $request->input('read_status', 'unread');
        if (! in_array($selectedStatus, ['unread', 'read'], true)) {
            $selectedStatus = 'unread';
        }

        // Apply security constraint: users must only view their own notifications
        $query = DatabaseNotification::query()
            ->where('notifiable_id', auth()->id())
            ->where('notifiable_type', auth()->user()->getMorphClass())
            ->with(['project', 'user']);

        // Tab filtering
        if ($selectedStatus === 'unread') {
            $query->whereNull('read_at');
        } else {
            $query->whereNotNull('read_at');
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Project filter
        if ($request->filled('project_id')) {
            $query->whereIn('project_id', (array) $request->input('project_id'));
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->whereIn('user_id', (array) $request->input('user_id'));
        }

        // Order by created_at desc (standard/default order for notifications)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $notifications = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        // Populate dropdown filters securely (only projects and users from current user's notifications)
        $projects = Project::whereIn('id', function ($q) {
            $q->select('project_id')
                ->from('notifications')
                ->where('notifiable_id', auth()->id())
                ->where('notifiable_type', auth()->user()->getMorphClass())
                ->whereNotNull('project_id')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);

        $users = User::whereIn('id', function ($q) {
            $q->select('user_id')
                ->from('notifications')
                ->where('notifiable_id', auth()->id())
                ->where('notifiable_type', auth()->user()->getMorphClass())
                ->whereNotNull('user_id')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);

        return view('notifications.index', compact(
            'notifications',
            'selectedStatus',
            'projects',
            'users',
            'perPage'
        ));
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:notifications,id',
        ]);

        // Security constraint: only delete notifications that belong to the logged-in user
        auth()->user()->notifications()
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Selected notifications deleted successfully.',
        ]);
    }

    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:notifications,id',
        ]);

        // Security constraint: only update notifications belonging to the logged-in user
        auth()->user()->notifications()
            ->whereIn('id', $request->ids)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Selected notifications marked as read successfully.',
        ]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        // Security constraint: only delete notifications belonging to the logged-in user
        auth()->user()->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared successfully.',
        ]);
    }

    public function markAllRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    public function markRead(string $notification)
    {
        $notification = auth()->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect($notification->data['url'] ?? url()->previous());
    }
}
