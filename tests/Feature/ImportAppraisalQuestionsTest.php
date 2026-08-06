<?php

namespace Tests\Feature;

use App\Jobs\ImportAppraisalQuestionsJob;
use App\Models\AppraisalCategory;
use App\Models\AppraisalQuestion;
use App\Models\AppraisalQuestionImport;
use App\Models\User;
use App\Services\AppraisalSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportAppraisalQuestionsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;
    protected AppraisalSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->regularUser = User::factory()->create(['is_super_admin' => false]);
        $this->service = app(AppraisalSettingsService::class);
    }

    public function test_authorized_user_can_upload_valid_xlsx_file_and_dispatch_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->create('appraisal_questions.xlsx', 500, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->postJson(route('settings.appraisal.importQuestions'), [
            'file' => $file,
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'status' => true,
            'message' => 'The appraisal question import has been queued and will be processed in the background.',
        ]);

        $import = AppraisalQuestionImport::latest('id')->first();
        $this->assertNotNull($import);
        $this->assertEquals('appraisal_questions.xlsx', $import->file_name);
        $this->assertEquals(AppraisalQuestionImport::STATUS_PENDING, $import->status);

        Storage::disk('local')->assertExists($import->file_path);
        $this->assertFalse(str_contains($import->file_path, 'storage/app/'));
        Queue::assertPushed(ImportAppraisalQuestionsJob::class, fn ($job) => $job->importId === $import->id);
    }

    public function test_missing_file_throws_file_not_found_exception(): void
    {
        Storage::fake('local');

        $import = AppraisalQuestionImport::create([
            'file_name' => 'non_existent.xlsx',
            'file_path' => 'appraisal-question-imports/non_existent.xlsx',
            'status' => AppraisalQuestionImport::STATUS_PENDING,
        ]);

        $this->expectException(\Illuminate\Contracts\Filesystem\FileNotFoundException::class);
        $this->service->processQuestionImport($import->id);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');

        $response = $this->postJson(route('settings.appraisal.importQuestions'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_missing_file_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('settings.appraisal.importQuestions'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_chunk_processing_inserts_questions_and_resolves_category_by_code(): void
    {
        $category1 = AppraisalCategory::create(['name' => 'Technical Category']);
        $category2 = AppraisalCategory::create(['name' => 'Soft Skills Category']);

        $import = AppraisalQuestionImport::create([
            'file_name' => 'test_import.xlsx',
            'file_path' => 'appraisal-question-imports/test.xlsx',
            'status' => AppraisalQuestionImport::STATUS_PROCESSING,
        ]);

        $rows = [
            [
                'category_code' => "  {$category1->code}  ",
                'question' => 'How well does the developer write code?',
                'question_type' => 'RATING',
                'measurement_type' => null,
                'target_value' => null,
                'unit' => null,
                'sort_order' => 1,
                'is_active' => 'true',
            ],
            [
                'category_code' => $category2->code,
                'question' => 'Achieved sales target percentage',
                'question_type' => 'TARGET',
                'measurement_type' => 'PERCENTAGE',
                'target_value' => 95.5,
                'unit' => '%',
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'category_code' => $category1->code,
                'question' => 'Describe your team contributions',
                'question_type' => 'answer',
                'measurement_type' => 'number', // Should be cleared to null
                'target_value' => 100, // Should be cleared to null
                'unit' => null,
                'sort_order' => null,
                'is_active' => true,
            ],
        ];

        $this->service->processQuestionChunk($import, $rows, 2);

        $import->refresh();
        $this->assertEquals(3, $import->inserted_rows);
        $this->assertEquals(0, $import->failed_rows);

        $q1 = AppraisalQuestion::where('question', 'How well does the developer write code?')->first();
        $this->assertNotNull($q1);
        $this->assertEquals($category1->id, $q1->appraisal_category_id);
        $this->assertEquals(AppraisalQuestion::QUESTION_TYPE_RATING, $q1->question_type);
        $this->assertTrue((bool) $q1->is_active);

        $q2 = AppraisalQuestion::where('question', 'Achieved sales target percentage')->first();
        $this->assertNotNull($q2);
        $this->assertEquals($category2->id, $q2->appraisal_category_id);
        $this->assertEquals(AppraisalQuestion::QUESTION_TYPE_TARGET, $q2->question_type);
        $this->assertEquals(AppraisalQuestion::MEASUREMENT_TYPE_PERCENTAGE, $q2->measurement_type);
        $this->assertEquals(95.50, (float) $q2->target_value);

        $q3 = AppraisalQuestion::where('question', 'Describe your team contributions')->first();
        $this->assertNotNull($q3);
        $this->assertNull($q3->measurement_type);
        $this->assertNull($q3->target_value);
    }

    public function test_unknown_or_soft_deleted_category_code_records_row_error(): void
    {
        $deletedCategory = AppraisalCategory::create(['name' => 'Archived Category']);
        $deletedCode = $deletedCategory->code;
        $deletedCategory->delete();

        $import = AppraisalQuestionImport::create([
            'file_name' => 'test_import_errors.xlsx',
            'file_path' => 'appraisal-question-imports/test_errors.xlsx',
            'status' => AppraisalQuestionImport::STATUS_PROCESSING,
        ]);

        $rows = [
            [
                'category_code' => 'APC-UNKNOWN1',
                'question' => 'Test Question 1',
                'question_type' => 'rating',
            ],
            [
                'category_code' => $deletedCode,
                'question' => 'Test Question 2',
                'question_type' => 'rating',
            ],
        ];

        $this->service->processQuestionChunk($import, $rows, 2);

        $import->refresh();
        $this->assertEquals(0, $import->inserted_rows);
        $this->assertEquals(2, $import->failed_rows);
        $this->assertNotNull($import->error_report_path);

        $errorContent = file_get_contents(storage_path('app/' . $import->error_report_path));
        $this->assertStringContainsString("Row 2: Category code 'APC-UNKNOWN1' does not exist.", $errorContent);
        $this->assertStringContainsString("Row 3: Category code '{$deletedCode}' does not exist.", $errorContent);
    }

    public function test_duplicate_question_in_same_category_is_skipped(): void
    {
        $category = AppraisalCategory::create(['name' => 'Quality Category']);

        AppraisalQuestion::create([
            'appraisal_category_id' => $category->id,
            'question' => 'Existing Unique Question',
            'question_type' => 'rating',
        ]);

        $import = AppraisalQuestionImport::create([
            'file_name' => 'test_duplicates.xlsx',
            'file_path' => 'appraisal-question-imports/test_dup.xlsx',
            'status' => AppraisalQuestionImport::STATUS_PROCESSING,
        ]);

        $rows = [
            [
                'category_code' => $category->code,
                'question' => 'Existing Unique Question',
                'question_type' => 'rating',
            ],
            [
                'category_code' => $category->code,
                'question' => 'New Import Question',
                'question_type' => 'rating',
            ],
            [
                'category_code' => $category->code,
                'question' => 'New Import Question',
                'question_type' => 'rating',
            ],
        ];

        $this->service->processQuestionChunk($import, $rows, 2);

        $import->refresh();
        $this->assertEquals(1, $import->inserted_rows);
        $this->assertEquals(2, $import->skipped_rows);
    }

    public function test_temporary_file_is_deleted_after_job_processing(): void
    {
        Storage::fake('local');

        $filePath = 'appraisal-question-imports/temp_test.xlsx';
        Storage::disk('local')->put($filePath, 'dummy_content');

        $import = AppraisalQuestionImport::create([
            'file_name' => 'temp_test.xlsx',
            'file_path' => $filePath,
            'status' => AppraisalQuestionImport::STATUS_PENDING,
        ]);

        Storage::disk('local')->assertExists($filePath);

        $this->service->deleteQuestionImportFile($import->id);

        Storage::disk('local')->assertMissing($filePath);
    }
}
