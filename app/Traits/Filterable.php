<?php

namespace App\Traits;

trait Filterable
{
    public function scopeFilter($query, $filters)
    {

        if (!empty($filters['search'])) {
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

        if (isset($filters['parent_id']) && $filters['parent_id'] !== '') {
            $query->whereIn('parent_id', (array)$filters['parent_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
