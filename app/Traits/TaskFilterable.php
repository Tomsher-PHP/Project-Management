<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TaskFilterable
{
    public function scopeFilter(Builder $query, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $this->applyTaskSearch(
                $query,
                $search,
                (string) ($filters['search_condition'] ?? 'contains')
            );
        }

        foreach ($this->taskFilterableColumns() as $column) {
            $values = $this->normalizeTaskFilterValues($filters[$column] ?? null);

            if ($values === []) {
                continue;
            }

            $query->whereIn($column, $values);
        }

        return $query;
    }

    protected function taskFilterableColumns(): array
    {
        return [
            'project_id',
            'project_milestone_id',
            'project_sprint_id',
            'current_assignee_id',
            'status_id',
            'priority',
            'task_type_id',
            'task_mode_id',
        ];
    }

    protected function applyTaskSearch(Builder $query, string $search, string $condition): void
    {
        $columns = $this->searchable ?? ['name'];

        $query->where(function (Builder $builder) use ($columns, $search, $condition) {
            foreach ((array) $columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                match ($condition) {
                    'starts_with' => $builder->{$method}($column, 'like', $search.'%'),
                    'ends_with' => $builder->{$method}($column, 'like', '%'.$search),
                    'not_contains' => $builder->where($column, 'not like', '%'.$search.'%'),
                    default => $builder->{$method}($column, 'like', '%'.$search.'%'),
                };
            }
        });
    }

    protected function normalizeTaskFilterValues(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($item) => filled($item))
            ->values()
            ->all();
    }
}
