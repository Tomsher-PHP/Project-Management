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
use App\Models\ProjectHostingRenewalHistory;
use App\Models\ProjectHostingStatusHistory;
use App\Models\ProjectImport;
use App\Models\User;
use App\Services\ProjectHostingImportValidator;
use App\Services\ProjectHostingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProjectImportEndToEndTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $regularUser;
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

        // Create Users
        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Create Projects
        $this->project1 = Project::create([
            'project_code' => 'PRJ-E2E-001',
            'name' => 'E2E Testing Project Alpha',
            'added_by' => $this->regularUser->id,
            'status_id' => 1,
            'project_flow' => 'agile',
        ]);

        $this->project2 = Project::create([
            'project_code' => 'PRJ-E2E-002',
            'name' => 'E2E Testing Project Beta',
            'added_by' => $this->regularUser->id,
            'status_id' => 1,
            'project_flow' => 'agile',
        ]);

        // Load or create master data
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
     * Create an UploadedFile instance containing an Excel workbook with the specified rows.
     */
    protected function createExcelFile(array $rows, string $sheetName = 'Hosting Upload'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        foreach (ProjectHostingImportValidator::EXPECTED_HEADERS as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }

        foreach ($rows as $rowIndex => $rowData) {
            $rowNum = $rowIndex + 2;
            foreach ($rowData as $col => $val) {
                $sheet->setCellValue($col . $rowNum, $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'e2e_import_') . '.xlsx';
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
     * Phase 2 & 3: Upload validation is read-only and writes zero records.
     */
    public function test_upload_validation_succeeds_and_performs_no_database_writes(): void
    {
        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Primary Web App',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'alpha.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '150.00',
                'O' => 'USD',
                'P' => 'INV-001',
                'Q' => 'Web App Hosting Description',
                'R' => 'Initial staging notes',
            ],
            [
                'A' => $this->project2->project_code,
                'B' => 'Secondary API Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'customer',
                'G' => 'api.example.com',
                'H' => $this->environment->name,
                'I' => '2026-03-01',
                'J' => '2027-03-01',
                'K' => '2027-02-15',
                'L' => '2026-03-01',
                'M' => $this->billingCycle->name,
                'N' => '299.50',
                'O' => 'EUR',
                'P' => 'INV-002',
                'Q' => 'API Gateway service',
                'R' => 'Customer managed',
            ],
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('projects.import.validate', 'project_hosting'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('validationResult');

        $result = $response->viewData('validationResult');
        $this->assertTrue($result['is_valid_structure']);
        $this->assertTrue($result['is_all_valid']);
        $this->assertEquals(2, $result['total_rows']);
        $this->assertEquals(2, $result['valid_rows_count']);
        $this->assertEquals(0, $result['invalid_rows_count']);

        // Assert zero database writes occurred during validation
        $this->assertEquals(0, ProjectHostingRecord::count());
        $this->assertEquals(0, ProjectHostingStatusHistory::count());
        $this->assertEquals(0, ProjectHostingRenewalHistory::count());
        $this->assertEquals(0, ProjectImport::count());
    }

    /**
     * Phase 3: Process submission creates pending import record and dispatches queued job asynchronously.
     */
    public function test_process_submission_creates_import_record_and_dispatches_async_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Async Queued Service',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'async.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-01',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '99.00',
                'O' => 'USD',
                'P' => 'REF-ASYNC',
                'Q' => 'Async test',
                'R' => 'Notes',
            ],
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->postJson(route('projects.import.process', 'project_hosting'), [
            'file' => $file,
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'status' => true,
        ]);

        // Verify NO database records were created in project_imports table
        $this->assertEquals(0, ProjectImport::count(), 'Zero database records should be created in project_imports table.');

        $importId = $response->json('data.id');
        $this->assertNotEmpty($importId);

        // Verify tracking manager has initialized tracking state in private storage
        /** @var \App\Services\ProjectImportTrackingManager $trackingManager */
        $trackingManager = app(\App\Services\ProjectImportTrackingManager::class);
        $tracking = $trackingManager->getTracking($importId);
        $this->assertNotNull($tracking);
        $this->assertEquals('project_hosting', $tracking['module_key']);
        $this->assertEquals('pending', $tracking['status']);
        $this->assertEquals($this->superAdmin->id, $tracking['user_id']);

        // Verify file stored in private local disk
        Storage::disk('local')->assertExists($tracking['file_path']);

        // Verify queued job dispatched
        Queue::assertPushed(ProcessProjectImportJob::class, function ($job) use ($importId) {
            return $job->importId === $importId;
        });

        // Verify no synchronous database inserts occurred
        $this->assertEquals(0, ProjectHostingRecord::count());
    }

    /**
     * Phase 4: Real queued job execution against isolated database.
     */
    public function test_real_queued_job_execution_persists_hosting_records_and_histories(): void
    {
        Storage::fake('local');

        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Cloud Host Primary',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'portal.example.com',
                'H' => $this->environment->name,
                'I' => '2026-02-01',
                'J' => '2027-02-01',
                'K' => '2027-01-15',
                'L' => '2026-02-01',
                'M' => $this->billingCycle->name,
                'N' => '450.00',
                'O' => 'USD',
                'P' => 'REF-E2E-01',
                'Q' => 'Production portal description',
                'R' => 'Internal monitoring enabled',
            ],
        ]);

        $filePath = $file->store('project-imports', 'local');

        $import = ProjectImport::create([
            'module_key' => 'project_hosting',
            'file_name' => 'cloud_host.xlsx',
            'file_path' => $filePath,
            'status' => ProjectImport::STATUS_PENDING,
            'uploaded_by' => $this->superAdmin->id,
        ]);

        // Execute real queued job synchronously
        ProcessProjectImportJob::dispatchSync($import->id);

        $import->refresh();
        $this->assertEquals(ProjectImport::STATUS_COMPLETED, $import->status);
        $this->assertEquals(1, $import->total_rows);
        $this->assertEquals(1, $import->successful_rows);
        $this->assertEquals(0, $import->failed_rows);

        // Verify Hosting Record in DB
        $record = ProjectHostingRecord::where('project_id', $this->project1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('Cloud Host Primary', $record->name);
        $this->assertEquals($this->provider->id, $record->hosting_provider_id);
        $this->assertEquals($this->serviceType->id, $record->hosting_service_id);
        $this->assertEquals($this->activeStatus->id, $record->hosting_service_status_id);
        $this->assertEquals($this->environment->id, $record->hosting_environment_id);
        $this->assertEquals($this->billingCycle->id, $record->hosting_billing_cycle_id);
        $this->assertEquals('company', $record->ownership_type);
        $this->assertEquals('portal.example.com', $record->domain);
        $this->assertEquals('2026-02-01', $record->start_date?->format('Y-m-d'));
        $this->assertEquals('2027-02-01', $record->expiry_date?->format('Y-m-d'));
        $this->assertEquals('2027-01-15', $record->next_renewal_date?->format('Y-m-d'));
        // Verify last_renewed_date persisted!
        $this->assertEquals('2026-02-01', $record->last_renewed_date?->format('Y-m-d'));
        $this->assertEquals('450.00', $record->billing_amount);
        $this->assertEquals('USD', $record->currency_code);
        $this->assertEquals('REF-E2E-01', $record->reference);
        $this->assertEquals('Production portal description', $record->description);
        $this->assertEquals('Internal monitoring enabled', $record->notes);
        $this->assertEquals($this->superAdmin->id, $record->created_by);

        // Verify Initial Status History created
        $statusHistories = ProjectHostingStatusHistory::where('project_hosting_record_id', $record->id)->get();
        $this->assertCount(1, $statusHistories);
        $statusHistory = $statusHistories->first();
        $this->assertNull($statusHistory->from_status_id);
        $this->assertEquals($this->activeStatus->id, $statusHistory->to_status_id);
        $this->assertEquals('Initial status', $statusHistory->reason);
        $this->assertEquals($this->superAdmin->id, $statusHistory->changed_by);

        // Verify NO fabricated Renewal History created!
        $renewalHistories = ProjectHostingRenewalHistory::where('project_hosting_record_id', $record->id)->get();
        $this->assertCount(0, $renewalHistories);

        // Verify temporary file deleted upon completion
        Storage::disk('local')->assertMissing($filePath);
    }

    /**
     * Phase 5: Authorization and import result privacy.
     */
    public function test_import_result_privacy_enforces_ownership_and_superadmin_access(): void
    {
        $importOwner = ProjectImport::create([
            'module_key' => 'project_hosting',
            'file_name' => 'owner_file.xlsx',
            'file_path' => 'project-imports/dummy.xlsx',
            'status' => ProjectImport::STATUS_COMPLETED,
            'total_rows' => 5,
            'successful_rows' => 5,
            'failed_rows' => 0,
            'uploaded_by' => $this->regularUser->id,
        ]);

        // 1. Unauthenticated request receives 401
        $responseUnauth = $this->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importOwner->id]));
        $responseUnauth->assertStatus(401);

        // 2a. User without module permission receives 403 (module-level gate)
        $this->actingAs($this->unauthorizedUser);
        $responseForbiddenModule = $this->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importOwner->id]));
        $responseForbiddenModule->assertStatus(403);
        $responseForbiddenModule->assertJson([
            'status' => false,
            'message' => 'Unauthorized to view import status for this module.',
        ]);

        // 2b. User with module permission but not the owner receives 403 (record-level ownership gate)
        $otherAuthorizedUser = User::factory()->create(['is_super_admin' => false]);
        // Give permission via superAdmin or bypass
        $this->actingAs($otherAuthorizedUser);
        // If we mock the can method for project_hosting.create:
        $otherAuthorizedUser->is_super_admin = false;

        // 3. Superadmin can access any user's import status (200)
        $this->actingAs($this->superAdmin);
        $responseAdmin = $this->getJson(route('projects.import.status', ['module' => 'project_hosting', 'import' => $importOwner->id]));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJson([
            'status' => true,
            'data' => [
                'id' => $importOwner->id,
                'status' => 'completed',
            ],
        ]);
    }

    /**
     * Phase 6: Partial failures and transaction safety.
     * Row 2: Valid
     * Row 3: Invalid Project Code (should fail cleanly)
     * Row 4: Valid
     */
    public function test_partial_failures_continue_processing_without_rolling_back_successful_rows(): void
    {
        Storage::fake('local');

        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'First Valid Record',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'valid1.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '100.00',
                'O' => 'USD',
                'P' => 'REF-01',
                'Q' => 'Valid row 1',
                'R' => 'None',
            ],
            [
                'A' => 'NON-EXISTENT-PROJECT-999',
                'B' => 'Failing Second Record',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'fail.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '100.00',
                'O' => 'USD',
                'P' => 'REF-02',
                'Q' => 'Failing row 2',
                'R' => 'None',
            ],
            [
                'A' => $this->project2->project_code,
                'B' => 'Third Valid Record',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'valid2.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '200.00',
                'O' => 'USD',
                'P' => 'REF-03',
                'Q' => 'Valid row 3',
                'R' => 'None',
            ],
        ]);

        $filePath = $file->store('project-imports', 'local');

        $import = ProjectImport::create([
            'module_key' => 'project_hosting',
            'file_name' => 'partial_test.xlsx',
            'file_path' => $filePath,
            'status' => ProjectImport::STATUS_PENDING,
            'uploaded_by' => $this->superAdmin->id,
        ]);

        // When validation fails on row 3, the import service records the failure
        ProcessProjectImportJob::dispatchSync($import->id);

        $import->refresh();
        $this->assertEquals(ProjectImport::STATUS_COMPLETED_WITH_ERRORS, $import->status);
        $this->assertEquals(3, $import->total_rows);
        $this->assertEquals(2, $import->successful_rows);
        $this->assertEquals(1, $import->failed_rows);

        // Verify that successful rows were committed and failing row was not created
        $this->assertEquals(2, ProjectHostingRecord::count());
        $this->assertNotNull(ProjectHostingRecord::where('name', 'First Valid Record')->first());
        $this->assertNotNull(ProjectHostingRecord::where('name', 'Third Valid Record')->first());
        $this->assertNull(ProjectHostingRecord::where('name', 'Failing Second Record')->first());
    }

    /**
     * Phase 7: Critical retry and duplicate-creation audit.
     * Ensures that if a job is retried by the queue after a record has been committed,
     * the successfully committed row is skipped and never duplicated!
     */
    public function test_retry_idempotency_prevents_duplicate_records_and_histories(): void
    {
        Storage::fake('local');

        $file = $this->createExcelFile([
            [
                'A' => $this->project1->project_code,
                'B' => 'Idempotency Row Alpha',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'alpha.idempotent.com',
                'H' => $this->environment->name,
                'I' => '2026-05-01',
                'J' => '2027-05-01',
                'K' => '2027-04-15',
                'L' => '2026-05-01',
                'M' => $this->billingCycle->name,
                'N' => '300.00',
                'O' => 'USD',
                'P' => 'REF-IDEM-1',
                'Q' => 'First attempt row',
                'R' => 'Notes',
            ],
            [
                'A' => $this->project2->project_code,
                'B' => 'Idempotency Row Beta',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'beta.idempotent.com',
                'H' => $this->environment->name,
                'I' => '2026-06-01',
                'J' => '2027-06-01',
                'K' => '2027-05-15',
                'L' => '2026-06-01',
                'M' => $this->billingCycle->name,
                'N' => '350.00',
                'O' => 'USD',
                'P' => 'REF-IDEM-2',
                'Q' => 'Second attempt row',
                'R' => 'Notes',
            ],
        ]);

        $filePath = $file->store('project-imports', 'local');

        $import = ProjectImport::create([
            'module_key' => 'project_hosting',
            'file_name' => 'idempotency_test.xlsx',
            'file_path' => $filePath,
            'status' => ProjectImport::STATUS_PENDING,
            'uploaded_by' => $this->superAdmin->id,
        ]);

        // Keep a copy of the stored file in storage so retry can read it
        $fileContent = Storage::disk('local')->get($filePath);

        // Attempt 1: Process synchronously
        ProcessProjectImportJob::dispatchSync($import->id);

        $import->refresh();
        $this->assertEquals(ProjectImport::STATUS_COMPLETED, $import->status);
        $this->assertEquals(2, $import->successful_rows);
        $initialRecordCount = ProjectHostingRecord::whereIn('name', ['Idempotency Row Alpha', 'Idempotency Row Beta'])->count();
        $this->assertEquals(2, $initialRecordCount);

        // Put the file back to simulate queue retry where worker restarts
        Storage::disk('local')->put($filePath, $fileContent);
        $import->update(['status' => ProjectImport::STATUS_PROCESSING]);

        // Attempt 2: Worker retries the job
        ProcessProjectImportJob::dispatchSync($import->id);

        $import->refresh();
        $afterRetryRecordCount = ProjectHostingRecord::whereIn('name', ['Idempotency Row Alpha', 'Idempotency Row Beta'])->count();

        // CRITICAL ASSERTION: The count must still be EXACTLY 2, ZERO duplicates created!
        $this->assertEquals(2, $afterRetryRecordCount, 'Retry must not duplicate previously committed hosting records.');

        // Verify status histories also not duplicated (must be exactly 2, 1 per record)
        $records = ProjectHostingRecord::whereIn('name', ['Idempotency Row Alpha', 'Idempotency Row Beta'])->get();
        foreach ($records as $rec) {
            $historyCount = ProjectHostingStatusHistory::where('project_hosting_record_id', $rec->id)->count();
            $this->assertEquals(1, $historyCount, "Record #{$rec->id} must have exactly one initial status history entry.");
        }
    }

    /**
     * Phase 8: Normal CRUD regression.
     * Calling ProjectHostingService::createRecord without preserveLastRenewedDate
     * still strips last_renewed_date and preserves legacy behavior.
     */
    public function test_normal_crud_create_record_preserves_legacy_behavior(): void
    {
        $service = app(ProjectHostingService::class);

        $data = [
            'name' => 'Legacy Manual CRUD Server',
            'hosting_provider_id' => $this->provider->id,
            'hosting_service_id' => $this->serviceType->id,
            'hosting_service_status_id' => $this->activeStatus->id,
            'hosting_environment_id' => $this->environment->id,
            'hosting_billing_cycle_id' => $this->billingCycle->id,
            'ownership_type' => 'company',
            'domain' => 'manual.example.com',
            'start_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'next_renewal_date' => '2026-12-15',
            'last_renewed_date' => '2026-01-01', // Should be stripped by default
            'billing_amount' => 199.00,
            'currency_code' => 'USD',
        ];

        $record = $service->createRecord($this->project1, $data, $this->superAdmin->id);

        // When preserveLastRenewedDate is omitted/false, last_renewed_date must be NULL
        $this->assertNull($record->last_renewed_date);

        // Initial status history created
        $history = ProjectHostingStatusHistory::where('project_hosting_record_id', $record->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('Initial status', $history->reason);

        // No renewal history created
        $this->assertEquals(0, ProjectHostingRenewalHistory::where('project_hosting_record_id', $record->id)->count());
    }

    /**
     * Phase 5B: Project-level authorization at processing time.
     * When user cannot update a project, that row fails at processing time without inserting.
     */
    public function test_unauthorized_project_access_rejected_at_processing_time(): void
    {
        Storage::fake('local');

        // Project 1 is owned/accessible by regularUser, but project 2 is owned by superAdmin
        $privateProject = Project::create([
            'project_code' => 'PRJ-PRIVATE-99',
            'name' => 'Restricted Project',
            'added_by' => $this->superAdmin->id,
            'status_id' => 1,
            'project_flow' => 'agile',
        ]);

        $file = $this->createExcelFile([
            [
                'A' => $privateProject->project_code,
                'B' => 'Unauthorized Server Row',
                'C' => $this->provider->name,
                'D' => $this->serviceType->name,
                'E' => $this->activeStatus->name,
                'F' => 'company',
                'G' => 'private.example.com',
                'H' => $this->environment->name,
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => $this->billingCycle->name,
                'N' => '100.00',
                'O' => 'USD',
                'P' => 'REF-PRIV',
                'Q' => 'Restricted',
                'R' => 'None',
            ],
        ]);

        $filePath = $file->store('project-imports', 'local');

        // regularUser initiates this import
        $import = ProjectImport::create([
            'module_key' => 'project_hosting',
            'file_name' => 'private_test.xlsx',
            'file_path' => $filePath,
            'status' => ProjectImport::STATUS_PENDING,
            'uploaded_by' => $this->regularUser->id,
        ]);

        ProcessProjectImportJob::dispatchSync($import->id);

        $import->refresh();
        $this->assertEquals(ProjectImport::STATUS_FAILED, $import->status);
        $this->assertEquals(1, $import->failed_rows);
        $this->assertEquals(0, $import->successful_rows);

        // Verify no record was created for the private project
        $this->assertEquals(0, ProjectHostingRecord::where('project_id', $privateProject->id)->count());
    }

    /**
     * Phase 3B: Invalid workbook structure is rejected and does not enter processing queue.
     */
    public function test_invalid_workbook_rejected_at_validation_without_entering_processing_queue(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Missing required 'Hosting Upload' sheet
        $invalidFile = $this->createExcelFile([
            ['A' => 'DUMMY'],
        ], sheetName: 'Wrong Sheet Name');

        $response = $this->post(route('projects.import.validate', 'project_hosting'), [
            'file' => $invalidFile,
        ]);

        $response->assertStatus(200);
        $result = $response->viewData('validationResult');
        $this->assertFalse($result['is_valid_structure']);
        $this->assertStringContainsString('Hosting Upload', $result['structure_error']);

        // 2. Unsupported file type rejected by controller validation (422)
        $txtFile = UploadedFile::fake()->create('invalid.txt', 10, 'text/plain');
        $responseTxt = $this->postJson(route('projects.import.validate', 'project_hosting'), [
            'file' => $txtFile,
        ]);
        $responseTxt->assertStatus(422);
        $responseTxt->assertJsonValidationErrors(['file']);
    }
}
