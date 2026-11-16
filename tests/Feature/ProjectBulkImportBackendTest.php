<?php

namespace Tests\Feature;

use App\Jobs\ProcessProjectImportJob;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectTimeline;
use App\Models\ServiceGroup;
use App\Models\ServiceType;
use App\Models\Technology;
use App\Models\User;
use App\Services\ProjectBulkImportService;
use App\Services\ProjectBulkImportValidator;
use App\Services\ProjectImportTrackingManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectBulkImportBackendTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $creatorUser;

    protected User $otherUser;

    protected Customer $customer;

    protected Customer $customerTwo;

    protected ProjectStatus $statusOpen;

    protected ProjectStatus $statusInProgress;

    protected ProjectStage $stagePlanning;

    protected ProjectStage $stageExecution;

    protected ServiceType $serviceWeb;

    protected ServiceType $serviceSeo;

    protected ProjectCategory $categoryWeb;

    protected ProjectCategory $categoryApp;

    protected Technology $techLaravel;

    protected Technology $techReact;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->creatorUser = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
            'email' => 'import.creator.' . uniqid() . '@example.com',
        ]);

        foreach (['project.view', 'project.create', 'project.edit', 'user.view_all_users'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $this->creatorUser->givePermissionTo($perm);
        }

        $this->otherUser = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
            'email' => 'import.other.' . uniqid() . '@example.com',
        ]);
        $editPerm = Permission::firstOrCreate(['name' => 'project.edit', 'guard_name' => 'web']);
        $this->otherUser->givePermissionTo($editPerm);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-IMP-' . rand(1000, 9999),
            'name' => 'Import Test Customer One',
            'is_active' => true,
            'sales_person_id' => $this->creatorUser->id,
        ]);

        $this->customerTwo = Customer::create([
            'customer_code' => 'CUS-IMP-' . rand(1000, 9999),
            'name' => 'Import Test Customer Two',
            'is_active' => true,
            'sales_person_id' => $this->otherUser->id,
        ]);

        $this->statusOpen = ProjectStatus::active()->where('is_default', true)->first()
            ?? ProjectStatus::firstOrCreate(
                ['code' => 'imp_open_' . uniqid()],
                ['name' => 'Open', 'type' => 'open', 'sort_order' => 1, 'is_default' => true, 'is_active' => true]
            );

        $this->statusInProgress = ProjectStatus::firstOrCreate(
            ['code' => 'imp_in_progress_' . uniqid()],
            ['name' => 'In Progress Test', 'type' => 'in_progress', 'sort_order' => 2, 'is_default' => false, 'is_active' => true]
        );

        $this->stagePlanning = ProjectStage::active()->where('is_default', true)->first()
            ?? ProjectStage::firstOrCreate(
                ['code' => 'imp_plan_' . uniqid()],
                ['name' => 'Planning', 'sort_order' => 1, 'is_default' => true, 'is_active' => true]
            );

        $this->stageExecution = ProjectStage::firstOrCreate(
            ['code' => 'imp_exec_' . uniqid()],
            ['name' => 'Execution Test', 'sort_order' => 2, 'is_default' => false, 'is_active' => true]
        );

        $serviceGroup = ServiceGroup::firstOrCreate(
            ['code' => 'grp_bulk_imp'],
            ['name' => 'Bulk Import Group', 'sort_order' => 1, 'is_active' => true]
        );

        $this->serviceWeb = ServiceType::create([
            'service_group_id' => $serviceGroup->id,
            'name' => 'Bulk Web Dev',
            'code' => 'pst-' . random_int(100000, 999999),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->serviceSeo = ServiceType::create([
            'service_group_id' => $serviceGroup->id,
            'name' => 'Bulk SEO',
            'code' => 'pst-' . random_int(100000, 999999),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->categoryWeb = ProjectCategory::create([
            'name' => 'Bulk Cat Web ' . uniqid(),
            'code' => 'pct-' . random_int(100000, 999999),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->categoryApp = ProjectCategory::create([
            'name' => 'Bulk Cat App ' . uniqid(),
            'code' => 'pct-' . random_int(100000, 999999),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->techLaravel = Technology::firstOrCreate(
            ['name' => 'Laravel'],
            ['sort_order' => 1, 'is_active' => true]
        );

        $this->techReact = Technology::firstOrCreate(
            ['name' => 'React'],
            ['sort_order' => 2, 'is_active' => true]
        );
    }

    protected function createWorkbook(array $rows, string $sheetName = ProjectBulkImportValidator::WORKSHEET_NAME): string
    {
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

        $tempPath = tempnam(sys_get_temp_dir(), 'bulk_prj_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    protected function dispatchTrackedImport(string $filePath, User $user): array
    {
        $trackingManager = app(ProjectImportTrackingManager::class);
        $importId = (string) Str::uuid();
        $storedPath = $trackingManager->getImportDirectory($importId) . '/source.xlsx';

        Storage::disk(ProjectImportTrackingManager::DISK)->put($storedPath, file_get_contents($filePath));

        $trackingManager->initTracking(
            importId: $importId,
            moduleKey: 'projects',
            fileName: basename($filePath),
            filePath: $storedPath,
            userId: $user->id
        );

        // Explicitly simulate queue worker with no logged-in session user
        Auth::logout();

        ProcessProjectImportJob::dispatchSync($importId);

        $meta = $trackingManager->getTracking($importId);
        $trackingManager->deleteTrackingDirectory($importId);

        return $meta;
    }

    public function test_validator_rejects_invalid_sheet_name_and_headers(): void
    {
        $validator = app(ProjectBulkImportValidator::class);

        $wrongSheetFile = $this->createWorkbook([['B' => 'Test']], 'Wrong Sheet');
        $res1 = $validator->validate($wrongSheetFile, $this->creatorUser);
        $this->assertFalse($res1['is_valid_structure']);
        $this->assertStringContainsString('Projects Upload', $res1['structure_error']);
        @unlink($wrongSheetFile);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ProjectBulkImportValidator::WORKSHEET_NAME);
        $sheet->setCellValue('A1', 'Wrong Header');
        $wrongHeaderFile = tempnam(sys_get_temp_dir(), 'bad_hdr_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($wrongHeaderFile);

        $res2 = $validator->validate($wrongHeaderFile, $this->creatorUser);
        $this->assertFalse($res2['is_valid_structure']);
        $this->assertStringContainsString('Invalid column header', $res2['structure_error']);
        @unlink($wrongHeaderFile);
    }

    public function test_bulk_import_handles_create_update_unchanged_and_failures_via_queue_job(): void
    {
        // Existing project owned by creatorUser to update
        Auth::login($this->creatorUser);
        $existingToUpdate = app(\App\Services\ProjectServices::class)->create([
            'name' => 'Existing Before Update',
            'customer_id' => $this->customer->id,
            'project_flow' => 'linear',
            'priority' => 'low',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'default_billable' => 1,
        ], $this->creatorUser);

        // Existing project that will match supplied values exactly -> Unchanged
        $unchangedProject = app(\App\Services\ProjectServices::class)->create([
            'name' => 'Unchanged Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
            'start_date' => '2026-03-01',
            'end_date' => '2026-04-01',
            'default_billable' => 1,
        ], $this->creatorUser);
        Auth::logout();

        $workbookPath = $this->createWorkbook([
            // Row 2: Create new project with full details + decimal hours
            [
                'B' => 'Created Corporate Portal',
                'C' => $this->customer->customer_code,
                'D' => 'agile',
                'E' => 'high',
                'F' => $this->statusInProgress->code,
                'G' => $this->stageExecution->code,
                'H' => $this->serviceWeb->code . ', ' . $this->serviceSeo->code,
                'I' => $this->categoryWeb->code . ', ' . $this->categoryApp->code,
                'J' => 'Phase 1 Original',
                'K' => '2026-11-15',
                'L' => '2027-02-15',
                'M' => '2027-02-20',
                'N' => '12.5', // 45,000 seconds
                'O' => '8',    // 28,800 seconds
                'P' => 'Laravel, React',
                'Q' => $this->otherUser->email,
                'R' => 'portal.example.com',
                'S' => 'No',
            ],
            // Row 3: Update existing project (Project Flow left blank; blank optional fields preserved)
            [
                'A' => $existingToUpdate->project_code,
                'B' => 'Updated Project Title',
                'E' => 'urgent',
                'F' => $this->statusInProgress->code,
                'G' => $this->stageExecution->code,
                'H' => $this->serviceSeo->code,
                'I' => $this->categoryApp->code,
                'J' => 'Updated Original Name',
                'K' => '2026-01-05',
                'L' => '2026-02-10',
                'N' => '20',
                'P' => 'React',
            ],
            // Row 4: Unchanged row (only name + priority matching existing values)
            [
                'A' => $unchangedProject->project_code,
                'B' => 'Unchanged Project',
                'E' => 'medium',
            ],
            // Row 5: Unknown Project Code -> must fail and NOT create a project
            [
                'A' => 'PRJ-DOES-NOT-EXIST',
                'B' => 'Should Fail',
            ],
            // Row 6: Project Flow supplied on Update -> must fail
            [
                'A' => $existingToUpdate->project_code,
                'B' => 'Duplicate Code Wait',
                'D' => 'linear',
            ],
        ]);

        // Test row 6 as a duplicate project code AND test non-duplicate Project Flow restriction cleanly:
        // Replace row 6 with another project to test Project Flow error independently
        $anotherProject = app(\App\Services\ProjectServices::class)->create([
            'name' => 'Flow Protected Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'linear',
            'priority' => 'medium',
        ], $this->creatorUser);
        @unlink($workbookPath);

        $workbookPath = $this->createWorkbook([
            // Row 2: Create
            [
                'B' => 'Created Corporate Portal',
                'C' => $this->customer->customer_code,
                'D' => 'agile',
                'E' => 'high',
                'F' => $this->statusInProgress->code,
                'G' => $this->stageExecution->code,
                'H' => $this->serviceWeb->code . ', ' . $this->serviceSeo->code,
                'I' => $this->categoryWeb->code . ', ' . $this->categoryApp->code,
                'J' => 'Phase 1 Original',
                'K' => '2026-11-15',
                'L' => '2027-02-15',
                'M' => '2027-02-20',
                'N' => '12.5',
                'O' => '8',
                'P' => 'Laravel, React',
                'Q' => $this->otherUser->email,
                'R' => 'portal.example.com',
                'S' => 'No',
            ],
            // Row 3: Update
            [
                'A' => $existingToUpdate->project_code,
                'B' => 'Updated Project Title',
                'E' => 'urgent',
                'F' => $this->statusInProgress->code,
                'G' => $this->stageExecution->code,
                'H' => $this->serviceSeo->code,
                'I' => $this->categoryApp->code,
                'J' => 'Updated Original Name',
                'K' => '2026-01-05',
                'L' => '2026-02-10',
                'N' => '20',
                'P' => 'React',
            ],
            // Row 4: Unchanged
            [
                'A' => $unchangedProject->project_code,
                'B' => 'Unchanged Project',
                'E' => 'medium',
            ],
            // Row 5: Unknown Project Code
            [
                'A' => 'PRJ-DOES-NOT-EXIST',
                'B' => 'Should Fail',
            ],
            // Row 6: Project Flow supplied on Update -> must fail
            [
                'A' => $anotherProject->project_code,
                'B' => 'Attempt Flow Change',
                'D' => 'linear',
            ],
            // Row 7: Duplicate Project Code in same file -> must fail
            [
                'A' => $existingToUpdate->project_code,
                'B' => 'Duplicate Update Attempt',
            ],
        ]);

        try {
            $meta = $this->dispatchTrackedImport($workbookPath, $this->creatorUser);
        } finally {
            @unlink($workbookPath);
        }

        $this->assertSame(ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS, $meta['status']);
        $this->assertSame(6, $meta['total_rows']);
        $this->assertSame(6, $meta['processed_rows']);
        $this->assertSame(1, $meta['created_rows']);
        $this->assertSame(1, $meta['updated_rows']);
        $this->assertSame(1, $meta['unchanged_rows']);
        $this->assertSame(3, $meta['failed_rows']);

        // Verify Row 2 Created Project
        $row2 = $meta['row_results'][0];
        $this->assertSame('created', $row2['status']);
        $createdProject = Project::where('project_code', $row2['project_code'])->first();
        $this->assertNotNull($createdProject);
        $this->assertSame('Created Corporate Portal', $createdProject->name);
        $this->assertSame($this->customer->id, $createdProject->customer_id);
        $this->assertSame('agile', $createdProject->project_flow);
        $this->assertSame('high', $createdProject->priority);
        $this->assertSame($this->statusInProgress->id, $createdProject->status_id);
        $this->assertSame($this->stageExecution->id, $createdProject->project_stage_id);
        $this->assertSame(45000, $createdProject->estimated_time_seconds);
        $this->assertSame(28800, $createdProject->customer_estimate_seconds);
        $this->assertSame($this->otherUser->id, $createdProject->sales_person_id);
        $this->assertSame('portal.example.com', $createdProject->domain);
        $this->assertFalse($createdProject->default_billable);
        $this->assertSame($this->creatorUser->id, $createdProject->added_by);
        $this->assertEqualsCanonicalizing([$this->categoryWeb->id, $this->categoryApp->id], $createdProject->project_category_ids);
        $this->assertEqualsCanonicalizing([$this->serviceWeb->id, $this->serviceSeo->id], $createdProject->serviceTypes->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$this->techLaravel->id, $this->techReact->id], $createdProject->technologies->pluck('id')->all());

        // Original Timeline created with initiating user
        $createdOriginalTimeline = $createdProject->projectTimelines()->where('type', 'original')->first();
        $this->assertNotNull($createdOriginalTimeline);
        $this->assertSame('Phase 1 Original', $createdOriginalTimeline->name);
        $this->assertSame(45000, $createdOriginalTimeline->estimated_time_seconds);
        $this->assertSame(28800, $createdOriginalTimeline->customer_estimate_seconds);
        $this->assertSame($this->creatorUser->id, $createdOriginalTimeline->created_by);

        // Verify Row 3 Updated Project
        $row3 = $meta['row_results'][1];
        $this->assertSame('updated', $row3['status']);
        $existingToUpdate->refresh();
        $this->assertSame('Updated Project Title', $existingToUpdate->name);
        $this->assertSame('linear', $existingToUpdate->project_flow);
        $this->assertSame('urgent', $existingToUpdate->priority);
        $this->assertSame($this->customer->id, $existingToUpdate->customer_id); // preserved blank
        $this->assertTrue($existingToUpdate->default_billable); // preserved blank
        $this->assertSame($this->creatorUser->id, $existingToUpdate->updated_by);
        $this->assertSame(72000, $existingToUpdate->estimated_time_seconds);
        $this->assertSame([$this->serviceSeo->id], $existingToUpdate->serviceTypes->pluck('id')->all());
        $this->assertSame([$this->techReact->id], $existingToUpdate->technologies->pluck('id')->all());
        $this->assertSame([$this->categoryApp->id], $existingToUpdate->project_category_ids);

        // Status & Stage histories created with initiating user
        $this->assertDatabaseHas('project_status_histories', [
            'project_id' => $existingToUpdate->id,
            'status_id' => $this->statusInProgress->id,
            'added_by' => $this->creatorUser->id,
        ]);
        $this->assertDatabaseHas('project_stage_histories', [
            'project_id' => $existingToUpdate->id,
            'stage_id' => $this->stageExecution->id,
            'added_by' => $this->creatorUser->id,
        ]);

        // Only 1 Original Timeline exists and was updated
        $this->assertSame(1, $existingToUpdate->projectTimelines()->count());
        $updatedTimeline = $existingToUpdate->projectTimelines()->first();
        $this->assertSame('Updated Original Name', $updatedTimeline->name);
        $this->assertSame('2026-01-05', $updatedTimeline->start_date->toDateString());
        $this->assertSame('2026-02-10', $updatedTimeline->end_date->toDateString());
        $this->assertSame(72000, $updatedTimeline->estimated_time_seconds);

        // Verify Row 4 Unchanged
        $this->assertSame('unchanged', $meta['row_results'][2]['status']);

        // Verify Row 5 Failed (unknown project code did not create any project)
        $this->assertSame('failed', $meta['row_results'][3]['status']);
        $this->assertNull(Project::where('project_code', 'PRJ-DOES-NOT-EXIST')->first());

        // Verify Row 6 Failed (Project Flow supplied on update)
        $this->assertSame('failed', $meta['row_results'][4]['status']);
        $this->assertStringContainsString('Project Flow must be blank', $meta['row_results'][4]['message']);
        $anotherProject->refresh();
        $this->assertSame('Flow Protected Project', $anotherProject->name);

        // Verify Row 7 Failed (Duplicate Project Code in file)
        $this->assertSame('failed', $meta['row_results'][5]['status']);
        $this->assertStringContainsString('Duplicate Project Code', $meta['row_results'][5]['message']);
    }

    public function test_update_protects_completed_timeline_overlapping_timelines_and_unauthorized_projects(): void
    {
        // Project owned by creatorUser, so otherUser cannot update it via ProjectPolicy
        $protectedProject = app(\App\Services\ProjectServices::class)->create([
            'name' => 'Policy Protected Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ], $this->creatorUser);

        // Add a second planned timeline (2026-06-01 to 2026-06-30) to test overlap check
        ProjectTimeline::create([
            'project_id' => $protectedProject->id,
            'name' => 'Renewal 1',
            'type' => 'renewal',
            'status' => ProjectTimeline::STATUS_PLANNED,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'sort_order' => 2,
            'created_by' => $this->creatorUser->id,
        ]);

        // Project with completed Original Timeline
        $completedTimelineProject = app(\App\Services\ProjectServices::class)->create([
            'name' => 'Completed Timeline Project',
            'customer_id' => $this->customer->id,
            'project_flow' => 'agile',
            'priority' => 'medium',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ], $this->creatorUser);
        $completedTimelineProject->projectTimelines()->where('type', 'original')->update([
            'status' => ProjectTimeline::STATUS_COMPLETED,
        ]);

        // 1. otherUser (lacks policy access to creatorUser's project, and lacks project.create)
        $unauthWorkbook = $this->createWorkbook([
            ['A' => $protectedProject->project_code, 'B' => 'Unauthorized Rename'],
            ['B' => 'Unauthorized Create', 'C' => $this->customer->customer_code, 'D' => 'agile', 'E' => 'low'],
        ]);

        try {
            $unauthMeta = $this->dispatchTrackedImport($unauthWorkbook, $this->otherUser);
        } finally {
            @unlink($unauthWorkbook);
        }

        $this->assertSame(ProjectImportTrackingManager::STATUS_FAILED, $unauthMeta['status']);
        $this->assertSame(2, $unauthMeta['failed_rows']);
        $this->assertStringContainsString('not authorized', $unauthMeta['row_results'][0]['message']);
        $this->assertStringContainsString('permission', $unauthMeta['row_results'][1]['message']);

        // 2. creatorUser tries to overlap Renewal 1 and modify completed Original Timeline
        $timelineWorkbook = $this->createWorkbook([
            ['A' => $protectedProject->project_code, 'L' => '2026-06-15'],
            ['A' => $completedTimelineProject->project_code, 'L' => '2026-02-15'],
        ]);

        try {
            $tlMeta = $this->dispatchTrackedImport($timelineWorkbook, $this->creatorUser);
        } finally {
            @unlink($timelineWorkbook);
        }

        $this->assertSame(ProjectImportTrackingManager::STATUS_FAILED, $tlMeta['status']);
        $this->assertStringContainsString('overlaps', $tlMeta['row_results'][0]['message']);
        $this->assertStringContainsString('Completed or cancelled Original Timeline', $tlMeta['row_results'][1]['message']);
    }

    public function test_master_data_rejects_unknown_codes_technologies_and_unauthorized_salesperson(): void
    {
        $workbook = $this->createWorkbook([
            [
                'B' => 'Invalid Master Project',
                'C' => 'UNKNOWN-CUST',
                'D' => 'agile',
                'E' => 'high',
                'F' => 'unknown_status_code',
                'G' => 'unknown_stage_code',
                'H' => $this->serviceWeb->code . ', pst-000000',
                'I' => 'pct-000000',
                'N' => '-5',
                'P' => 'NonExistentTechXYZ',
                'Q' => 'nobody@nonexistent.example.com',
                'S' => 'Maybe',
            ],
        ]);

        try {
            $meta = $this->dispatchTrackedImport($workbook, $this->creatorUser);
        } finally {
            @unlink($workbook);
        }

        $this->assertSame(ProjectImportTrackingManager::STATUS_FAILED, $meta['status']);
        $msg = $meta['row_results'][0]['message'];
        $this->assertStringContainsString('Customer Code', $msg);
        $this->assertStringContainsString('Project Status Code', $msg);
        $this->assertStringContainsString('Project Stage Code', $msg);
        $this->assertStringContainsString('Service Type Codes', $msg);
        $this->assertStringContainsString('Category Codes', $msg);
        $this->assertStringContainsString('Estimated Hours', $msg);
        $this->assertStringContainsString('Technologies', $msg);
        $this->assertStringContainsString('Sales Person Email', $msg);
        $this->assertStringContainsString('Default Billable', $msg);
        $this->assertNull(Project::where('name', 'Invalid Master Project')->first());
    }

    public function test_idempotency_guard_skips_already_processed_rows_on_replay(): void
    {
        $workbook = $this->createWorkbook([
            [
                'B' => 'Idempotent Project Alpha',
                'C' => $this->customer->customer_code,
                'D' => 'agile',
                'E' => 'medium',
            ],
        ]);

        $trackingManager = app(ProjectImportTrackingManager::class);
        $importId = (string) Str::uuid();
        $storedPath = $trackingManager->getImportDirectory($importId) . '/source.xlsx';
        Storage::disk(ProjectImportTrackingManager::DISK)->put($storedPath, file_get_contents($workbook));

        $trackingManager->initTracking($importId, 'projects', 'idempotent.xlsx', $storedPath, $this->creatorUser->id);

        try {
            app(ProjectBulkImportService::class)->process($importId);
            $countAfterFirst = Project::where('name', 'Idempotent Project Alpha')->count();
            $this->assertSame(1, $countAfterFirst);

            // Simulate status being reset to processing and re-processed
            $trackingManager->updateTracking($importId, ['status' => ProjectImportTrackingManager::STATUS_PROCESSING]);
            app(ProjectBulkImportService::class)->process($importId);
            $countAfterSecond = Project::where('name', 'Idempotent Project Alpha')->count();
            $this->assertSame(1, $countAfterSecond);
        } finally {
            @unlink($workbook);
            $trackingManager->deleteTrackingDirectory($importId);
        }
    }
}

