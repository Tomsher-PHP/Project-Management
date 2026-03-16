<?php

namespace App\Traits;

trait Filterable
{
    public function scopeFilter($query, $filters)
    {

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

        if (isset($filters['role_id']) && !empty($filters['role_id'])) {
            $roles = (array) $filters['role_id'];

            $query->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('roles.id', $roles);
            });
        }

        // --- 3. Handle Dynamic / Module-Specific Filters ---
        $dynamicFilters = $filters;
        unset($dynamicFilters['search'], $dynamicFilters['search_condition'], $dynamicFilters['status'], $dynamicFilters['role_id'], $dynamicFilters['per_page'], $dynamicFilters['page']);

        foreach ($dynamicFilters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Multi select
            if (is_array($value)) {
                $query->whereIn($field, $value);
                continue;
            }

            // Numeric fields (IDs, status etc.)
            if (is_numeric($value)) {
                $query->where($field, $value);
                continue;
            }

            // Text search
            $query->where($field, 'like', "%{$value}%");
        }

        return $query;
    }
}
