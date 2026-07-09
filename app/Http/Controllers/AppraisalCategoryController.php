<?php

namespace App\Http\Controllers;

use App\Services\AppraisalSettingsService;
use Illuminate\Http\Request;

class AppraisalCategoryController extends Controller
{
    protected string $pageTitle;

    public function __construct(private readonly AppraisalSettingsService $appraisalSettingsService)
    {
        $this->pageTitle = 'Appraisal Categories';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $appraisalCategories = $this->appraisalSettingsService->getAppraisalCategories();

        return view('settings.appraisal-categories.index', compact('appraisalCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
