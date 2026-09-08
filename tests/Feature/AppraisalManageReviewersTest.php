<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\AppraisalAnswer;
use App\Models\AppraisalQuestion;
use App\Models\AppraisalReviewer;
use App\Models\Kpi;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AppraisalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppraisalManageReviewersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Grant permission for testing
        config(['permission.permissions.appraisal.create' => true]);
    }

    private function createEmployeeWithChain(): array
    {
        $topManager = User::factory()->create(['name' => 'Top Manager']);
        $middleManager = User::factory()->create(['name' => 'Middle Manager']);
        $employee = User::factory()->create(['name' => 'Employee']);
        $otherManager = User::factory()->create(['name' => 'Other Manager']);
        $unrelatedUser = User::factory()->create(['name' => 'Unrelated User']);

        UserDetail::create(['user_id' => $topManager->id, 'reporter_id' => null]);
        UserDetail::create(['user_id' => $middleManager->id, 'reporter_id' => $topManager->id]);
        UserDetail::create(['user_id' => $employee->id, 'reporter_id' => $middleManager->id]);
        UserDetail::create(['user_id' => $otherManager->id, 'reporter_id' => $topManager->id]);
        UserDetail::create(['user_id' => $unrelatedUser->id, 'reporter_id' => null]);

        $admin = User::factory()->create(['is_super_admin' => true]);
        UserDetail::create(['user_id' => $admin->id, 'reporter_id' => null]);

        $kpi = Kpi::create([
            'name' => 'General KPI',
            'description' => 'Description',
            'is_active' => true,
        ]);

        return [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi];
    }

    private function createPublishedAppraisal($employee, $kpi, array $reviewers): Appraisal
    {
        $service = app(AppraisalService::class);

        $data = [
            'month' => 9,
            'year' => 2026,
            'status' => 'published',
            'kpi_id' => $kpi->id,
            'user_ids' => [$employee->id],
            'categories' => [
                [
                    'name' => 'Performance',
                    'questions' => [
                        [
                            'question' => 'Deliverables quality',
                            'question_type' => AppraisalQuestion::QUESTION_TYPE_RATING,
                        ],
                    ],
                ],
            ],
        ];

        $service->assign($data);

        $appraisal = Appraisal::where('user_id', $employee->id)->firstOrFail();

        $reviewerAssignments = [
            'month' => 9,
            'year' => 2026,
            'assignments' => [
                [
                    'user_id' => $employee->id,
                    'reviewer_user_ids' => array_map(fn($r) => $r->id, $reviewers),
                ],
            ],
        ];

        $service->assignReviewers($reviewerAssignments);

        return $appraisal->fresh(['reviewers', 'snapshotCategories.questions']);
    }

    public function test_unstarted_reviewers_can_be_changed_and_removed_regardless_of_level(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();
        $this->actingAs($admin);

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager, $topManager]);
        $service = app(AppraisalService::class);

        $manageData = $service->getManageReviewersData($appraisal);
        $this->assertCount(2, $manageData['reviewers']);
        $this->assertTrue($manageData['reviewers'][0]['can_change']);
        $this->assertTrue($manageData['reviewers'][0]['can_remove']);
        $this->assertTrue($manageData['reviewers'][1]['can_change']);
        $this->assertTrue($manageData['reviewers'][1]['can_remove']);

        // Change Level 2 (topManager -> otherManager) while unstarted
        $reviewer2 = $appraisal->reviewers->where('level', 2)->first();
        $service->changeReviewer($appraisal, $reviewer2, $otherManager->id);

        $this->assertDatabaseHas('appraisal_reviewers', [
            'id' => $reviewer2->id,
            'reviewer_user_id' => $otherManager->id,
            'level' => 2,
        ]);

        // Remove Level 1 (middleManager) while unstarted
        $reviewer1 = $appraisal->reviewers->where('level', 1)->first();
        $service->removeReviewer($appraisal, $reviewer1);

        $remainingReviewers = $appraisal->fresh()->reviewers()->orderBy('level')->get();
        $this->assertCount(1, $remainingReviewers);
        $this->assertEquals($otherManager->id, $remainingReviewers->first()->reviewer_user_id);
        $this->assertEquals(1, $remainingReviewers->first()->level); // Re-indexed to Level 1
    }

    public function test_started_reviewer_cannot_be_changed_or_removed_backend_rejects(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager, $topManager]);
        $service = app(AppraisalService::class);

        // Employee agrees to KPI & submits answers
        $this->actingAs($employee);
        $service->agreeToKpi($appraisal);

        $question = $appraisal->snapshotCategories->first()->questions->first();
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 4.0],
        ]);

        // Middle Manager submits review (Level 1 started & completed)
        $this->actingAs($middleManager);
        $service->submitAnswers($appraisal->fresh(), [
            ['question_id' => $question->id, 'rating' => 4.5],
        ]);

        $reviewer1 = $appraisal->reviewers->where('level', 1)->first();

        $this->actingAs($admin);
        $manageData = $service->getManageReviewersData($appraisal->fresh());

        $r1Data = collect($manageData['reviewers'])->firstWhere('id', $reviewer1->id);
        $this->assertEquals('Completed', $r1Data['status']);
        $this->assertFalse($r1Data['can_change']);
        $this->assertFalse($r1Data['can_remove']);

        // Check Level 2 reviewer (topManager is active in progress or waiting)
        $reviewer2 = $appraisal->reviewers->where('level', 2)->first();
        $r2Data = collect($manageData['reviewers'])->firstWhere('id', $reviewer2->id);
        if (in_array($r2Data['status'], ['Completed', 'In Progress'], true)) {
            $this->assertFalse($r2Data['can_change']);
            $this->assertFalse($r2Data['can_remove']);
        } else {
            $this->assertTrue($r2Data['can_change']);
            $this->assertTrue($r2Data['can_remove']);
        }

        // Backend rejects change of started reviewer
        try {
            $service->changeReviewer($appraisal->fresh(), $reviewer1, $otherManager->id);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewer', $e->errors());
        }

        // Backend rejects removal of started reviewer
        try {
            $service->removeReviewer($appraisal->fresh(), $reviewer1);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewer', $e->errors());
        }
    }

    public function test_removing_unstarted_reviewer_allows_sequential_workflow_to_continue(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager, $topManager, $otherManager]);
        $service = app(AppraisalService::class);

        // Step 1: Assignee agrees to KPI & submits answers
        $this->actingAs($employee);
        $service->agreeToKpi($appraisal);
        $question = $appraisal->snapshotCategories->first()->questions->first();
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 4.0],
        ]);

        // Step 2: Level 1 (middleManager) submits review and assignee acknowledges
        $this->actingAs($middleManager);
        $service->submitAnswers($appraisal->fresh(), [
            ['question_id' => $question->id, 'rating' => 4.2],
        ]);

        $reviewer1 = $appraisal->reviewers->where('level', 1)->first();
        $this->actingAs($employee);
        $service->acknowledgeReview($appraisal->fresh(), $reviewer1->id);

        $appraisal = $appraisal->fresh();
        $this->assertEquals('Reviewer Level 2', $appraisal->current_stage);

        // Level 2 (topManager) is unstarted. Admin removes topManager (Level 2).
        $reviewer2 = $appraisal->reviewers->where('level', 2)->first();
        $this->actingAs($admin);
        $service->removeReviewer($appraisal, $reviewer2);

        // former Level 3 (otherManager) becomes Level 2 and is now the current stage!
        $appraisal = $appraisal->fresh();
        $this->assertEquals('Reviewer Level 2', $appraisal->current_stage);

        // otherManager can now submit answers!
        $this->actingAs($otherManager);
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 5.0],
        ]);

        $reviewer2New = $appraisal->reviewers()->where('reviewer_user_id', $otherManager->id)->first();
        $this->assertNotNull($reviewer2New->submitted_at);
    }

    public function test_adding_reviewer_appends_to_end_and_reopens_completed_appraisal(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager]);
        $service = app(AppraisalService::class);

        // Complete the appraisal
        $this->actingAs($employee);
        $service->agreeToKpi($appraisal);
        $question = $appraisal->snapshotCategories->first()->questions->first();
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 4.0],
        ]);

        $this->actingAs($middleManager);
        $service->submitAnswers($appraisal->fresh(), [
            ['question_id' => $question->id, 'rating' => 4.5],
        ]);

        $reviewer1 = $appraisal->reviewers->where('level', 1)->first();
        $this->actingAs($employee);
        $service->acknowledgeReview($appraisal->fresh(), $reviewer1->id);

        $appraisal = $appraisal->fresh();
        $this->assertEquals('completed', $appraisal->status);
        $this->assertEquals('Completed', $appraisal->current_stage);

        // Add topManager as Level 2 reviewer
        $this->actingAs($admin);
        $service->addReviewer($appraisal, $topManager->id);

        $appraisal = $appraisal->fresh();
        $this->assertEquals('published', $appraisal->status);
        $this->assertNull($appraisal->completed_at);
        $this->assertEquals('Reviewer Level 2', $appraisal->current_stage);

        // topManager submits review
        $this->actingAs($topManager);
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 4.8],
        ]);

        $reviewer2 = $appraisal->reviewers->where('level', 2)->first();
        $this->actingAs($employee);
        $service->acknowledgeReview($appraisal->fresh(), $reviewer2->id);

        // Appraisal becomes completed again
        $appraisal = $appraisal->fresh();
        $this->assertEquals('completed', $appraisal->status);
        $this->assertEquals('Completed', $appraisal->current_stage);
        $this->assertEquals(4.8, (float) $appraisal->final_rating);
    }

    public function test_adding_ineligible_or_duplicate_reviewer_is_rejected(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager]);
        $service = app(AppraisalService::class);
        $this->actingAs($admin);

        // Attempting to add unrelated user (not in chain)
        try {
            $service->addReviewer($appraisal, $unrelatedUser->id);
            $this->fail('Expected ValidationException for ineligible user');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewer_user_id', $e->errors());
        }

        // Attempting to add employee as self reviewer
        try {
            $service->addReviewer($appraisal, $employee->id);
            $this->fail('Expected ValidationException for employee as reviewer');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewer_user_id', $e->errors());
        }

        // Attempting to add duplicate reviewer
        try {
            $service->addReviewer($appraisal, $middleManager->id);
            $this->fail('Expected ValidationException for duplicate reviewer');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewer_user_id', $e->errors());
        }
    }

    public function test_questions_and_answers_are_never_flushed_during_reviewer_management(): void
    {
        [$employee, $middleManager, $topManager, $otherManager, $unrelatedUser, $admin, $kpi] = $this->createEmployeeWithChain();

        $appraisal = $this->createPublishedAppraisal($employee, $kpi, [$middleManager, $topManager]);
        $service = app(AppraisalService::class);

        $this->actingAs($employee);
        $service->agreeToKpi($appraisal);
        $question = $appraisal->snapshotCategories->first()->questions->first();
        $service->submitAnswers($appraisal, [
            ['question_id' => $question->id, 'rating' => 4.2],
        ]);

        $answerCountBefore = AppraisalAnswer::where('appraisal_id', $appraisal->id)->count();
        $this->assertEquals(1, $answerCountBefore);

        // Manage reviewers: add a new reviewer
        $this->actingAs($admin);
        $service->addReviewer($appraisal, $otherManager->id);

        $answerCountAfter = AppraisalAnswer::where('appraisal_id', $appraisal->id)->count();
        $this->assertEquals(1, $answerCountAfter);

        $answer = AppraisalAnswer::where('appraisal_id', $appraisal->id)->first();
        $this->assertEquals(4.2, (float) $answer->rating);
        $this->assertNotNull($answer->submitted_at);
    }
}
