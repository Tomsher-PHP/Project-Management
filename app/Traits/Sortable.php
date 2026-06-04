<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Sortable
{
    public function scopeSort($query, $request)
    {
        $model = $query->getModel();
        $sortBy = isset($request['sort_by']) ? $request['sort_by'] : null;
        $sortDir = isset($request['sort_dir']) ? $request['sort_dir'] : 'asc';

        // Allow only asc/desc
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        // Allowed columns (define in model)
        if (!$sortBy || !in_array($sortBy, $this->sortable ?? [])) {
            return $query->latest();
        }

        if (!str_contains($sortBy, '.')) {
            return $query->orderBy($sortBy, $sortDir);
        }

        [$relationName, $column] = explode('.', $sortBy, 2);

        if (!method_exists($model, $relationName)) {
            return $query->latest();
        }

        $relation = $model->{$relationName}();

        if (! $relation instanceof BelongsTo) {
            return $query->latest();
        }

        $relatedQuery = $relation->getRelated()
            ->newQuery()
            ->select($column)
            ->whereColumn(
                $relation->getQualifiedOwnerKeyName(),
                $relation->getQualifiedForeignKeyName()
            );

        return $query->orderBy($relatedQuery, $sortDir);
    }
}
