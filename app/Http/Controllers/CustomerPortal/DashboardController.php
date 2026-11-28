<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $guard = (string) config('customer_portal.authentication.guard', 'customer');
        $customer = Auth::guard($guard)->user();

        return view('customer-portal.dashboard.index', [
            'customer' => $customer,
        ]);
    }
}
