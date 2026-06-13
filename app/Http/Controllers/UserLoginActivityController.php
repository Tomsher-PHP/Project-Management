<?php

namespace App\Http\Controllers;

use App\Services\UserLoginActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserLoginActivityController extends Controller
{
    public function __construct(private readonly UserLoginActivityService $userLoginActivityService)
    {
        view()->share([
            'pageTitle' => 'User Login Activity',
            'subTitle' => 'Review recent login and session history',
        ]);
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count'));

        return view('user-login-activities.index', [
            'activities' => $this->userLoginActivityService->getActivities(
                $request->user(),
                $request->all(),
                $perPage
            ),
            'users' => $this->userLoginActivityService->getAccessibleUsers($request->user()),
            'perPage' => $perPage,
        ]);
    }
}
