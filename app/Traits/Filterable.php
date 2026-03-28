<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait Filterable
{
    // public function scopeFilter($query, $filters)
    // {
    //     if (isset($filters['search']) && !empty($filters['search'])) {
    //         $condition = $filters['search_condition'] ?? 'contains';
    //         //switch case
    //         switch ($condition) {
    //             case 'all':
    //                 $query->where('name', 'like', '%' . $filters['search'] . '%');
    //                 break;
    //             case 'starts_with':
    //                 $query->where('name', 'like', $filters['search'] . '%');
    //                 break;
    //             case 'ends_with':
    //                 $query->where('name', 'like', '%' . $filters['search']);
    //                 break;
    //             case 'contains':
    //                 $query->where('name', 'like', '%' . $filters['search'] . '%');
    //                 break;
    //             case 'not_contains':
    //                 $query->where('name', 'not like', '%' . $filters['search'] . '%');
    //                 break;
    //         }
    //     }

    //     if (isset($filters['role_id']) && !empty($filters['role_id'])) {
    //         $roles = (array) $filters['role_id'];

    //         $query->whereHas('roles', function ($q) use ($roles) {
    //             $q->whereIn('roles.id', $roles);
    //         });
    //     }

    //     if (isset($filters['department_id']) && !empty($filters['department_id'])) {
    //         $departments = (array) $filters['department_id'];

    //         $query->whereHas('details', function ($q) use ($departments) {
    //             $q->whereIn('department_id', $departments);
    //         });
    //     }

    //     if (isset($filters['designation_id']) && !empty($filters['designation_id'])) {
    //         $designations = (array) $filters['designation_id'];

    //         $query->whereHas('details', function ($q) use ($designations) {
    //             $q->whereIn('designation_id', $designations);
    //         });
    //     }

    //     if (isset($filters['user_id']) && !empty($filters['user_id'])) {
    //         $users = (array) $filters['user_id'];

    //         $query->whereHas('users', function ($q) use ($users) {
    //             $q->whereIn('users.id', $users);
    //         });
    //     }

    //     // --- 3. Handle Dynamic / Module-Specific Filters ---
    //     $dynamicFilters = $filters;
    //     unset($dynamicFilters['search'], $dynamicFilters['search_condition'], $dynamicFilters['role_id'], $dynamicFilters['per_page'], $dynamicFilters['page'], $dynamicFilters['department_id'], $dynamicFilters['designation_id'], $dynamicFilters['user_id']);

    //     foreach ($dynamicFilters as $field => $value) {

    //         if (!Schema::hasColumn($query->getModel()->getTable(), $field)) {
    //             continue; // skip invalid columns
    //         }

    //         if ($value === null || $value === '') {
    //             continue;
    //         }

    //         // Multi select
    //         if (is_array($value)) {
    //             $query->whereIn($field, $value);
    //             continue;
    //         }

    //         // Numeric fields (IDs, status etc.)
    //         if (is_numeric($value)) {
    //             $query->where($field, $value);
    //             continue;
    //         }

    //         // Text search
    //         $query->where($field, 'like', "%{$value}%");
    //     }

    //     return $query;
    // }

    public function scopeFilter($query, $filters)
    {
        // 1. Handle search dynamically
        if (!empty($filters['search'])) {
            $condition = $filters['search_condition'] ?? 'contains';
            $columns = $this->searchable ?? ['name']; // default to 'name'

            $query->where(function ($q) use ($columns, $filters, $condition) {
                foreach ((array)$columns as $column) {
                    $search = $filters['search'];

                    switch ($condition) {
                        case 'starts_with':
                            $q->orWhere($column, 'like', $search . '%');
                            break;

                        case 'ends_with':
                            $q->orWhere($column, 'like', '%' . $search);
                            break;

                        case 'not_contains':
                            $q->orWhere($column, 'not like', '%' . $search . '%');
                            break;

                        default: // contains + all
                            $q->orWhere($column, 'like', '%' . $search . '%');
                            break;
                    }
                }
            });
        }

        // 2. Handle role, department, designation, user_id filters
        if (!empty($filters['role_id'])) {
            $roles = (array)$filters['role_id'];
            $query->whereHas('roles', fn($q) => $q->whereIn('roles.id', $roles));
        }

        if (!empty($filters['department_id'])) {
            $departments = (array)$filters['department_id'];
            $query->whereHas('details', fn($q) => $q->whereIn('department_id', $departments));
        }

        if (!empty($filters['designation_id'])) {
            $designations = (array)$filters['designation_id'];
            $query->whereHas('details', fn($q) => $q->whereIn('designation_id', $designations));
        }

        if (!empty($filters['user_id'])) {
            $users = (array)$filters['user_id'];
            $query->whereHas('users', fn($q) => $q->whereIn('users.id', $users));
        }

        // 3. Handle dynamic filters
        $dynamicFilters = collect($filters)
            ->except(['search', 'search_condition', 'role_id', 'per_page', 'page', 'department_id', 'designation_id', 'user_id']);

        foreach ($dynamicFilters as $field => $value) {

            if (!Schema::hasColumn($query->getModel()->getTable(), $field) || $value === null || $value === '') {
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
