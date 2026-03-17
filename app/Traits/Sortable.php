<?php

namespace App\Traits;

trait Sortable
{
    public function scopeSort($query, $request)
    {
        $sortBy = isset($request['sort_by']) ? $request['sort_by'] : null;
        $sortDir = isset($request['sort_dir']) ? $request['sort_dir'] : 'asc';

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
