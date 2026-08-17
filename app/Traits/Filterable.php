<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait Filterable
{
    public function scopeFilter($query, $filters)
    {
        // 1. Handle search dynamically
        if (!empty($filters['search'])) {
            $condition = $filters['search_condition'] ?? 'contains';
            $columns = $this->searchable ?? ['name']; // default to 'name'

            $query->where(function ($q) use ($columns, $filters, $condition) {
                $search = $filters['search'];

                foreach ((array)$columns as $column) {
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

                if (method_exists($this, 'applyFilterSearchExtensions')) {
                    $this->applyFilterSearchExtensions($q, $search, $condition);
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

        // date range filter 
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $dateColumn = $filters['date_column'] ?? 'start_date';
            if (Schema::hasColumn($query->getModel()->getTable(), $dateColumn)) {
                $query->whereBetween($dateColumn, [
                    $filters['start_date'],
                    $filters['end_date']
                ]);
            }
        }

        // Project category filter
        if (!empty($filters['project_category_id'])) {

            $categoryIds = $filters['project_category_id'];

            // Convert to array if a single value is passed
            if (!is_array($categoryIds)) {
                $categoryIds = [$categoryIds];
            }

            // If only "Others" is selected
            if (in_array('others', $categoryIds, true) && count($categoryIds) === 1) {

                $query->whereNull('project_category_id');

            } else {

                // Check whether Others is selected along with categories
                $hasOthers = in_array('others', $categoryIds, true);

                // Remove "others"
                $categoryIds = array_values(
                    array_filter($categoryIds, fn ($id) => $id !== 'others')
                );

                $query->where(function ($q) use ($categoryIds, $hasOthers) {

                    // Selected categories
                    if (!empty($categoryIds)) {
                        $q->whereIn('project_category_id', $categoryIds);
                    }

                    // Others = NULL category
                    if ($hasOthers) {
                        $q->orWhereNull('project_category_id');
                    }
                });
            }
        }

        // 3. Handle dynamic filters
        $dynamicFilters = collect($filters)
            ->except(['search', 'search_condition', 'role_id', 'per_page', 'page', 'department_id', 'designation_id', 'user_id', 'start_date', 'end_date', 'date_column', 'project_category_id']);

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
