<?php

namespace App\Services\DataSync\Explorer;

class DataSyncExplorerRegistry
{
    /**
     * Get all categories defined in the configuration.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public function getCategories(): array
    {
        return config('data_sync_explorer.categories', []);
    }

    /**
     * Get all modules defined in the configuration.
     *
     * @return array<string, array>
     */
    public function getModules(): array
    {
        return config('data_sync_explorer.modules', []);
    }

    /**
     * Get modules belonging to a specific category.
     *
     * @param string $category
     * @return array<string, array>
     */
    public function getModulesByCategory(string $category): array
    {
        return array_filter(
            $this->getModules(),
            fn (array $module) => ($module['category'] ?? '') === $category
        );
    }

    /**
     * Get a single module by slug/key.
     *
     * @param string $moduleKey
     * @return array|null
     */
    public function getModule(string $moduleKey): ?array
    {
        return config("data_sync_explorer.modules.{$moduleKey}");
    }

    /**
     * Get a specific operation within a module.
     *
     * @param string $moduleKey
     * @param string $operationKey
     * @return array|null
     */
    public function getOperation(string $moduleKey, string $operationKey): ?array
    {
        return config("data_sync_explorer.modules.{$moduleKey}.operations.{$operationKey}");
    }

    /**
     * Build an executable cURL snippet with placeholder credentials.
     *
     * @param array $operation
     * @param string $baseUrl
     * @return string
     */
    public function buildCurlCommand(array $operation, string $baseUrl = ''): string
    {
        $method = strtoupper($operation['method'] ?? 'GET');
        $endpoint = $operation['endpoint'] ?? '';
        $baseUrl = rtrim($baseUrl ?: url('/'), '/');

        // Replace path param placeholders with examples if available
        $resolvedEndpoint = $endpoint;
        if (!empty($operation['path_params'])) {
            foreach ($operation['path_params'] as $param) {
                $name = $param['name'] ?? '';
                $example = $param['example'] ?? "{{$name}}";
                $resolvedEndpoint = str_replace("{{$name}}", $example, $resolvedEndpoint);
            }
        }

        $fullUrl = $baseUrl . '/' . ltrim($resolvedEndpoint, '/');

        // If GET and has query params, append sample query string
        if ($method === 'GET' && !empty($operation['query_params'])) {
            $sampleParams = [];
            foreach ($operation['query_params'] as $param) {
                if (isset($param['default']) && $param['default'] !== 'null') {
                    $sampleParams[$param['name']] = $param['default'];
                }
            }
            if (!empty($sampleParams)) {
                $fullUrl .= '?' . http_build_query($sampleParams);
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
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($operation['request_payload'])) {
            $jsonPayload = json_encode($operation['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = "  --data '{$jsonPayload}'";
        } else {
            // Trim trailing backslash from the last header line
            $lastIndex = count($lines) - 1;
            if ($lastIndex >= 0) {
                $lines[$lastIndex] = rtrim($lines[$lastIndex], " \\");
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get complete structured explorer tree with resolved full URLs and cURL snippets.
     *
     * @param string|null $baseUrl
     * @return array
     */
    public function getAllData(?string $baseUrl = null): array
    {
        $baseUrl = rtrim($baseUrl ?: url('/'), '/');
        $categories = $this->getCategories();
        $rawModules = $this->getModules();

        $modules = [];
        foreach ($rawModules as $modKey => $module) {
            $processedOperations = [];
            foreach ($module['operations'] as $opKey => $operation) {
                $resolvedEndpoint = $operation['endpoint'];
                if (!empty($operation['path_params'])) {
                    foreach ($operation['path_params'] as $param) {
                        $pName = $param['name'] ?? '';
                        $pExample = $param['example'] ?? "{{$pName}}";
                        $resolvedEndpoint = str_replace("{{$pName}}", $pExample, $resolvedEndpoint);
                    }
                }

                $operation['full_url'] = $baseUrl . '/' . ltrim($resolvedEndpoint, '/');
                $operation['curl_command'] = $this->buildCurlCommand($operation, $baseUrl);
                $operation['formatted_payload'] = !empty($operation['request_payload'])
                    ? json_encode($operation['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : null;
                $operation['formatted_success_response'] = !empty($operation['response_success']['body'])
                    ? json_encode($operation['response_success']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : null;

                $formattedErrors = [];
                if (!empty($operation['response_errors'])) {
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
}

