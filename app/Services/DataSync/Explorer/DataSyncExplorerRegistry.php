<?php

namespace App\Services\DataSync\Explorer;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

class DataSyncExplorerRegistry
{
    /**
     * Resolved examples cached for the lifetime of this Registry instance.
     *
     * @var array<string, array|stdClass>
     */
    private array $exampleCache = [];

    private ?string $resolvedExampleBasePath = null;

    public function __construct(
        private readonly ?string $exampleBasePath = null
    ) {}

    /**
     * Get the configured API directions.
     *
     * @return array<string, array>
     */
    public function getDirections(): array
    {
        $directions = config('data_sync_explorer.directions', []);

        return is_array($directions) ? $directions : [];
    }

    /**
     * Get the direction used by the current Explorer.
     */
    public function getDefaultDirection(): string
    {
        $default = config('data_sync_explorer.metadata.default_direction', 'inbound');

        if (is_string($default) && $this->isDirectionEnabled($default)) {
            return $default;
        }

        foreach ($this->getDirections() as $direction => $configuration) {
            if (is_string($direction)
                && is_array($configuration)
                && ($configuration['enabled'] ?? false) === true) {
                return $direction;
            }
        }

        return is_string($default) && $default !== '' ? $default : 'inbound';
    }

    /**
     * Determine whether a direction is configured and enabled.
     */
    public function isDirectionEnabled(string $direction): bool
    {
        $configuration = $this->getDirections()[$direction] ?? null;

        return is_array($configuration) && ($configuration['enabled'] ?? false) === true;
    }

    /**
     * Get all categories defined in the configuration.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public function getCategories(): array
    {
        $categories = config('data_sync_explorer.categories', []);

        return is_array($categories) ? $categories : [];
    }

    /**
     * Get all modules for an enabled API direction.
     *
     * @return array<string, array>
     */
    public function getModules(?string $direction = null): array
    {
        $direction ??= $this->getDefaultDirection();

        if (! $this->isDirectionEnabled($direction)) {
            return [];
        }

        $configuration = $this->getDirections()[$direction] ?? [];
        $configuredModules = $configuration['modules'] ?? [];

        if (! is_array($configuredModules)) {
            return [];
        }

        $moduleOrder = $configuration['module_order'] ?? [];
        $orderedModules = [];

        if (is_array($moduleOrder)) {
            foreach ($moduleOrder as $moduleKey) {
                if (! is_string($moduleKey) || ! isset($configuredModules[$moduleKey]) || ! is_array($configuredModules[$moduleKey])) {
                    continue;
                }

                $orderedModules[$moduleKey] = $this->resolveModuleProfiles($configuredModules[$moduleKey]);
            }
        }

        foreach ($configuredModules as $moduleKey => $module) {
            if (! is_string($moduleKey) || isset($orderedModules[$moduleKey]) || ! is_array($module)) {
                continue;
            }

            $orderedModules[$moduleKey] = $this->resolveModuleProfiles($module);
        }

        return $orderedModules;
    }

    /**
     * Get modules belonging to a specific category.
     *
     * @return array<string, array>
     */
    public function getModulesByCategory(string $category, ?string $direction = null): array
    {
        return array_filter(
            $this->getModules($direction),
            fn (array $module) => ($module['category'] ?? '') === $category
        );
    }

    /**
     * Get a single module by slug/key.
     */
    public function getModule(string $moduleKey, ?string $direction = null): ?array
    {
        return $this->getModules($direction)[$moduleKey] ?? null;
    }

    /**
     * Get a specific operation within a module.
     */
    public function getOperation(string $moduleKey, string $operationKey, ?string $direction = null): ?array
    {
        $module = $this->getModule($moduleKey, $direction);
        $operation = $module['operations'][$operationKey] ?? null;

        if (! $operation && $operationKey === 'show') {
            $operation = $module['operations']['get'] ?? null;
        } elseif (! $operation && $operationKey === 'get') {
            $operation = $module['operations']['show'] ?? null;
        }

        return is_array($operation) ? $operation : null;
    }

    /**
     * Build an executable cURL snippet with placeholder credentials.
     */
    public function buildCurlCommand(array $operation, string $baseUrl = ''): string
    {
        $method = strtoupper($operation['method'] ?? 'GET');
        $endpoint = $operation['endpoint'] ?? '';
        $baseUrl = rtrim($baseUrl ?: url('/'), '/');

        // Replace path param placeholders with examples if available
        $resolvedEndpoint = $endpoint;
        if (! empty($operation['path_params'])) {
            foreach ($operation['path_params'] as $param) {
                $name = $param['name'] ?? '';
                $example = $param['example'] ?? "{{$name}}";
                $resolvedEndpoint = str_replace("{{$name}}", $example, $resolvedEndpoint);
            }
        }

        $fullUrl = $baseUrl.'/'.ltrim($resolvedEndpoint, '/');

        // If GET/DELETE and has query params, append sample query string
        if (in_array($method, ['GET', 'DELETE']) && ! empty($operation['query_params'])) {
            $sampleParams = [];
            foreach ($operation['query_params'] as $param) {
                if (isset($param['default']) && $param['default'] !== 'null') {
                    $sampleParams[$param['name']] = $param['default'];
                }
            }
            if (! empty($sampleParams)) {
                $separator = str_contains($fullUrl, '?') ? '&' : '?';
                $fullUrl .= $separator.http_build_query($sampleParams);
            }
        }

        $lines = [];
        $lines[] = "curl --location --request {$method} '{$fullUrl}' \\";

        // Headers
        $headers = $operation['headers'] ?? [];
        foreach ($headers as $header) {
            $name = $header['name'] ?? '';
            $value = $header['value'] ?? '';
            $lines[] = "  --header '{$name}: {$value}' \\";
        }

        // Body
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && ! empty($operation['request_payload'])) {
            $jsonPayload = json_encode($operation['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = "  --data '{$jsonPayload}'";
        } else {
            // Trim trailing backslash from the last header line
            $lastIndex = count($lines) - 1;
            if ($lastIndex >= 0) {
                $lines[$lastIndex] = rtrim($lines[$lastIndex], ' \\');
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get the complete Explorer tree for an enabled API direction.
     */
    public function getAllData(?string $baseUrl = null, ?string $direction = null): array
    {
        $baseUrl = rtrim($baseUrl ?: url('/'), '/');
        $categories = $this->getCategories();
        $rawModules = $this->getModules($direction);

        $modules = [];
        foreach ($rawModules as $modKey => $module) {
            $processedOperations = [];
            $operations = $module['operations'] ?? [];

            if (! is_array($operations)) {
                $operations = [];
            }

            foreach ($operations as $opKey => $operation) {
                if (! is_array($operation)) {
                    continue;
                }

                $resolvedEndpoint = $operation['endpoint'] ?? '';
                if (! empty($operation['path_params'])) {
                    foreach ($operation['path_params'] as $param) {
                        $pName = $param['name'] ?? '';
                        $pExample = $param['example'] ?? "{{$pName}}";
                        $resolvedEndpoint = str_replace("{{$pName}}", $pExample, $resolvedEndpoint);
                    }
                }

                $operation['full_url'] = $baseUrl.'/'.ltrim($resolvedEndpoint, '/');
                $operation['curl_command'] = $this->buildCurlCommand($operation, $baseUrl);
                $operation['formatted_payload'] = ! empty($operation['request_payload'])
                    ? json_encode($operation['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : null;
                $operation['formatted_success_response'] = ! empty($operation['response_success']['body'])
                    ? json_encode($operation['response_success']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : null;
                $operation['formatted_updated_response'] = ! empty($operation['response_success']['updated_body'])
                    ? json_encode($operation['response_success']['updated_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : null;

                $formattedErrors = [];
                if (! empty($operation['response_errors'])) {
                    foreach ($operation['response_errors'] as $statusCode => $errorBody) {
                        $formattedErrors[$statusCode] = json_encode($errorBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    }
                }
                $operation['formatted_error_responses'] = $formattedErrors;

                $processedOperations[$opKey] = $operation;
            }

            $module['operations'] = $processedOperations;
            $modules[$modKey] = $module;
        }

        return [
            'base_url' => $baseUrl,
            'categories' => $categories,
            'modules' => $modules,
        ];
    }

    /**
     * Resolve shared header and error profiles into the legacy operation shape.
     */
    private function resolveModuleProfiles(array $module): array
    {
        $operations = $module['operations'] ?? [];

        if (! is_array($operations)) {
            $module['operations'] = [];

            return $module;
        }

        $resolvedOperations = [];
        foreach ($operations as $operationKey => $operation) {
            if (! is_array($operation)) {
                continue;
            }

            $resolvedOperations[$operationKey] = $this->resolveOperationProfiles($operation);
        }

        $module['operations'] = $resolvedOperations;

        return $module;
    }

    /**
     * Resolve profile references while preserving operation-specific overrides.
     */
    private function resolveOperationProfiles(array $operation): array
    {
        $resolved = [];
        $hasHeaderProfile = isset($operation['header_profile']);
        $hasErrorProfile = isset($operation['error_profile']);

        foreach ($operation as $key => $value) {
            if ($key === 'header_profile') {
                $profile = $this->getProfile('header_profiles', $value);
                $additionalHeaders = $operation['additional_headers'] ?? [];
                $resolved['headers'] = array_values(array_merge(
                    $profile,
                    is_array($additionalHeaders) ? $additionalHeaders : []
                ));

                continue;
            }

            if ($key === 'additional_headers' && $hasHeaderProfile) {
                continue;
            }

            if ($key === 'error_profile') {
                $profile = $this->getProfile('error_profiles', $value);
                $operationErrors = $operation['response_errors'] ?? [];
                $resolved['response_errors'] = array_replace(
                    $profile,
                    is_array($operationErrors) ? $operationErrors : []
                );

                continue;
            }

            if ($key === 'response_errors' && $hasErrorProfile) {
                continue;
            }

            $resolved[$key] = $value;
        }

        return $this->resolveOperationExamples($resolved);
    }

    /**
     * Read a shared profile defensively.
     *
     * @return array<int|string, mixed>
     */
    private function getProfile(string $profileGroup, mixed $profileName): array
    {
        if (! is_string($profileName) || $profileName === '') {
            return [];
        }

        $profile = config("data_sync_explorer.{$profileGroup}.{$profileName}", []);

        return is_array($profile) ? $profile : [];
    }

    /**
     * Resolve configured JSON references into the legacy operation shape.
     */
    private function resolveOperationExamples(array $operation): array
    {
        if (is_string($operation['request_payload'] ?? null)) {
            $operation['request_payload'] = $this->loadExample($operation['request_payload']);
        }

        if (is_array($operation['response_success'] ?? null)
            && is_string($operation['response_success']['body'] ?? null)) {
            $operation['response_success']['body'] = $this->loadExample(
                $operation['response_success']['body']
            );
        }

        if (is_array($operation['response_success'] ?? null)
            && is_string($operation['response_success']['updated_body'] ?? null)) {
            $operation['response_success']['updated_body'] = $this->loadExample(
                $operation['response_success']['updated_body']
            );
        }

        if (is_array($operation['response_errors'] ?? null)) {
            foreach ($operation['response_errors'] as $statusCode => $errorExample) {
                if (is_string($errorExample)) {
                    $operation['response_errors'][$statusCode] = $this->loadExample($errorExample);
                }
            }
        }

        return $operation;
    }

    /**
     * Load and validate one registered JSON example.
     */
    private function loadExample(string $reference): array|stdClass
    {
        $path = $this->resolveExamplePath($reference);

        if (array_key_exists($path, $this->exampleCache)) {
            return $this->exampleCache[$path];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read Data Sync Explorer JSON example [{$reference}].");
        }

        try {
            $decoded = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException(
                "Invalid JSON in Data Sync Explorer example [{$reference}]: {$exception->getMessage()}",
                previous: $exception
            );
        }

        if (! is_array($decoded) && ! $decoded instanceof stdClass) {
            throw new UnexpectedValueException(
                "Data Sync Explorer JSON example [{$reference}] must contain a JSON object or array."
            );
        }

        return $this->exampleCache[$path] = $this->normalizeDecodedExample($decoded);
    }

    /**
     * Resolve a registered reference inside the private example directory.
     */
    private function resolveExamplePath(string $reference): string
    {
        $normalizedReference = $this->normalizeRelativePath($reference, 'JSON example reference');
        if (strtolower(pathinfo($normalizedReference, PATHINFO_EXTENSION)) !== 'json') {
            throw new InvalidArgumentException(
                "Data Sync Explorer JSON example reference [{$reference}] must use the .json extension."
            );
        }

        $basePath = $this->resolveExampleBasePath();
        $candidate = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalizedReference);
        $resolvedPath = realpath($candidate);

        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            throw new RuntimeException("Data Sync Explorer JSON example [{$reference}] was not found.");
        }

        if (! str_starts_with($resolvedPath, $basePath.DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException(
                "Data Sync Explorer JSON example reference [{$reference}] resolves outside the allowed directory."
            );
        }

        if (! is_readable($resolvedPath)) {
            throw new RuntimeException("Data Sync Explorer JSON example [{$reference}] is not readable.");
        }

        return $resolvedPath;
    }

    /**
     * Resolve the application-controlled example base directory.
     */
    private function resolveExampleBasePath(): string
    {
        if ($this->resolvedExampleBasePath !== null) {
            return $this->resolvedExampleBasePath;
        }

        $configuredPath = $this->exampleBasePath;

        if ($configuredPath === null) {
            $resourcePath = app()->bound('path.resources')
                ? app('path.resources')
                : dirname(__DIR__, 4).'/resources';
            $exampleDirectory = config(
                'data_sync_explorer.metadata.example_directory',
                'data/data-sync-explorer/examples'
            );

            if (! is_string($exampleDirectory)) {
                throw new RuntimeException('The Data Sync Explorer JSON example directory configuration is invalid.');
            }

            $configuredPath = $resourcePath.DIRECTORY_SEPARATOR.str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $this->normalizeRelativePath($exampleDirectory, 'JSON example directory')
            );
        }

        $resolvedPath = realpath($configuredPath);

        if ($resolvedPath === false || ! is_dir($resolvedPath)) {
            throw new RuntimeException('The Data Sync Explorer JSON example directory does not exist.');
        }

        return $this->resolvedExampleBasePath = rtrim($resolvedPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Validate and normalize an application-controlled relative path.
     */
    private function normalizeRelativePath(string $path, string $description): string
    {
        if ($path === '' || str_contains($path, "\0")) {
            throw new InvalidArgumentException("The Data Sync Explorer {$description} is empty or invalid.");
        }

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            throw new InvalidArgumentException("The Data Sync Explorer {$description} must be relative.");
        }

        $segments = explode('/', $normalized);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidArgumentException(
                    "The Data Sync Explorer {$description} contains an invalid path segment."
                );
            }
        }

        return implode('/', $segments);
    }

    /**
     * Preserve empty JSON objects while retaining the existing associative shape.
     */
    private function normalizeDecodedExample(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $properties = get_object_vars($value);

            if ($properties === []) {
                return $value;
            }

            return array_map(
                fn (mixed $item): mixed => $this->normalizeDecodedExample($item),
                $properties
            );
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normalizeDecodedExample($item),
                $value
            );
        }

        return $value;
    }
}
