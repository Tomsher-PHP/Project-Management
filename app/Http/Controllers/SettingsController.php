<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Settings';
        $this->subTitle = 'Settings subtitle here';

        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index()
    {
        $settingsPermissions = config('constants.settings_permissions');
        $hasSettingsAccess = collect($settingsPermissions)->contains(fn($permission) => auth()->user()->can($permission));

        return view('settings.index', compact('hasSettingsAccess'));
    }
}
