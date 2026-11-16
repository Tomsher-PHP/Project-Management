<?php

namespace Tests\Feature;

use App\Jobs\ProcessProjectImportJob;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use App\Services\ProjectBulkImportService;
use App\Services\ProjectBulkImportValidator;
use App\Services\ProjectImportTrackingManager;
use App\Services\ProjectServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectsImportEndToEndTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $bothUser;

    protected User $createOnlyUser;

    protected User $editOnlyUser;

    protected User $neitherUser;

    protected Customer $customer;

    protected ProjectStatus $statusOpen;

    protected ProjectStatus $statusInProgress;

    protected ProjectStage $stagePlanning;

    protected ProjectStage $stageExecution;

    protected ServiceType $serviceWeb;

    protected ServiceType $serviceSeo;

    protected ProjectCategory $categoryWeb;

    protected ProjectCategory $categoryMobile;

    protected Technology $techLaravel;

    protected Technology $techVue;

    protected array $tempFiles = [];

    protected array $trackingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->bothUser = $this->makeUserWithPermissions([
            'project.view',
            'project.create',
            'project.edit',
            'project.export',
            'user.view_all_users',
        ]);

        $this->createOnlyUser = $this->makeUserWithPermissions([
            'project.view',
            'project.create',
        ]);

        $this->editOnlyUser = $this->makeUserWithPermissions([
            'project.view',
            'project.edit',
            'user.view_all_users',
        ]);

        $this->neitherUser = $this->makeUserWithPermissions([
            'project.view',
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-E2E-' . rand(1000, 9999),
            'name' => 'E2E Projects Customer',
            'is_active' => true,
            'sales_person_id' => $this->bothUser->id,
        ]);

        $this->statusOpen = ProjectStatus::active()->where('is_default', true)->first()
            ?? ProjectStatus::firstOrCreate(
                ['code' => 'e2e_open_' . uniqid()],
                ['name' => 'Open', 'type' => 'open', 'sort_order' => 1, 'is_default' => true, 'is_active' => true]
            );

        $this->statusInProgress = ProjectStatus::firstOrCreate(
            ['code' => 'e2e_prog_' . uniqid()],
            ['name' => 'In Progress', 'type' => 'in_progress', 'sort_order' => 2, 'is_default' => false, 'is_active' => true]
        );

        $this->stagePlanning = ProjectStage::active()->where('is_default', true)->first()
            ?? ProjectStage::firstOrCreate(
                ['code' => 'e2e_plan_' . uniqid()],
                ['name' => 'Planning', 'sort_order' => 1, 'is_default' => true, 'is_active' => true]
            );

        $this->stageExecution = ProjectStage::firstOrCreate(
            ['code' => 'e2e_exec_' . uniqid()],
            ['name' => 'Execution', 'sort_order' => 2, 'is_default' => false, 'is_active' => true]
        );

        $serviceGroup = ServiceGroup::firstOrCreate(
            ['code' => 'grp_e2e_prj'],
            ['name' => 'E2E Group', 'sort_order' => 1, 'is_active' => true]
        );

        $this->serviceWeb = ServiceType::create([
            'service_group_id' => $serviceGroup->id,
            'name' => 'E2E Web',
            'code' => 'pst-' . random_int(100000, 999999),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->serviceSeo = ServiceType::create([
            'service_group_id' => $serviceGroup->id,
            'name' => 'E2E SEO',
            'code' => 'pst-' . random_int(100000, 999999),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->categoryWeb = ProjectCategory::create([
            'name' => 'E2E Web Cat ' . uniqid(),
            'code' => 'pct-' . random_int(100000, 999999),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->categoryMobile = ProjectCategory::create([
            'name' => 'E2E Mobile Cat ' . uniqid(),
            'code' => 'pct-' . random_int(100000, 999999),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->techLaravel = Technology::firstOrCreate(['name' => 'Laravel'], ['sort_order' => 1, 'is_active' => true]);
        $this->techVue = Technology::firstOrCreate(['name' => 'Vue.js'], ['sort_order' => 2, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        $trackingManager = app(ProjectImportTrackingManager::class);
        foreach ($this->trackingIds as $id) {
            $trackingManager->deleteTrackingDirectory($id);
        }
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    protected function makeUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
        ]);

        foreach ($permissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    protected function createUploadedWorkbook(
        array $rows,
        string $sheetName = ProjectBulkImportValidator::WORKSHEET_NAME,
        string $filename = 'bulk_projects.xlsx'
    ): UploadedFile {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        foreach (ProjectBulkImportValidator::EXPECTED_HEADERS as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }

        foreach ($rows as $index => $rowData) {
            $rowNumber = $index + 2;
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue($col . $rowNumber, $value);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'e2e_prj_') . '.xlsx';
        $this->tempFiles[] = $tempPath;

        (new Xlsx($spreadsheet))->save($tempPath);

        return new UploadedFile(
            $tempPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_full_end_to_end_http_workflow_status_polling_and_excel_downloads(): void
    {
        $existingProject = app(ProjectServices::class)->create([
            'name' => 'E2E Existing Site',
            'customer_id' => $this->customer->id,
            'project_flow' => 'linear',
            'priority' => 'low',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ], $this->bothUser);

        $unchangedProject = app(ProjectServices::class)->create([
            'name' => 'E2E Unchanged Site',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
        ], $this->bothUser);

        // 1. Projects list shows Import button & Export still works
        $this->actingAs($this->bothUser)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee(route('projects.import.index'), false);

        // 2. Import page defaults to Projects and template downloads
        $this->actingAs($this->bothUser)
            ->get(route('projects.import.index'))
            ->assertOk()
            ->assertViewHas('selectedModuleKey', 'projects');

        $this->actingAs($this->bothUser)
            ->get(route('projects.import.template', 'projects'))
            ->assertOk()
            ->assertDownload('bulk-projects-import-template-final.xlsx');

        // 3. Upload workbook with Create + Update + Unchanged + Failed rows
        $file = $this->createUploadedWorkbook([
            // Row 2: Create
            [
                'B' => 'E2E Created App',
                'C' => $this->customer->customer_code,
                'D' => 'agile',
                'E' => 'high',
                'H' => $this->serviceWeb->code,
                'I' => $this->categoryWeb->code,
                'N' => '10',
                'P' => 'Laravel',
                'Q' => $this->editOnlyUser->email,
                'S' => 'Yes',
            ],
            // Row 3: Update (only Priority & Estimated Hours)
            [
                'A' => $existingProject->project_code,
                'E' => 'urgent',
                'N' => '15.5',
            ],
            // Row 4: Unchanged
            [
                'A' => $unchangedProject->project_code,
                'B' => 'E2E Unchanged Site',
            ],
            // Row 5: Failed Create (missing Customer Code)
            [
                'B' => 'Broken New Project',
                'D' => 'agile',
                'E' => 'low',
            ],
            // Row 6: Failed Update (nonblank Project Flow even if matching)
            [
                'A' => $unchangedProject->project_code,
                'D' => 'agile',
            ],
        ]);

        // Fake queue to verify async dispatch first, then run job synchronously
        Queue::fake();

        $uploadResponse = $this->actingAs($this->bothUser)
            ->postJson(route('projects.import.process', 'projects'), [
                'file' => $file,
            ]);

        $uploadResponse->assertStatus(202)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.module_key', 'projects')
            ->assertJsonPath('data.status', ProjectImportTrackingManager::STATUS_PENDING);

        $importId = $uploadResponse->json('data.id');
        $this->trackingIds[] = $importId;

        Queue::assertPushed(ProcessProjectImportJob::class, fn ($job) => $job->importId === $importId);

        // 4. Execute queued job with no authenticated session user
        Auth::logout();
        (new ProcessProjectImportJob($importId))->handle(
            app(\App\Services\ProjectImportService::class),
            app(ProjectImportTrackingManager::class)
        );

        // Source file must be cleaned up after job finishes
        $this->assertFalse(
            Storage::disk(ProjectImportTrackingManager::DISK)->exists("project-imports-data/{$importId}/source.xlsx")
        );

        // 5. Status JSON endpoint returns accurate metrics (Processed = Created + Updated + Unchanged + Failed)
        $statusResponse = $this->actingAs($this->bothUser)
            ->getJson(route('projects.import.status', ['module' => 'projects', 'import' => $importId]));

        $statusResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS)
            ->assertJsonPath('data.total_rows', 5)
            ->assertJsonPath('data.processed_rows', 5)
            ->assertJsonPath('data.created_rows', 1)
            ->assertJsonPath('data.updated_rows', 1)
            ->assertJsonPath('data.unchanged_rows', 1)
            ->assertJsonPath('data.failed_rows', 2)
            ->assertJsonPath('data.is_finished', true);

        $rowResults = $statusResponse->json('data.row_results');
        $generatedCode = $rowResults[0]['project_code'];
        $this->assertStringStartsWith('PRJ', $generatedCode);

        // 6. Verify Failed Rows Excel workbook (19 original columns + Import Error on T)
        $failedDownload = $this->actingAs($this->bothUser)
            ->get(route('projects.import.download.failed', ['module' => 'projects', 'import' => $importId]));

        $failedDownload->assertOk();
        $failedSpreadsheet = IOFactory::load($failedDownload->getFile()->getPathname());
        $failedSheet = $failedSpreadsheet->getSheetByName(ProjectBulkImportValidator::WORKSHEET_NAME);
        $this->assertNotNull($failedSheet);
        $this->assertSame('Project Code', $failedSheet->getCell('A1')->getValue());
        $this->assertSame('Default Billable', $failedSheet->getCell('S1')->getValue());
        $this->assertSame('Import Error', $failedSheet->getCell('T1')->getValue());

        // Row 2 = failed create (Row 5)
        $this->assertSame('', (string) $failedSheet->getCell('A2')->getValue());
        $this->assertSame('Broken New Project', $failedSheet->getCell('B2')->getValue());
        $this->assertStringContainsString('Customer Code', $failedSheet->getCell('T2')->getValue());

        // Row 3 = failed update (Row 6)
        $this->assertSame($unchangedProject->project_code, $failedSheet->getCell('A3')->getValue());
        $this->assertSame('agile', $failedSheet->getCell('D3')->getValue());
        $this->assertStringContainsString('Project Flow must be blank', $failedSheet->getCell('T3')->getValue());

        // 7. Re-upload corrected Failed Rows workbook (verifies column T 'Import Error' doesn't break re-import)
        $failedSheet->setCellValue('C2', $this->customer->customer_code); // Fix missing Customer Code
        $failedSheet->setCellValue('D3', ''); // Fix Project Flow on update
        $failedSheet->setCellValue('E3', 'high'); // Apply real update
        $reuploadPath = tempnam(sys_get_temp_dir(), 'reupload_prj_') . '.xlsx';
        $this->tempFiles[] = $reuploadPath;
        (new Xlsx($failedSpreadsheet))->save($reuploadPath);

        $reuploadFile = new UploadedFile(
            $reuploadPath,
            'fixed_failed_rows.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $reuploadTrackingId = (string) Str::uuid();
        $this->trackingIds[] = $reuploadTrackingId;
        $reuploadStored = "project-imports-data/{$reuploadTrackingId}/source.xlsx";
        Storage::disk(ProjectImportTrackingManager::DISK)->put($reuploadStored, file_get_contents($reuploadPath));
        app(ProjectImportTrackingManager::class)->initTracking($reuploadTrackingId, 'projects', 'fixed.xlsx', $reuploadStored, $this->bothUser->id);

        app(ProjectBulkImportService::class)->process($reuploadTrackingId);
        $reuploadMeta = app(ProjectImportTrackingManager::class)->getTracking($reuploadTrackingId);
        $this->assertSame(ProjectImportTrackingManager::STATUS_COMPLETED, $reuploadMeta['status']);
        $this->assertSame(1, $reuploadMeta['created_rows']);
        $this->assertSame(1, $reuploadMeta['updated_rows']);
        $this->assertSame(0, $reuploadMeta['failed_rows']);

        // 8. Verify All Results Excel workbook
        $allDownload = $this->actingAs($this->bothUser)
            ->get(route('projects.import.download.results', ['module' => 'projects', 'import' => $importId]));

        $allDownload->assertOk();
        $allSpreadsheet = IOFactory::load($allDownload->getFile()->getPathname());
        $allSheet = $allSpreadsheet->getSheetByName('Import Results');
        $this->assertNotNull($allSheet);

        $this->assertSame('Excel Row', $allSheet->getCell('A1')->getValue());
        $this->assertSame('Project Code', $allSheet->getCell('B1')->getValue());
        $this->assertSame('Project Name', $allSheet->getCell('C1')->getValue());
        $this->assertSame('Result', $allSheet->getCell('D1')->getValue());
        $this->assertSame('Details', $allSheet->getCell('E1')->getValue());
        $this->assertSame('Project Code', $allSheet->getCell('F1')->getValue()); // Original uploaded column F
        $this->assertSame('Default Billable', $allSheet->getCell('X1')->getValue());

        // Created row: generated Project Code in column B, original blank Project Code preserved in column F
        $this->assertSame('Row 2', $allSheet->getCell('A2')->getValue());
        $this->assertSame($generatedCode, $allSheet->getCell('B2')->getValue());
        $this->assertSame('E2E Created App', $allSheet->getCell('C2')->getValue());
        $this->assertSame('Created', $allSheet->getCell('D2')->getValue());
        $this->assertSame('', (string) $allSheet->getCell('F2')->getValue());
        $this->assertSame('E2E Created App', $allSheet->getCell('G2')->getValue());

        // Updated row retains existing code
        $this->assertSame($existingProject->project_code, $allSheet->getCell('B3')->getValue());
        $this->assertSame('Updated', $allSheet->getCell('D3')->getValue());

        // Privacy check: another user cannot access status or download reports
        $this->actingAs($this->editOnlyUser)
            ->getJson(route('projects.import.status', ['module' => 'projects', 'import' => $importId]))
            ->assertForbidden();

        $this->actingAs($this->editOnlyUser)
            ->get(route('projects.import.download.failed', ['module' => 'projects', 'import' => $importId]))
            ->assertForbidden();

        $this->actingAs($this->editOnlyUser)
            ->get(route('projects.import.download.results', ['module' => 'projects', 'import' => $importId]))
            ->assertForbidden();
    }

    public function test_create_validation_edge_cases_and_field_level_updates(): void
    {
        $project = app(ProjectServices::class)->create([
            'name' => 'Field Update Target',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'low',
        ], $this->bothUser);

        $file = $this->createUploadedWorkbook([
            // Row 2: Missing Project Name, Customer Code, Project Flow, invalid Priority -> fail
            ['E' => 'ultra_invalid'],
            // Row 3: Invalid Project Flow -> fail
            ['B' => 'Bad Flow', 'C' => $this->customer->customer_code, 'D' => 'waterfall', 'E' => 'low'],
            // Row 4: Update only Service Type Codes
            ['A' => $project->project_code, 'H' => $this->serviceWeb->code],
            // Row 5: Update invalid date (End Date before Start Date) -> fail
            ['A' => $project->project_code . '_NOPE'],
        ]);

        $importId = (string) Str::uuid();
        $this->trackingIds[] = $importId;
        $stored = "project-imports-data/{$importId}/source.xlsx";
        Storage::disk(ProjectImportTrackingManager::DISK)->put($stored, file_get_contents($file->getRealPath()));
        app(ProjectImportTrackingManager::class)->initTracking($importId, 'projects', 'cases.xlsx', $stored, $this->bothUser->id);

        app(ProjectBulkImportService::class)->process($importId);
        $meta = app(ProjectImportTrackingManager::class)->getTracking($importId);

        $this->assertSame(0, $meta['created_rows']);
        $this->assertSame(1, $meta['updated_rows']);
        $this->assertSame(3, $meta['failed_rows']);

        $project->refresh();
        $this->assertSame([$this->serviceWeb->id], $project->serviceTypes->pluck('id')->all());
        $this->assertSame('Field Update Target', $project->name);
        $this->assertSame('low', $project->priority);
    }

    public function test_excel_file_upload_validations_extension_empty_size_and_5000_row_limit(): void
    {
        // Unsupported extension via HTTP upload
        $txtFile = UploadedFile::fake()->create('projects.txt', 10, 'text/plain');
        $this->actingAs($this->bothUser)
            ->postJson(route('projects.import.process', 'projects'), ['file' => $txtFile])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // File larger than 20 MB via HTTP upload
        $largeFile = UploadedFile::fake()->create('huge.xlsx', 21000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($this->bothUser)
            ->postJson(route('projects.import.process', 'projects'), ['file' => $largeFile])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // Empty workbook (headers only, 0 data rows)
        $emptyFile = $this->createUploadedWorkbook([]);
        $validator = app(ProjectBulkImportValidator::class);
        $emptyRes = $validator->validate($emptyFile->getRealPath(), $this->bothUser);
        $this->assertTrue($emptyRes['is_valid_structure']);
        $this->assertSame(0, $emptyRes['total_rows']);

        // Exceeds 5,000 non-blank rows
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ProjectBulkImportValidator::WORKSHEET_NAME);
        foreach (ProjectBulkImportValidator::EXPECTED_HEADERS as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }
        for ($r = 2; $r <= 5002; $r++) {
            $sheet->setCellValue('B' . $r, 'Row ' . $r);
        }
        $overLimitPath = tempnam(sys_get_temp_dir(), 'over5k_') . '.xlsx';
        $this->tempFiles[] = $overLimitPath;
        (new Xlsx($spreadsheet))->save($overLimitPath);

        $overRes = $validator->validate($overLimitPath, $this->bothUser);
        $this->assertFalse($overRes['is_valid_structure']);
        $this->assertStringContainsString('5,000', $overRes['structure_error']);
    }

    public function test_job_failure_interrupted_processing_and_tracking_expiry_cleanup(): void
    {
        $trackingManager = app(ProjectImportTrackingManager::class);
        $importId = (string) Str::uuid();
        $this->trackingIds[] = $importId;

        $stored = "project-imports-data/{$importId}/source.xlsx";
        Storage::disk(ProjectImportTrackingManager::DISK)->put($stored, 'dummy');
        $trackingManager->initTracking($importId, 'projects', 'fail.xlsx', $stored, $this->bothUser->id);
        $trackingManager->updateTracking($importId, [
            'total_rows' => 3,
            'row_results' => [
                ['excel_row' => 2, 'project_code' => 'PRJ00001', 'project_name' => 'Done Row', 'status' => 'created', 'message' => 'Created', 'raw_values' => []],
            ],
        ]);

        // Simulate job permanent failure
        (new ProcessProjectImportJob($importId))->failed(new \RuntimeException('Worker killed unexpectedly'));

        $meta = $trackingManager->getTracking($importId);
        $this->assertSame(ProjectImportTrackingManager::STATUS_INTERRUPTED, $meta['status']);
        $this->assertCount(3, $meta['row_results']);
        $this->assertSame('unprocessed', $meta['row_results'][1]['status']);
        $this->assertSame('unprocessed', $meta['row_results'][2]['status']);
        $this->assertArrayHasKey('project_name', $meta['row_results'][1]);
        $this->assertFalse(Storage::disk(ProjectImportTrackingManager::DISK)->exists($stored));

        // Simulate tracking expiry
        $trackingManager->updateTracking($importId, ['expires_at' => now()->subMinute()->timestamp]);
        $this->assertNull($trackingManager->getTracking($importId));

        $this->actingAs($this->bothUser)
            ->getJson(route('projects.import.status', ['module' => 'projects', 'import' => $importId]))
            ->assertNotFound()
            ->assertJsonPath('expired', true);
    }

    public function test_permission_boundaries_and_project_export_regression(): void
    {
        // Neither permission cannot access import page
        $this->actingAs($this->neitherUser)
            ->get(route('projects.import.index'))
            ->assertForbidden();

        // Create-only user can access Projects import, cannot update existing project
        $this->actingAs($this->createOnlyUser)
            ->get(route('projects.import.index'))
            ->assertOk()
            ->assertViewHas('selectedModuleKey', 'projects');

        $targetProject = app(ProjectServices::class)->create([
            'name' => 'Target Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
        ], $this->bothUser);

        $createOnlyUpload = $this->createUploadedWorkbook([
            ['A' => $targetProject->project_code, 'B' => 'Should Not Update'],
        ]);
        $importId = (string) Str::uuid();
        $this->trackingIds[] = $importId;
        $stored = "project-imports-data/{$importId}/source.xlsx";
        Storage::disk(ProjectImportTrackingManager::DISK)->put($stored, file_get_contents($createOnlyUpload->getRealPath()));
        app(ProjectImportTrackingManager::class)->initTracking($importId, 'projects', 'perm.xlsx', $stored, $this->createOnlyUser->id);

        app(ProjectBulkImportService::class)->process($importId);
        $meta = app(ProjectImportTrackingManager::class)->getTracking($importId);
        $this->assertSame(1, $meta['failed_rows']);
        $this->assertStringContainsString('permission', $meta['row_results'][0]['message']);

        // Existing Project Export regression
        $exportResponse = $this->actingAs($this->bothUser)->get(route('projects.export'));
        $exportResponse->assertOk();
    }
}

