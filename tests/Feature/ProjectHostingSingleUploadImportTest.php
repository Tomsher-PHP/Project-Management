<?php

namespace Tests\Feature;

use App\Jobs\ProcessProjectImportJob;
use App\Models\HostingBillingCycle;
use App\Models\HostingEnvironment;
use App\Models\HostingProvider;
use App\Models\HostingService;
use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\ProjectHostingStatusHistory;
use App\Models\ProjectImport;
use App\Models\User;
use App\Services\ProjectHostingImportValidator;
use App\Services\ProjectImportTrackingManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProjectHostingSingleUploadImportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $authorizedUser;
    protected User $unauthorizedUser;
    protected Project $project1;
    protected Project $project2;
    protected HostingProvider $provider;
    protected HostingService $serviceType;
    protected HostingServiceStatus $activeStatus;
    protected HostingBillingCycle $billingCycle;
    protected HostingEnvironment $environment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->authorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'project_hosting.create',
            'guard_name' => 'web',
        ]);
        $this->authorizedUser->givePermissionTo($permission);

        $this->unauthorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Login as authorized user so Project::booted sets added_by
        auth()->login($this->authorizedUser);

        $this->project1 = Project::create([
            'project_code' => 'PRJ-SGL-001',
            'name' => 'Single Upload Test Alpha',
            'status_id' => 1,
            'project_flow' => 'agile',
        ]);
        $this->project1->is_system = false;
        $this->project1->added_by = $this->authorizedUser->id;
        $this->project1->saveQuietly();

        $this->project2 = Project::create([
            'project_code' => 'PRJ-SGL-002',
            'name' => 'Single Upload Test Beta',
            'status_id' => 1,
            'project_flow' => 'agile',
        ]);
        $this->project2->is_system = false;
        $this->project2->added_by = $this->authorizedUser->id;
        $this->project2->saveQuietly();

        $this->provider = HostingProvider::firstOrCreate(
            ['name' => 'Amazon Web Services (AWS)'],
            ['code' => 'aws', 'is_active' => true]
        );

        $this->serviceType = HostingService::firstOrCreate(
            ['name' => 'Dedicated Server'],
            ['code' => 'dedicated', 'is_active' => true]
        );

        $this->activeStatus = HostingServiceStatus::firstOrCreate(
            ['name' => 'Active'],
            ['code' => 'active', 'is_active' => true, 'is_default' => true]
        );

        $this->billingCycle = HostingBillingCycle::firstOrCreate(
            ['name' => 'Monthly'],
            ['code' => 'monthly', 'is_active' => true]
        );

        $this->environment = HostingEnvironment::firstOrCreate(
            ['name' => 'Production'],
            ['code' => 'production', 'is_active' => true]
        );
    }

    /**
     * Create an in-memory/temp UploadedFile for testing.
     */
    protected function createExcelFile(array $rows, string $sheetName = 'Hosting Upload', bool $includeImportErrorCol = false): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }

        if ($includeImportErrorCol) {
            $sheet->setCellValue('S1', 'Import Error');
        }

        foreach ($rows as $rowIndex => $rowData) {
            $rowNum = $rowIndex + 2;
            foreach ($rowData as $col => $val) {
                $sheet->setCellValue($col . $rowNum, $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'sgl_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile(
            $tempPath,
            'project_hosting_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * Test single upload starts an import, creates zero database import records,
     * processes valid rows while reporting failed rows, and does not stop valid rows.
     */
    public function test_single_upload_imports_valid_rows_reports_failed_rows_and_writes_no_db_import_records(): void
    {
        $initialDbImportCount = ProjectImport::count();
        $initialHostingCount = ProjectHostingRecord::count();

        // Row 2: Valid
        // Row 3: Invalid (Unknown provider)
        // Row 4: Valid
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Database Primary Server',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'db1.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2026-12-31',
                'M' => $this->billingCycle->name,
                'N' => '250.00',
                'O' => 'USD',
            ],
            [
                'A' => $this->project1->project_code,
                'B' => 'Invalid Provider Service',
                'C' => 'Non Existent Provider XYZ',
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
            [
                'A' => $this->project2->project_code,
                'B' => 'Redis Cache Instance',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'redis.example.com',
                'H' => $this->environment->name,
                'I' => '2026-02-01',
                'J' => '2027-02-01',
                'M' => $this->billingCycle->name,
                'N' => '150.00',
                'O' => 'USD',
            ],
        ]);

        // Submit to start import (single-upload workflow)
        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Assert NO database records in project_imports table
        $this->assertEquals($initialDbImportCount, ProjectImport::count(), 'Zero database records should be created in project_imports table.');

        // Extract import_id from redirect URL
        $redirectUrl = $response->headers->get('Location');
        $this->assertNotNull($redirectUrl);
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'] ?? null;
        $this->assertNotEmpty($importId);

        // 2. Process the queued job synchronously
        ProcessProjectImportJob::dispatchSync($importId);

        // 3. Verify status endpoint returns accurate counts and results
        $statusRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));

        $statusRes->assertOk();
        $statusData = $statusRes->json('data');

        $this->assertEquals(ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS, $statusData['status']);
        $this->assertEquals(3, $statusData['total_rows']);
        $this->assertEquals(3, $statusData['processed_rows']);
        $this->assertEquals(2, $statusData['successful_rows']);
        $this->assertEquals(1, $statusData['failed_rows']);
        $this->assertTrue($statusData['is_finished']);

        // 4. Verify 2 database records were added to project_hosting_records
        $this->assertEquals($initialHostingCount + 2, ProjectHostingRecord::count());

        $this->assertDatabaseHas('project_hosting_records', [
            'project_id' => $this->project1->id,
            'name' => 'Database Primary Server',
        ]);
        $this->assertDatabaseHas('project_hosting_records', [
            'project_id' => $this->project2->id,
            'name' => 'Redis Cache Instance',
        ]);
        $this->assertDatabaseMissing('project_hosting_records', [
            'name' => 'Invalid Provider Service',
        ]);

        // 5. Verify source workbook was cleaned up
        /** @var ProjectImportTrackingManager $trackingManager */
        $trackingManager = app(ProjectImportTrackingManager::class);
        $tracking = $trackingManager->getTracking($importId);
        $this->assertFalse(Storage::disk('local')->exists($tracking['file_path']));
    }

    /**
     * Test completely blank rows are skipped and original Excel row numbers are preserved.
     */
    public function test_blank_rows_are_skipped_and_row_numbers_preserved(): void
    {
        $file = $this->createExcelFile([
            // Row 2: Valid
            [
                'A' => $this->project1->project_code,
                'B' => 'Row 2 Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
            // Row 3: Blank
            [
                'A' => '',
                'B' => null,
            ],
            // Row 4: Valid
            [
                'A' => $this->project1->project_code,
                'B' => 'Row 4 Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($importId);

        $statusRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));

        $statusData = $statusRes->json('data');
        $this->assertEquals(2, $statusData['total_rows']);
        $this->assertEquals(2, $statusData['successful_rows']);

        $rowResults = $statusData['row_results'];
        $this->assertCount(2, $rowResults);
        $this->assertEquals(2, $rowResults[0]['excel_row']);
        $this->assertEquals(4, $rowResults[1]['excel_row']);
    }

    /**
     * Test invalid workbook structure creates no hosting records and halts with error.
     */
    public function test_invalid_workbook_structure_creates_no_records(): void
    {
        $initialHostingCount = ProjectHostingRecord::count();

        // Create workbook with wrong sheet name
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Should Not Import',
            ],
        ], sheetName: 'Wrong Sheet Name');

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($importId);

        $statusRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));

        $statusData = $statusRes->json('data');
        $this->assertEquals(ProjectImportTrackingManager::STATUS_FAILED, $statusData['status']);
        $this->assertStringContainsString('Hosting Upload', $statusData['error_message']);
        $this->assertEquals($initialHostingCount, ProjectHostingRecord::count());
    }

    /**
     * Test permission and ownership protections for imports, status, and downloads.
     */
    public function test_permission_and_ownership_protections(): void
    {
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Secure Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
            ],
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($importId);

        // Unauthorized user cannot view status of another user's import
        $unauthRes = $this->actingAs($this->unauthorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));
        $unauthRes->assertForbidden();

        // Unauthorized user cannot download failed or all results
        $dlRes = $this->actingAs($this->unauthorizedUser)
            ->get(route('projects.import.download.results', ['module' => 'project_hosting', 'import' => $importId]));
        $dlRes->assertForbidden();

        // Owner can access
        $ownerRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));
        $ownerRes->assertOk();

        // Super admin can access
        $adminRes = $this->actingAs($this->superAdmin)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));
        $adminRes->assertOk();
    }

    /**
     * Test downloading failed rows, correcting data, and re-importing successfully
     * (verifies importer ignores 19th 'Import Error' column).
     */
    public function test_download_failed_rows_and_reimport_with_import_error_column(): void
    {
        // 1. Initial import with 1 failed row
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Initial Failed Service',
                'C' => 'Invalid Provider Name',
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($importId);

        // 2. Download failed rows
        $downloadRes = $this->actingAs($this->authorizedUser)
            ->get(route('projects.import.download.failed', ['module' => 'project_hosting', 'import' => $importId]));

        $downloadRes->assertOk();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $downloadRes->headers->get('Content-Type')
        );

        // 3. Create a corrected file mimicking user fixing the downloaded failed file
        // (It includes column S "Import Error" which the importer must ignore)
        $correctedFile = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Corrected Service Now Valid',
                'C' => $this->provider->name, // Corrected to valid provider
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'S' => 'Provider: The selected provider was not available.', // Old error in column S
            ],
        ], sheetName: 'Hosting Upload', includeImportErrorCol: true);

        // 4. Re-upload corrected file
        $reimportRes = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $correctedFile,
            ]);

        $reimportRes->assertRedirect();
        parse_str(parse_url($reimportRes->headers->get('Location'), PHP_URL_QUERY), $newQueryParams);
        $newImportId = $newQueryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($newImportId);

        $newStatusRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $newImportId]));

        $newStatusData = $newStatusRes->json('data');
        $this->assertEquals(ProjectImportTrackingManager::STATUS_COMPLETED, $newStatusData['status']);
        $this->assertEquals(1, $newStatusData['successful_rows']);
        $this->assertEquals(0, $newStatusData['failed_rows']);

        $this->assertDatabaseHas('project_hosting_records', [
            'name' => 'Corrected Service Now Valid',
        ]);
    }

    /**
     * Test download all results endpoint generates a valid spreadsheet with report columns.
     */
    public function test_download_all_results_endpoint(): void
    {
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'All Results Audit Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        ProcessProjectImportJob::dispatchSync($importId);

        $downloadRes = $this->actingAs($this->authorizedUser)
            ->get(route('projects.import.download.results', ['module' => 'project_hosting', 'import' => $importId]));

        $downloadRes->assertOk();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $downloadRes->headers->get('Content-Type')
        );
    }

    /**
     * Test creation failure rolls back both the hosting record and its status history for that row.
     */
    public function test_creation_failure_rolls_back_record_and_status_history_for_that_row(): void
    {
        $initialHistoryCount = ProjectHostingStatusHistory::count();
        $initialHostingCount = ProjectHostingRecord::count();

        // Row 2: Valid
        // Row 3: Valid row, but we simulate a creation failure via mocking
        // Row 4: Valid
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Rollback Row 1 Good',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
            [
                'A' => $this->project1->project_code,
                'B' => 'Rollback Row 2 Bad',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
            [
                'A' => $this->project1->project_code,
                'B' => 'Rollback Row 3 Good',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
            ],
        ]);

        // Simulate a creation failure on row 2 ('Rollback Row 2 Bad') via Eloquent model event
        ProjectHostingRecord::creating(function ($record) {
            if ($record->name === 'Rollback Row 2 Bad') {
                throw new \RuntimeException('Simulated database write error during record creation');
            }
        });

        $response = $this->actingAs($this->authorizedUser)
            ->post(route('projects.import.process', 'project_hosting'), [
                'file' => $file,
            ]);

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $queryParams);
        $importId = $queryParams['import_id'];

        $statusRes = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));

        $statusData = $statusRes->json('data');
        $this->assertEquals(2, $statusData['successful_rows']);
        $this->assertEquals(1, $statusData['failed_rows']);

        // Check that 'Rollback Row 2 Bad' does NOT exist in project_hosting_records or status history
        $this->assertDatabaseMissing('project_hosting_records', [
            'name' => 'Rollback Row 2 Bad',
        ]);
        $this->assertEquals($initialHostingCount + 2, ProjectHostingRecord::count());
        $this->assertEquals($initialHistoryCount + 2, ProjectHostingStatusHistory::count());
    }

    /**
     * Test interrupted job records remaining rows as unprocessed and marks status interrupted.
     */
    public function test_interrupted_job_marks_status_interrupted_and_records_unprocessed_rows(): void
    {
        /** @var ProjectImportTrackingManager $trackingManager */
        $trackingManager = app(ProjectImportTrackingManager::class);

        $importId = (string) \Illuminate\Support\Str::uuid();
        $trackingManager->initTracking($importId, 'project_hosting', 'test.xlsx', 'path.xlsx', $this->authorizedUser->id);

        $trackingManager->updateTracking($importId, [
            'total_rows' => 4,
            'processed_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
            'row_results' => [
                ['excel_row' => 2, 'status' => 'success', 'message' => 'Record created.'],
                ['excel_row' => 3, 'status' => 'failed', 'message' => 'Validation error.'],
            ],
        ]);

        $trackingManager->markInterrupted($importId, 'System connection dropped unexpectedly.');

        $tracking = $trackingManager->getTracking($importId);
        $this->assertEquals(ProjectImportTrackingManager::STATUS_INTERRUPTED, $tracking['status']);
        $this->assertStringContainsString('System connection dropped', $tracking['error_message']);

        // Rows 4 and 5 should be marked as unprocessed
        $this->assertCount(4, $tracking['row_results']);
        $this->assertEquals('unprocessed', $tracking['row_results'][2]['status']);
        $this->assertEquals('unprocessed', $tracking['row_results'][3]['status']);
    }

    /**
     * Test expiry and cleanup in tracking manager.
     */
    public function test_tracking_manager_expiry_and_cleanup(): void
    {
        /** @var ProjectImportTrackingManager $trackingManager */
        $trackingManager = app(ProjectImportTrackingManager::class);

        $importId = (string) \Illuminate\Support\Str::uuid();
        $trackingManager->initTracking($importId, 'project_hosting', 'expiring.xlsx', 'path.xlsx', $this->authorizedUser->id);

        // Manually backdate expires_at to 1 hour ago
        $trackingManager->updateTracking($importId, [
            'expires_at' => now()->subHour()->timestamp,
        ]);

        // getTracking should return null for expired import
        $this->assertNull($trackingManager->getTracking($importId));

        // HTTP status endpoint should return 404 with expired flag
        $res = $this->actingAs($this->authorizedUser)
            ->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importId]));

        $res->assertNotFound();
        $this->assertTrue($res->json('expired'));
    }
}
