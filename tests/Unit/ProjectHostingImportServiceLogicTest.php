<?php

namespace Tests\Unit;

use App\Models\ProjectImport;
use App\Services\Contracts\ProjectImportHandlerInterface;
use App\Services\ProjectHostingImportService;
use App\Services\ProjectHostingImportValidator;
use App\Services\ProjectHostingService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProjectHostingImportServiceLogicTest extends TestCase
{
    public function test_implements_project_import_handler_interface(): void
    {
        $this->assertTrue(is_subclass_of(ProjectHostingImportService::class, ProjectImportHandlerInterface::class));
    }

    public function test_import_model_status_constants_and_casts(): void
    {
        $this->assertEquals('pending', ProjectImport::STATUS_PENDING);
        $this->assertEquals('processing', ProjectImport::STATUS_PROCESSING);
        $this->assertEquals('completed', ProjectImport::STATUS_COMPLETED);
        $this->assertEquals('completed_with_errors', ProjectImport::STATUS_COMPLETED_WITH_ERRORS);
        $this->assertEquals('failed', ProjectImport::STATUS_FAILED);

        $import = new ProjectImport([
            'module_key' => 'project_hosting',
            'file_name' => 'test.xlsx',
            'file_path' => 'project-imports/test.xlsx',
            'status' => ProjectImport::STATUS_PENDING,
            'total_rows' => 10,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'row_results' => [
                ['excel_row' => 2, 'status' => 'Success'],
            ],
        ]);

        $this->assertEquals('project_hosting', $import->module_key);
        $this->assertEquals(10, $import->total_rows);
        $this->assertIsArray($import->row_results);
        $this->assertEquals('Success', $import->row_results[0]['status']);
    }

    public function test_missing_file_throws_file_not_found_exception(): void
    {
        Storage::fake('local');

        $hostingService = Mockery::mock(ProjectHostingService::class);
        $validator = Mockery::mock(ProjectHostingImportValidator::class);
        $handler = new ProjectHostingImportService($hostingService, $validator);

        $import = Mockery::mock(ProjectImport::class)->makePartial();
        $import->file_path = 'project-imports/non_existent.xlsx';

        $this->expectException(FileNotFoundException::class);
        $handler->process($import);
    }

    public function test_handler_halts_when_workbook_is_invalid(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('project-imports/dummy.xlsx', 'content');

        $validator = Mockery::mock(ProjectHostingImportValidator::class);
        $validator->shouldReceive('validate')
            ->once()
            ->andReturn([
                'is_valid_structure' => false,
                'structure_error' => 'Missing worksheet Hosting Upload',
                'total_rows' => 0,
                'valid_rows_count' => 0,
                'invalid_rows_count' => 0,
                'rows' => [],
            ]);

        $hostingService = Mockery::mock(ProjectHostingService::class);
        $handler = new ProjectHostingImportService($hostingService, $validator);

        $user = new \App\Models\User(['id' => 1]);
        $import = Mockery::mock(ProjectImport::class)->makePartial();
        $import->file_path = 'project-imports/dummy.xlsx';
        $import->setRelation('uploader', $user);

        $import->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($attributes) {
                return $attributes['status'] === ProjectImport::STATUS_FAILED
                    && str_contains($attributes['error_message'], 'Missing worksheet');
            }));

        $handler->process($import);
        $this->assertTrue(true);
    }

    public function test_handler_halts_when_validation_errors_exist(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('project-imports/dummy.xlsx', 'content');

        $validator = Mockery::mock(ProjectHostingImportValidator::class);
        $validator->shouldReceive('validate')
            ->once()
            ->andReturn([
                'is_valid_structure' => true,
                'structure_error' => null,
                'total_rows' => 2,
                'valid_rows_count' => 0,
                'invalid_rows_count' => 2,
                'is_all_valid' => false,
                'rows' => [
                    ['row_number' => 2, 'project_code' => 'PRJ-1', 'service_name' => 'Web', 'status' => 'Invalid', 'errors' => ['Missing provider']],
                    ['row_number' => 3, 'project_code' => 'PRJ-2', 'service_name' => 'API', 'status' => 'Invalid', 'errors' => ['Unknown project code: PRJ-999']],
                ],
            ]);

        $hostingService = Mockery::mock(ProjectHostingService::class);
        // Ensure createRecord is never called when validation fails
        $hostingService->shouldNotReceive('createRecord');

        $handler = new ProjectHostingImportService($hostingService, $validator);

        $user = new \App\Models\User(['id' => 1]);
        $import = Mockery::mock(ProjectImport::class)->makePartial();
        $import->file_path = 'project-imports/dummy.xlsx';
        $import->setRelation('uploader', $user);
        $import->shouldReceive('refresh')->andReturnSelf();

        $import->shouldReceive('update')
            ->atLeast()->once()
            ->andReturnUsing(function ($attrs) use ($import) {
                foreach ($attrs as $k => $v) {
                    $import->{$k} = $v;
                }
                return true;
            });

        $handler->process($import);
        $this->assertEquals(ProjectImport::STATUS_FAILED, $import->status);
        $this->assertEquals(2, $import->failed_rows);
    }
}
