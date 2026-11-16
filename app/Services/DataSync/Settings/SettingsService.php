<?php

namespace App\Services\DataSync\Settings;

use App\Models\Customer;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTechnology;
use App\Models\Technology;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SettingsService
{
    /**
     * Common sortable columns across all settings master modules.
     *
     * @var list<string>
     */
    protected const BASE_SORT_COLUMNS = [
        'id',
        'name',
        'code',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        protected ModuleResolver $moduleResolver
    ) {}

    /**
     * Return the explicit allowlist of sortable columns for a module.
     *
     * @return list<string>
     */
    public function allowedSortColumns(string $module): array
    {
        $modelClass = $this->moduleResolver->resolve($module);

        if ($modelClass === ProjectStatus::class) {
            return array_merge(self::BASE_SORT_COLUMNS, ['type', 'is_default', 'is_completed']);
        }

        return self::BASE_SORT_COLUMNS;
    }

    public function list(string $module, array $filters = []): LengthAwarePaginator
    {
        $modelClass = $this->moduleResolver->resolve($module);
        $query = $modelClass::query();

        if ($modelClass === Industry::class) {
            $query->with('parent:id,code');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) (int) $filters['is_active']);
        }

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $allowedSorts = $this->allowedSortColumns($module);
        $sortBy = isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSorts, true)
            ? $filters['sort_by']
            : 'sort_order';

        $sortDirection = isset($filters['sort_direction']) && in_array(strtolower((string) $filters['sort_direction']), ['asc', 'desc'], true)
            ? strtolower((string) $filters['sort_direction'])
            : 'asc';

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 50;
        $perPage = max(1, min(100, $perPage));
        $page = isset($filters['page']) ? max(1, (int) $filters['page']) : 1;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function find(string $module, string $code): Model
    {
        $modelClass = $this->moduleResolver->resolve($module);
        $query = $modelClass::query();

        if ($modelClass === Industry::class) {
            $query->with('parent:id,code');
        }

        $record = $query->where('code', $code)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel($modelClass, [$code]);
        }

        return $record;
    }

    /**
     * Create a new settings master record for the given module.
     *
     * @throws ValidationException
     * @throws ConflictHttpException
     */
    public function create(string $module, array $data): Model
    {
        $modelClass = $this->moduleResolver->resolve($module);

        if (array_key_exists('code', $data) && $data['code'] !== null && $data['code'] !== '') {
            $suppliedCode = (string) $data['code'];
            if (preg_match('/^\d{6}$/', $suppliedCode) === 1
                && $modelClass::withTrashed()->where('code', $suppliedCode)->exists()
            ) {
                throw new ConflictHttpException('A record with this code already exists.');
            }
        }

        $validated = $this->validateModulePayload($module, $modelClass, $data);

        $payload = [
            'name' => $validated['name'],
            'code' => $validated['code'] ?? $modelClass::generateUniqueCode(),
            'sort_order' => array_key_exists('sort_order', $validated)
                ? (int) $validated['sort_order']
                : ((int) $modelClass::max('sort_order')) + 1,
            'is_system' => false,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : true,
        ];

        if ($modelClass === Industry::class) {
            $payload['parent_id'] = $this->resolveIndustryParentId($validated['parent_code'] ?? null);

            $industry = Industry::create($payload);

            return $industry->load('parent:id,code');
        }

        if ($modelClass === ProjectStatus::class) {
            $isDefault = (bool) ($validated['is_default'] ?? false);
            $payload['color'] = $validated['color'] ?? null;
            $payload['type'] = $validated['type'];
            $payload['is_default'] = $isDefault;
            $payload['is_completed'] = $validated['type'] === ProjectStatus::TYPE_COMPLETED;

            return (new ProjectStatus())->getConnection()->transaction(function () use ($payload, $isDefault) {
                if ($isDefault) {
                    $this->clearExistingDefaultProjectStatuses();
                }

                return ProjectStatus::create($payload);
            });
        }

        return $modelClass::create($payload);
    }

    /**
     * Update an existing settings master record identified by its unique code.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function update(string $module, string $code, array $data): Model
    {
        $modelClass = $this->moduleResolver->resolve($module);
        $record = $modelClass::query()->where('code', $code)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel($modelClass, [$code]);
        }

        if (array_key_exists('code', $data) && (string) $data['code'] !== (string) $record->code) {
            throw ValidationException::withMessages([
                'code' => ['The code field cannot be modified.'],
            ]);
        }

        $validated = $this->validateModulePayload($module, $modelClass, $data, $record);

        $payload = [];
        foreach (['name', 'sort_order', 'is_active'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if ($modelClass === Industry::class) {
            if (array_key_exists('parent_code', $validated)) {
                $payload['parent_id'] = $this->resolveIndustryParentId($validated['parent_code'], $record);
            }

            $record->update($payload);

            return $record->refresh()->load('parent:id,code');
        }

        if ($modelClass === ProjectStatus::class) {
            if (array_key_exists('color', $validated)) {
                $payload['color'] = $validated['color'];
            }

            if (array_key_exists('type', $validated)) {
                $payload['type'] = $validated['type'];
            }

            $effectiveType = $payload['type'] ?? $record->type;
            $payload['is_completed'] = $effectiveType === ProjectStatus::TYPE_COMPLETED;

            if (array_key_exists('is_default', $validated)) {
                $payload['is_default'] = (bool) $validated['is_default'];
            }

            return $record->getConnection()->transaction(function () use ($record, $payload) {
                if (($payload['is_default'] ?? false) === true) {
                    $this->clearExistingDefaultProjectStatuses($record->id);
                }

                $record->update($payload);

                return $record->refresh();
            });
        }

        $record->update($payload);

        return $record->refresh();
    }

    /**
     * Soft-delete a settings master record identified by its unique code.
     *
     * @return array{already_deleted: bool}
     *
     * @throws ModelNotFoundException
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    public function delete(string $module, string $code): array
    {
        $modelClass = $this->moduleResolver->resolve($module);

        return (new $modelClass())->getConnection()->transaction(function () use ($modelClass, $code): array {
            $record = $modelClass::withTrashed()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw (new ModelNotFoundException())->setModel($modelClass, [$code]);
            }

            if ((bool) $record->is_system) {
                throw new AccessDeniedHttpException('System records cannot be deleted.');
            }

            if (method_exists($record, 'trashed') && $record->trashed()) {
                return ['already_deleted' => true];
            }

            $this->ensureDeletable($modelClass, $record);

            $record->delete();

            return ['already_deleted' => false];
        });
    }

    /**
     * Ensure the record is not protected by default status or active module references.
     *
     * @param  class-string<Model>  $modelClass
     *
     * @throws ConflictHttpException
     */
    protected function ensureDeletable(string $modelClass, Model $record): void
    {
        if ($modelClass === Technology::class) {
            if (ProjectTechnology::query()->where('technology_id', $record->id)->exists()) {
                throw new ConflictHttpException('This record is currently in use and cannot be deleted.');
            }

            return;
        }

        if ($modelClass === Industry::class) {
            $hasChildren = Industry::query()->where('parent_id', $record->id)->exists();
            $hasCustomers = Customer::withTrashed()->where('industry_id', $record->id)->exists();

            if ($hasChildren || $hasCustomers) {
                throw new ConflictHttpException('This record is currently in use and cannot be deleted.');
            }

            return;
        }

        if ($modelClass === ProjectCategory::class) {
            $categoryId = (int) $record->id;
            $inUse = Project::withTrashed()
                ->where(function ($query) use ($categoryId) {
                    $query->whereJsonContains('project_category_ids', $categoryId)
                        ->orWhereJsonContains('project_category_ids', (string) $categoryId);
                })
                ->exists();

            if ($inUse) {
                throw new ConflictHttpException('This record is currently in use and cannot be deleted.');
            }

            return;
        }

        if ($modelClass === ProjectStatus::class) {
            if ((bool) $record->is_default) {
                throw new ConflictHttpException('Default project status cannot be deleted.');
            }

            $usedByProjects = Project::withTrashed()->where('status_id', $record->id)->exists();
            $usedByHistory = ProjectStatusHistory::query()
                ->where('status_id', $record->id)
                ->orWhere('from_status_id', $record->id)
                ->exists();

            if ($usedByProjects || $usedByHistory) {
                throw new ConflictHttpException('This record is currently in use and cannot be deleted.');
            }
        }
    }

    /**
     * Validate incoming create or update payload using module-specific rules.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validateModulePayload(string $module, string $modelClass, array $data, ?Model $existing = null): array
    {
        $isCreate = $existing === null;
        $table = (new $modelClass())->getTable();

        $nameUniqueRule = Rule::unique($table, 'name')->whereNull('deleted_at');
        if ($existing !== null) {
            $nameUniqueRule->ignore($existing->id);
        }

        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255', $nameUniqueRule],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:127'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($isCreate) {
            $rules['code'] = ['sometimes', 'nullable', 'digits:6'];
        }

        if ($module === 'industries') {
            $rules['parent_code'] = ['sometimes', 'nullable', 'string', 'digits:6'];
        }

        if ($module === 'project_statuses') {
            $statusTypes = array_keys(config('project_constants.project_status_types', []));
            $rules['color'] = ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];
            $rules['type'] = [$isCreate ? 'required' : 'sometimes', 'string', Rule::in($statusTypes)];
            $rules['is_default'] = ['sometimes', 'boolean'];
        }

        return Validator::make($data, $rules)->validate();
    }

    /**
     * Resolve an industry parent_code to its local parent_id and reject self/circular chains.
     *
     * @throws ValidationException
     */
    protected function resolveIndustryParentId(?string $parentCode, ?Industry $current = null): ?int
    {
        if ($parentCode === null || $parentCode === '') {
            return null;
        }

        $parent = Industry::query()->where('code', $parentCode)->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_code' => ['The selected parent industry code is invalid.'],
            ]);
        }

        if ($current !== null) {
            if ((int) $parent->id === (int) $current->id) {
                throw ValidationException::withMessages([
                    'parent_code' => ['An industry cannot be its own parent.'],
                ]);
            }

            $visited = [$current->id => true];
            $cursor = $parent;
            while ($cursor !== null && $cursor->parent_id !== null) {
                if ((int) $cursor->parent_id === (int) $current->id) {
                    throw ValidationException::withMessages([
                        'parent_code' => ['Circular parent industry relationship is not allowed.'],
                    ]);
                }

                if (isset($visited[$cursor->parent_id])) {
                    break;
                }

                $visited[$cursor->parent_id] = true;
                $cursor = Industry::query()->select(['id', 'parent_id'])->find($cursor->parent_id);
            }
        }

        return (int) $parent->id;
    }

    protected function clearExistingDefaultProjectStatuses(?int $exceptId = null): void
    {
        $query = ProjectStatus::query()->where('is_default', true);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->lockForUpdate()->update([
            'is_default' => false,
        ]);
    }
}
