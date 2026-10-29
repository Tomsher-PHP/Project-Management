<?php

namespace App\Services;

use App\Models\User;

class ProjectImportService
{
    /**
     * Retrieve all configured import modules from project_constants.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllModules(): array
    {
        return config('project_constants.project_import_modules', []);
    }

    /**
     * Retrieve all enabled import modules.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEnabledModules(): array
    {
        return array_filter($this->getAllModules(), function (array $module) {
            return !empty($module['enabled']);
        });
    }

    /**
     * Retrieve enabled import modules that the user is authorized to access.
     *
     * @param  \App\Models\User|null  $user
     * @return array<string, array<string, mixed>>
     */
    public function getAccessibleModules(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $enabled = $this->getEnabledModules();

        if (!$user) {
            return [];
        }

        return array_filter($enabled, function (array $module) use ($user) {
            if (empty($module['permission'])) {
                return true;
            }

            return $user->can($module['permission']);
        });
    }

    /**
     * Check if the user has access to at least one import module.
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function hasAnyAccessibleModule(?User $user = null): bool
    {
        return count($this->getAccessibleModules($user)) > 0;
    }

    /**
     * Resolve a module definition by key if enabled and accessible to the user.
     *
     * @param  string|null  $moduleKey
     * @param  \App\Models\User|null  $user
     * @return array<string, mixed>|null
     */
    public function resolveModule(?string $moduleKey, ?User $user = null): ?array
    {
        if (empty($moduleKey)) {
            return null;
        }

        $accessibleModules = $this->getAccessibleModules($user);

        if (!isset($accessibleModules[$moduleKey])) {
            return null;
        }

        $module = $accessibleModules[$moduleKey];
        $module['key'] = $moduleKey;
        $module['has_template'] = $this->hasTemplate($moduleKey, $user);
        $module['template_download_name'] = $this->getTemplateDownloadFilename($moduleKey, $user);

        return $module;
    }

    /**
     * Get the default module key for the user (the first accessible module).
     *
     * @param  \App\Models\User|null  $user
     * @return string|null
     */
    public function getDefaultModuleKey(?User $user = null): ?string
    {
        $keys = array_keys($this->getAccessibleModules($user));

        return $keys[0] ?? null;
    }

    /**
     * Resolve the verified absolute path of a module's import template file.
     * Ensures the file exists within public_path and prevents directory traversal.
     *
     * @param  string|null  $moduleKey
     * @param  \App\Models\User|null  $user
     * @return string|null
     */
    public function getTemplateAbsolutePath(?string $moduleKey, ?User $user = null): ?string
    {
        if (empty($moduleKey)) {
            return null;
        }

        $accessibleModules = $this->getAccessibleModules($user);
        if (!isset($accessibleModules[$moduleKey])) {
            return null;
        }

        $module = $accessibleModules[$moduleKey];
        $relativePath = $module['template_path'] ?? null;

        if (empty($relativePath)) {
            return null;
        }

        $targetPath = public_path($relativePath);
        $realPath = realpath($targetPath);
        $realPublic = realpath(public_path());

        if (!$realPath || !$realPublic) {
            return null;
        }

        if (!str_starts_with($realPath, $realPublic) || !is_file($realPath)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Get the user-friendly download filename for a module template.
     *
     * @param  string|null  $moduleKey
     * @param  \App\Models\User|null  $user
     * @return string|null
     */
    public function getTemplateDownloadFilename(?string $moduleKey, ?User $user = null): ?string
    {
        if (empty($moduleKey)) {
            return null;
        }

        $accessibleModules = $this->getAccessibleModules($user);
        if (!isset($accessibleModules[$moduleKey])) {
            return null;
        }

        $module = $accessibleModules[$moduleKey];

        return $module['template_filename'] ?? (isset($module['template_path']) ? basename($module['template_path']) : null);
    }

    /**
     * Check if a valid import template exists for the specified module.
     *
     * @param  string|null  $moduleKey
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function hasTemplate(?string $moduleKey, ?User $user = null): bool
    {
        return $this->getTemplateAbsolutePath($moduleKey, $user) !== null;
    }

    /**
     * Resolve the module-specific import handler service instance.
     *
     * @param  string  $moduleKey
     * @return \App\Services\Contracts\ProjectImportHandlerInterface
     *
     * @throws \InvalidArgumentException
     */
    public function resolveImportHandler(string $moduleKey): \App\Services\Contracts\ProjectImportHandlerInterface
    {
        $enabled = $this->getEnabledModules();

        if (!isset($enabled[$moduleKey])) {
            throw new \InvalidArgumentException("Import module '{$moduleKey}' is not recognized or is disabled.");
        }

        $handlerClass = $enabled[$moduleKey]['handler_class'] ?? null;

        if (!$handlerClass || !class_exists($handlerClass)) {
            // Robust convention fallback: e.g. project_hosting -> App\Services\ProjectHostingImportService
            $studlyName = str_replace(' ', '', ucwords(str_replace('_', ' ', $moduleKey)));
            $fallbackClass = "App\\Services\\{$studlyName}ImportService";

            if (class_exists($fallbackClass)) {
                $handlerClass = $fallbackClass;
            } else {
                throw new \InvalidArgumentException("No valid import handler class found for module '{$moduleKey}'.");
            }
        }

        $handler = app($handlerClass);

        if (!$handler instanceof \App\Services\Contracts\ProjectImportHandlerInterface) {
            throw new \InvalidArgumentException("Handler class '{$handlerClass}' must implement ProjectImportHandlerInterface.");
        }

        return $handler;
    }

    /**
     * Create an import-tracking record.
     *
     * @param  string  $moduleKey
     * @param  string  $fileName
     * @param  string  $filePath
     * @param  int  $userId
     * @return \App\Models\ProjectImport
     */
    public function createImportRecord(string $moduleKey, string $fileName, string $filePath, int $userId): \App\Models\ProjectImport
    {
        return \App\Models\ProjectImport::create([
            'module_key' => $moduleKey,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => \App\Models\ProjectImport::STATUS_PENDING,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Mark an import as failed with an error message.
     *
     * @param  int  $importId
     * @param  string  $errorMessage
     * @return void
     */
    public function markImportFailed(int $importId, string $errorMessage): void
    {
        $import = \App\Models\ProjectImport::find($importId);

        if ($import) {
            $import->update([
                'status' => \App\Models\ProjectImport::STATUS_FAILED,
                'error_message' => $errorMessage,
                'failed_at' => now(),
            ]);
        }
    }

    /**
     * Safely delete the stored temporary import file.
     *
     * @param  int  $importId
     * @return void
     */
    public function deleteImportFile(int $importId): void
    {
        $import = \App\Models\ProjectImport::find($importId);

        if ($import && $import->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($import->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($import->file_path);
        }
    }
}
