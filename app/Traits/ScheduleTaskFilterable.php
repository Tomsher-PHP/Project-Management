<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScheduleTaskFilterable
{
    public function scopeFilter(Builder $query, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $this->applyScheduleTaskSearch(
                $query,
                $search,
                (string) ($filters['search_condition'] ?? 'contains')
            );
        }

        foreach ($this->scheduleTaskFilterableColumns() as $column) {
            $values = $this->normalizeScheduleTaskFilterValues($filters[$column] ?? null);

            if ($values === []) {
                continue;
            }

            $query->whereIn($column, $values);
        }

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('end_date', '<=', $endDate);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = $filters['status'];
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    protected function scheduleTaskFilterableColumns(): array
    {
        return [
            'project_id',
            'current_assignee_id',
        ];
    }

    protected function applyScheduleTaskSearch(Builder $query, string $search, string $condition): void
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

    protected function normalizeScheduleTaskFilterValues(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($item) => filled($item))
            ->values()
            ->all();
    }
}
