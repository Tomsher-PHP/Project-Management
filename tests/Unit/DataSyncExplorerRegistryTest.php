<?php

namespace Tests\Unit;

use App\Models\ApiClient;
use App\Services\DataSync\Explorer\DataSyncExplorerRegistry;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

class DataSyncExplorerRegistryTest extends TestCase
{
    private Container $container;

    private Repository $config;

    private DataSyncExplorerRegistry $registry;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    /**
     * @var list<string>
     */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        Container::setInstance($this->container);

        $this->config = new Repository([
            'data_sync_explorer' => require dirname(__DIR__, 2).'/config/data_sync_explorer.php',
        ]);
        $this->container->instance('config', $this->config);
        $this->registry = new DataSyncExplorerRegistry;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryFiles) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach (array_reverse($this->temporaryDirectories) as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }

        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_directions_are_registered_without_enabling_outbound_modules(): void
    {
        $directions = $this->registry->getDirections();

        $this->assertSame('inbound', $this->registry->getDefaultDirection());
        $this->assertTrue($directions['inbound']['enabled']);
        $this->assertFalse($directions['outbound']['enabled']);
        $this->assertSame([], $this->registry->getModules('outbound'));
        $this->assertSame([], $this->registry->getAllData('https://pms.example', 'outbound')['modules']);
    }

    public function test_inbound_module_and_operation_order_is_preserved(): void
    {
        $modules = $this->registry->getModules();
        $expectedModules = [
            'technologies',
            'industries',
            'project_categories',
            'project_statuses',
            'projects',
            'customers',
            'meetings',
        ];

        $this->assertSame(['settings', 'operations'], array_keys($this->registry->getCategories()));
        $this->assertSame($expectedModules, array_keys($modules));
        $this->assertSame($expectedModules, $this->config->get('data_sync_explorer.directions.inbound.module_order'));
        $this->assertSame(20, array_sum(array_map(
            fn (array $module): int => count($module['operations']),
            $modules
        )));
        $this->assertSame(7, array_sum(array_map(
            fn (array $module): int => count(array_filter(
                $module['operations'],
                fn (array $operation): bool => ! empty($operation['request_payload'])
            )),
            $modules
        )));
    }

    public function test_shared_profiles_resolve_to_the_legacy_operation_shape(): void
    {
        $modules = $this->registry->getModules();
        $profileReferenceCount = 0;

        foreach ($this->config->get('data_sync_explorer.directions.inbound.modules') as $module) {
            foreach ($module['operations'] as $operation) {
                $profileReferenceCount += isset($operation['header_profile']) ? 1 : 0;
                $profileReferenceCount += isset($operation['error_profile']) ? 1 : 0;
            }
        }

        $this->assertGreaterThan(0, $profileReferenceCount);

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $this->assertArrayHasKey('headers', $operation);
                $this->assertArrayHasKey('response_errors', $operation);
                $this->assertArrayNotHasKey('header_profile', $operation);
                $this->assertArrayNotHasKey('additional_headers', $operation);
                $this->assertArrayNotHasKey('error_profile', $operation);
            }
        }

        $technologySync = $modules['technologies']['operations']['sync'];
        $this->assertSame(ApiClient::SCOPE_SETTINGS_WRITE, $technologySync['scope']);
        $this->assertSame(
            ['Authorization', 'Accept', 'Content-Type', 'Idempotency-Key'],
            array_column($technologySync['headers'], 'name')
        );
        $this->assertSame([401, 403, 409, 422], array_keys($technologySync['response_errors']));
    }

    public function test_current_explorer_output_remains_direction_agnostic(): void
    {
        $data = $this->registry->getAllData('https://pms.example');

        $this->assertSame(['base_url', 'categories', 'modules'], array_keys($data));
        $this->assertArrayNotHasKey('directions', $data);
        $this->assertSame('https://pms.example', $data['base_url']);
        $this->assertSame('https://pms.example/api/data-sync/settings?module=technologies', $data['modules']['technologies']['operations']['list']['full_url']);
        $this->assertNotEmpty($data['modules']['technologies']['operations']['list']['curl_command']);
        $this->assertNotEmpty($data['modules']['technologies']['operations']['list']['formatted_success_response']);
        $this->assertSame(
            '48581ca763eb7297f5f25840496f41198ede7155d65f87ef6501e1dffa1d9063',
            hash('sha256', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))
        );
    }

    public function test_invalid_direction_configuration_fails_safely(): void
    {
        $this->config->set('data_sync_explorer.directions.inbound.modules', 'invalid');

        $this->assertSame([], $this->registry->getModules('inbound'));
        $this->assertNull($this->registry->getModule('technologies', 'inbound'));
        $this->assertNull($this->registry->getOperation('technologies', 'list', 'inbound'));
    }

    public function test_every_registered_example_reference_exists_and_contains_valid_json(): void
    {
        $configuration = $this->config->get('data_sync_explorer');
        $references = [];
        $this->collectExampleReferences($configuration, $references);
        $references = array_values(array_unique($references));
        sort($references);

        $basePath = dirname(__DIR__, 2).'/resources/data/data-sync-explorer/examples';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $files[] = str_replace(
                    '\\',
                    '/',
                    substr($file->getPathname(), strlen($basePath) + 1)
                );
            }
        }
        sort($files);

        $this->assertCount(47, $references);
        $this->assertSame($references, $files);

        foreach ($references as $reference) {
            $contents = file_get_contents($basePath.'/'.$reference);
            $this->assertNotFalse($contents);
            $decoded = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
            $this->assertTrue(is_array($decoded) || $decoded instanceof stdClass);
        }
    }

    public function test_resolved_examples_preserve_all_documented_associations(): void
    {
        $modules = $this->registry->getModules();
        $requestCount = 0;
        $successCount = 0;
        $errorCount = 0;

        foreach ($modules as $module) {
            foreach ($module['operations'] as $operation) {
                $requestCount += $operation['request_payload'] !== null ? 1 : 0;
                $successCount += isset($operation['response_success']['body']) ? 1 : 0;
                $errorCount += count($operation['response_errors']);

                $this->assertFalse(is_string($operation['request_payload']));
                $this->assertFalse(is_string($operation['response_success']['body']));
                foreach ($operation['response_errors'] as $errorExample) {
                    $this->assertFalse(is_string($errorExample));
                }
            }
        }

        $this->assertSame(7, $requestCount);
        $this->assertSame(20, $successCount);
        $this->assertSame(60, $errorCount);
    }

    public function test_directory_traversal_reference_is_rejected(): void
    {
        $this->config->set(
            'data_sync_explorer.directions.inbound.modules.technologies.operations.sync.request_payload',
            '../outside.json'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contains an invalid path segment');

        $this->registry->getModules();
    }

    public function test_missing_example_file_produces_a_clear_error(): void
    {
        $this->config->set(
            'data_sync_explorer.directions.inbound.modules.technologies.operations.sync.request_payload',
            'inbound/technologies/missing.request.json'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('was not found');

        $this->registry->getModules();
    }

    public function test_malformed_json_produces_a_clear_error(): void
    {
        $directory = $this->createTemporaryExampleDirectory([
            'malformed.json' => '{"invalid":',
        ]);
        $this->configureSingleExample('malformed.json');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid JSON');

        (new DataSyncExplorerRegistry($directory))->getModules();
    }

    public function test_unsupported_top_level_json_value_is_rejected(): void
    {
        $directory = $this->createTemporaryExampleDirectory([
            'primitive.json' => '"not an object or array"',
        ]);
        $this->configureSingleExample('primitive.json');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must contain a JSON object or array');

        (new DataSyncExplorerRegistry($directory))->getModules();
    }

    public function test_empty_json_objects_and_arrays_retain_their_types(): void
    {
        $directory = $this->createTemporaryExampleDirectory([
            'types.json' => '{"empty_object":{},"empty_array":[],"nested":[{},[]]}',
        ]);
        $this->configureSingleExample('types.json');

        $operation = (new DataSyncExplorerRegistry($directory))
            ->getOperation('sample', 'show');
        $payload = $operation['request_payload'];

        $this->assertInstanceOf(stdClass::class, $payload['empty_object']);
        $this->assertSame([], $payload['empty_array']);
        $this->assertInstanceOf(stdClass::class, $payload['nested'][0]);
        $this->assertSame([], $payload['nested'][1]);
        $this->assertSame(
            '{"empty_object":{},"empty_array":[],"nested":[{},[]]}',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTemporaryExampleDirectory(array $files): string
    {
        $directory = sys_get_temp_dir().'/data-sync-explorer-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        $this->temporaryDirectories[] = $directory;

        foreach ($files as $name => $contents) {
            $path = $directory.'/'.$name;
            file_put_contents($path, $contents);
            $this->temporaryFiles[] = $path;
        }

        return $directory;
    }

    private function configureSingleExample(string $reference): void
    {
        $this->config->set('data_sync_explorer.directions.inbound.module_order', ['sample']);
        $this->config->set('data_sync_explorer.directions.inbound.modules', [
            'sample' => [
                'category' => 'settings',
                'operations' => [
                    'show' => [
                        'request_payload' => $reference,
                        'response_success' => [
                            'status' => 200,
                            'body' => $reference,
                        ],
                        'response_errors' => [],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  list<string>  $references
     */
    private function collectExampleReferences(mixed $value, array &$references): void
    {
        if (is_string($value) && str_ends_with($value, '.json')) {
            $references[] = $value;

            return;
        }

        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            $this->collectExampleReferences($item, $references);
        }
    }
}
