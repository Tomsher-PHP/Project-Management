<?php

namespace App\Services\DataSync\Settings;

use App\Models\Industry;
use App\Models\ProjectCategory;
use App\Models\ProjectStatus;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ModuleResolver
{
    /**
     * Explicit allowlist mapping supported module slugs to Eloquent model classes.
     *
     * @var array<string, class-string<Model>>
     */
    protected const MODEL_MAP = [
        'technologies' => Technology::class,
        'industries' => Industry::class,
        'project_categories' => ProjectCategory::class,
        'project_statuses' => ProjectStatus::class,
    ];

    /**
     * Determine whether the given module slug is configured and supported.
     */
    public function isValid(string $module): bool
    {
        return $this->isConfigured($module)
            && array_key_exists($module, self::MODEL_MAP);
    }

    /**
     * Resolve a validated module slug to its corresponding Eloquent model class.
     *
     * @return class-string<Model>
     *
     * @throws InvalidArgumentException
     */
    public function resolve(string $module): string
    {
        if (! $this->isValid($module)) {
            throw new InvalidArgumentException("Unsupported Data Sync module [{$module}].");
        }

        return self::MODEL_MAP[$module];
    }

    /**
     * Return all supported module slugs currently enabled in configuration.
     *
     * @return list<string>
     */
    public function supportedModules(): array
    {
        return array_values(array_filter(
            array_keys(self::MODEL_MAP),
            fn (string $slug): bool => $this->isConfigured($slug)
        ));
    }

    /**
     * Determine whether the module slug exists in configuration.
     */
    protected function isConfigured(string $module): bool
    {
        $configured = $this->configuredModules();

        return array_key_exists($module, $configured) || in_array($module, $configured, true);
    }

    /**
     * @return array<string, string>
     */
    protected function configuredModules(): array
    {
        $modules = config('constants.data_sync_modules', []);

        return is_array($modules) ? $modules : [];
    }
}

