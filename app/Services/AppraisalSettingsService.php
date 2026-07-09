<?php

namespace App\Services;

use App\Models\AppraisalCategory;
use Illuminate\Database\Eloquent\Collection;

class AppraisalSettingsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAppraisalCategories(): Collection
    {
        return AppraisalCategory::query()
            ->with('questions')
            ->withCount('questions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
