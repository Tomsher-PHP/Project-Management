<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Settings';

        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index()
    {
        $settingsPermissions = config('constants.settings_permissions');
        $hasSettingsAccess = collect($settingsPermissions)->contains(fn($permission) => auth()->user()->can($permission));

        return view('settings.index', compact('hasSettingsAccess'));
    }
}
