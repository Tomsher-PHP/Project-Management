<?php

namespace App\Traits;

trait Filterable
{
    public function scopeFilter($query, $filters)
    {
        if ($filters) {
            // dd($filters);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $condition = $filters['search_condition'] ?? 'contains';

            //switch case
            switch ($condition) {
                case 'all':
                    $query->where('name', 'like', '%' . $filters['search'] . '%');
                    break;
                case 'starts_with':
                    $query->where('name', 'like', $filters['search'] . '%');
                    break;
                case 'ends_with':
                    $query->where('name', 'like', '%' . $filters['search']);
                    break;
                case 'contains':
                    $query->where('name', 'like', '%' . $filters['search'] . '%');
                    break;
                case 'not_contains':
                    $query->where('name', 'not like', '%' . $filters['search'] . '%');
                    break;
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['parent_id']) && $filters['parent_id'] !== '') {
            $query->whereIn('parent_id', (array)$filters['parent_id']);
        }

        // --- 3. Handle Dynamic / Module-Specific Filters ---
        $dynamicFilters = $filters;
        unset($dynamicFilters['search'], $dynamicFilters['search_condition'], $dynamicFilters['status']);

        foreach ($dynamicFilters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Handle multi-select arrays
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }
}
