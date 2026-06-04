<?php

namespace App\Http\Controllers;

use App\Services\UserRestoreService;
use Illuminate\Http\Request;

class UserRestoreController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Restore Users';
        $this->subTitle = 'Review deleted users and restore them when there is no active email conflict';

        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function restoreIndex(Request $request, UserRestoreService $userRestoreService)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $users = $userRestoreService->getDeletedUsers($perPage);

        return view('users.restore.index', compact('users', 'perPage'));
    }

    public function restore($id, UserRestoreService $userRestoreService)
    {
        $user = $userRestoreService->findDeletedUserOrFail($id);

        if (! $userRestoreService->restoreDeletedUser($user)) {
            return redirect()
                ->back()
                ->with('error', 'This user cannot be restored because the email is already used by an active user.');
        }

        return redirect()
            ->back()
            ->with('success', 'User restored successfully.');
    }

    public function bulkRestore(Request $request, UserRestoreService $userRestoreService)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $result = $userRestoreService->bulkRestoreUsers($validated['user_ids']);

        if ($result['selected_count'] === 0) {
            return redirect()
                ->back()
                ->with('error', 'No deleted users were selected for restore.');
        }

        if ($result['restored_count'] === 0) {
            return redirect()
                ->back()
                ->with('error', 'Selected users could not be restored because their emails are already used by active users.');
        }

        $response = redirect()
            ->back()
            ->with('success', $result['restored_count'] . ' user(s) restored successfully.');

        if ($result['conflicting_count'] > 0) {
            $response->with('warning', $result['conflicting_count'] . ' user(s) were skipped because their emails are already used by active users.');
        }

        return $response;
    }
}
