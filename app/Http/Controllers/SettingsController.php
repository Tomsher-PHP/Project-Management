<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Settings';
        $this->subTitle = 'Settings subtitle here';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $departments = Department::orderBy('order', 'asc')->paginate($perPage)->withQueryString();

        $designations = [];
        return view('settings.index', compact('departments', 'designations', 'perPage'));
    }
}
