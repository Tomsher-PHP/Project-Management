<?php

namespace App\Traits;

trait HasFormOptions
{
    /**
     * Scope a query to include options for form select fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $selected
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForForm($query, $selected = null, $options = [])
    {
        $nameColumn = $options['order_by'] ?? 'name';
        $direction  = $options['direction'] ?? 'asc';

        // Normalize selected → always array
        $selectedIds = collect($selected)
            ->filter()
            ->flatten()
            ->unique()
            ->values();

        return $query->withTrashed()
            ->where(function ($q) use ($selectedIds) {

                // Active + not deleted
                $q->where(function ($q) {
                    if (method_exists($this, 'scopeActive')) {
                        $q->active();
                    }
                    $q->whereNull('deleted_at');
                });

                // Include selected (single or multiple)
                if ($selectedIds->isNotEmpty()) {
                    $q->orWhereIn('id', $selectedIds);
                }
            })
            ->orderBy($nameColumn, $direction);
    }
}
