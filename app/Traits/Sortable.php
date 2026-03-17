<?php

namespace App\Traits;

trait Sortable
{
    public function scopeSort($query, $request)
    {
        $sortBy = $request->get('sort_by');
        $sortDir = $request->get('sort_dir', 'asc');

        // Allow only asc/desc
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        // Allowed columns (define in model)
        if (!$sortBy || !in_array($sortBy, $this->sortable ?? [])) {
            return $query;
        }

        return $query->orderBy($sortBy, $sortDir);
    }
}
