<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AppraisalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppraisalReviewCommentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_comment_is_drafted_submitted_and_locked_with_answers(): void
    {
        [$appraisal, $question, $reporter] = $this->createReviewAppraisal('reporter');
        $service = app(AppraisalService::class);
        $this->actingAs($reporter);

        $service->saveDraft($appraisal, $this->answersFor($question->id, 3.5, 'First draft'), 'First comment');
        $service->saveDraft($appraisal->fresh(), $this->answersFor($question->id, 4.0, 'Updated draft'), 'Updated comment');

        $this->assertDatabaseHas('appraisal_comments', [
            'appraisal_id' => $appraisal->id,
            'role' => 'reporter',
            'comment' => 'Updated comment',
        ]);
        $this->assertNull($appraisal->fresh()->reporter_submitted_at);

        $service->submitAnswers($appraisal->fresh(), $this->answersFor($question->id, 4.5, 'Final remark'), 'Final comment');

        $this->assertNotNull($appraisal->fresh()->reporter_submitted_at);
        $this->assertDatabaseHas('appraisal_comments', [
            'appraisal_id' => $appraisal->id,
            'role' => 'reporter',
            'comment' => 'Final comment',
        ]);

        $this->expectException(ValidationException::class);
        $service->saveDraft($appraisal->fresh(), $this->answersFor($question->id, 5.0, 'Too late'), 'Changed after submit');
    }

    public function test_manager_comment_is_drafted_submitted_and_locked_with_answers(): void
    {
        [$appraisal, $question, $manager] = $this->createReviewAppraisal('manager');
        $service = app(AppraisalService::class);
        $this->actingAs($manager);

        $service->saveDraft($appraisal, $this->answersFor($question->id, 3.0, 'Manager draft'), 'Manager draft comment');

        $this->assertDatabaseHas('appraisal_comments', [
            'appraisal_id' => $appraisal->id,
            'role' => 'manager',
            'comment' => 'Manager draft comment',
        ]);
        $this->assertNull($appraisal->fresh()->manager_submitted_at);

        $service->submitAnswers($appraisal->fresh(), $this->answersFor($question->id, 4.0, 'Manager final'), 'Manager final comment');

        $appraisal->refresh();
        $this->assertNotNull($appraisal->manager_submitted_at);
        $this->assertSame('completed', $appraisal->status);
        $this->assertDatabaseHas('appraisal_comments', [
            'appraisal_id' => $appraisal->id,
            'role' => 'manager',
            'comment' => 'Manager final comment',
        ]);

        $this->expectException(ValidationException::class);
        $service->submitAnswers($appraisal, $this->answersFor($question->id, 5.0, 'Too late'), 'Changed after submit');
    }

    public function test_rating_can_be_submitted_without_a_remark_and_accepts_zero(): void
    {
        [$appraisal, $question, $reporter] = $this->createReviewAppraisal('reporter');
        $this->actingAs($reporter);

        app(AppraisalService::class)->submitAnswers($appraisal, [[
            'question_id' => $question->id,
            'rating' => 0,
            'remark' => null,
        ]]);

        $this->assertNotNull($appraisal->fresh()->reporter_submitted_at);
    }

    private function createReviewAppraisal(string $role): array
    {
        $assignee = User::factory()->create();
        $reporter = User::factory()->create();
        $manager = User::factory()->create();

        UserDetail::create([
            'user_id' => $assignee->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $manager->id,
            'gender' => 'other',
        ]);

        $appraisal = Appraisal::create([
            'year' => 2026,
            'month' => 7,
            'user_id' => $assignee->id,
            'status' => 'published',
            'kpi_agreed_at' => now(),
        ]);
        $appraisal->assignee_submitted_at = now();

        if ($role === 'manager') {
            $appraisal->reporter_submitted_at = now();
        }

        $appraisal->save();

        $category = $appraisal->snapshotCategories()->create([
            'name' => 'Performance',
            'sort_order' => 1,
        ]);
        $question = $category->questions()->create([
            'question' => 'How was performance?',
            'question_type' => 'rating',
            'sort_order' => 1,
        ]);

        return [$appraisal, $question, $role === 'manager' ? $manager : $reporter];
    }

    private function answersFor(int $questionId, float $rating, string $remark): array
    {
        return [[
            'question_id' => $questionId,
            'rating' => $rating,
            'remark' => $remark,
        ]];
    }
}
