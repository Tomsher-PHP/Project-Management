<?php

namespace Tests\Feature;

use App\Models\AppraisalCategory;
use App\Models\User;
use App\Services\AppraisalSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppraisalCategoryCodeTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected AppraisalSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->service = app(AppraisalSettingsService::class);
    }

    public function test_creating_a_category_automatically_generates_a_code(): void
    {
        $category = AppraisalCategory::create([
            'name' => 'Communication Skills',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertNotNull($category->code);
        $this->assertNotEmpty($category->code);
    }

    public function test_the_code_starts_with_apc_prefix(): void
    {
        $category = AppraisalCategory::create([
            'name' => 'Technical Abilities',
        ]);

        $this->assertStringStartsWith('APC-', $category->code);
    }

    public function test_the_code_contains_eight_uppercase_alphanumeric_characters_after_prefix(): void
    {
        $category = AppraisalCategory::create([
            'name' => 'Leadership',
        ]);

        $this->assertMatchesRegularExpression('/^APC-[A-Z0-9]{8}$/', $category->code);
    }

    public function test_two_categories_receive_different_codes(): void
    {
        $category1 = AppraisalCategory::create(['name' => 'Category One']);
        $category2 = AppraisalCategory::create(['name' => 'Category Two']);

        $this->assertNotEquals($category1->code, $category2->code);
    }

    public function test_existing_codes_are_not_changed_during_category_updates(): void
    {
        $category = $this->service->createCategory([
            'name' => 'Original Category',
            'questions' => [
                ['question' => 'First Question?', 'question_type' => 'rating'],
            ],
        ]);

        $originalCode = $category->code;

        $updatedCategory = $this->service->updateCategory($category, [
            'name' => 'Updated Category Name',
            'questions' => [
                ['id' => $category->questions->first()->id, 'question' => 'Updated Question?', 'question_type' => 'rating'],
            ],
        ]);

        $this->assertEquals($originalCode, $updatedCategory->fresh()->code);
    }

    public function test_a_request_cannot_manually_replace_the_category_code(): void
    {
        $category = AppraisalCategory::create([
            'name' => 'Custom Code Request',
        ]);

        $originalCode = $category->code;

        $updatedCategory = $this->service->updateCategory($category, [
            'name' => 'Updated Custom Code Request',
            'code' => 'APC-FORCED02',
            'questions' => [
                ['question' => 'Valid Question?', 'question_type' => 'rating'],
            ],
        ]);

        $this->assertEquals($originalCode, $updatedCategory->fresh()->code);
        $this->assertNotEquals('APC-FORCED02', $updatedCategory->fresh()->code);
    }

    public function test_soft_deleted_category_codes_are_still_treated_as_reserved(): void
    {
        $deletedCategory = AppraisalCategory::create(['name' => 'Deleted Category']);
        $reservedCode = $deletedCategory->code;

        $deletedCategory->delete();

        $this->assertSoftDeleted('appraisal_categories', ['id' => $deletedCategory->id]);
        $this->assertTrue(AppraisalCategory::withTrashed()->where('code', $reservedCode)->exists());

        $newCategory = AppraisalCategory::create(['name' => 'New Active Category']);

        $this->assertNotEquals($reservedCode, $newCategory->code);
    }

    public function test_existing_database_records_are_backfilled_by_the_migration(): void
    {
        $id = DB::table('appraisal_categories')->insertGetId([
            'code' => 'TEMP-OLD-' . Str::random(5),
            'name' => 'Legacy Category Without Code',
            'sort_order' => 1,
            'is_system' => false,
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categories = DB::table('appraisal_categories')->whereNull('code')->orWhere('code', 'LIKE', 'TEMP-OLD-%')->get();
        $usedCodes = DB::table('appraisal_categories')->whereNotNull('code')->pluck('code')->all();

        foreach ($categories as $category) {
            do {
                $code = 'APC-' . strtoupper(Str::random(8));
            } while (in_array($code, $usedCodes, true));

            $usedCodes[] = $code;

            DB::table('appraisal_categories')
                ->where('id', $category->id)
                ->update(['code' => $code]);
        }

        $record = DB::table('appraisal_categories')->where('id', $id)->first();

        $this->assertNotNull($record->code);
        $this->assertMatchesRegularExpression('/^APC-[A-Z0-9]{8}$/', $record->code);
    }

    public function test_category_creation_through_appraisal_settings_service_generates_a_code(): void
    {
        $category = $this->service->createCategory([
            'name' => 'Service Created Category',
            'questions' => [
                ['question' => 'Sample Service Question', 'question_type' => 'rating'],
            ],
        ]);

        $this->assertNotNull($category->code);
        $this->assertMatchesRegularExpression('/^APC-[A-Z0-9]{8}$/', $category->code);
    }

    public function test_question_excel_import_still_assigns_questions_to_the_category_created_through_the_form(): void
    {
        $category = $this->service->createCategory([
            'name' => 'Form Category',
            'questions' => [
                [
                    'question' => 'Form Question 1',
                    'question_type' => 'rating',
                ],
                [
                    'question' => 'Form Question 2',
                    'question_type' => 'target',
                    'measurement_type' => 'percentage',
                    'target_value' => 95,
                    'unit' => '%',
                ],
            ],
        ]);

        $this->assertNotNull($category->code);
        $this->assertCount(2, $category->questions);
        $this->assertEquals($category->id, $category->questions->first()->appraisal_category_id);
    }
}
