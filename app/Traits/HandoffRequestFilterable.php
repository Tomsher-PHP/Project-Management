<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait HandoffRequestFilterable
{
    public function scopeFilter(Builder $query, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $this->applyHandoffSearch(
                $query,
                $search,
                (string) ($filters['search_condition'] ?? 'contains')
            );
        }

        foreach ($this->handoffFilterableColumns() as $column) {
            $values = $this->normalizeHandoffFilterValues($filters[$column] ?? null);

            if ($values === []) {
                continue;
            }

            $query->whereIn($column, $values);
        }

        if (!empty($filters['date_range'])) {
            $dates = explode(' to ', $filters['date_range']);
            if (count($dates) === 2) {
                try {
                    $start = Carbon::parse($dates[0])->startOfDay();
                    $end = Carbon::parse($dates[1])->endOfDay();
                    $query->whereBetween('created_at', [$start, $end]);
                } catch (\Exception $e) {
                    // Ignore invalid dates
                }
            } elseif (count($dates) === 1) {
                try {
                    $start = Carbon::parse($dates[0])->startOfDay();
                    $end = Carbon::parse($dates[0])->endOfDay();
                    $query->whereBetween('created_at', [$start, $end]);
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        return $query;
    }

    protected function handoffFilterableColumns(): array
    {
        return [
            'user_id',
            'project_id',
            'project_milestone_id',
            'project_sprint_id',
            'purpose',
            'status',
        ];
    }

    protected function applyHandoffSearch(Builder $query, string $search, string $condition): void
    {
        $columns = $this->searchable ?? ['description', 'purpose'];

        $query->where(function (Builder $builder) use ($columns, $search, $condition) {
            foreach ((array) $columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                match ($condition) {
                    'starts_with' => $builder->{$method}($column, 'like', $search . '%'),
                    'ends_with' => $builder->{$method}($column, 'like', '%' . $search),
                    'not_contains' => $builder->where($column, 'not like', '%' . $search . '%'),
                    default => $builder->{$method}($column, 'like', '%' . $search . '%'),
                };
            }

            $builder->orWhereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
            $builder->orWhereHas('project', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
            $builder->orWhereHas('sourceTask', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        });
    }

    protected function normalizeHandoffFilterValues(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn($item) => filled($item))
            ->values()
            ->all();
    }
}
