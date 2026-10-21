<?php

namespace Tests\Unit;

use App\Models\ProjectHostingRecord;
use App\Models\ProjectTimeline;
use App\Services\ProjectNotificationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ProjectNotificationServiceLogicTest extends TestCase
{
    private ProjectNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectNotificationService();
    }

    public function test_determine_due_occasion_with_10_days_before()
    {
        $timeline = new ProjectTimeline();
        $timeline->end_date = Carbon::parse('2026-10-30');

        $daysBefore = 10;

        // Occasion 1: 10 days before (Oct 20)
        $occasion1 = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-20'), $daysBefore);
        $this->assertEquals('days_before_10', $occasion1);

        // Not due date (Oct 21)
        $occasionNone = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-21'), $daysBefore);
        $this->assertNull($occasionNone);

        // Occasion 2: 1 day before (Oct 29)
        $occasion2 = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-29'), $daysBefore);
        $this->assertEquals('1_day_before', $occasion2);

        // Occasion 3: on expiry date (Oct 30)
        $occasion3 = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-30'), $daysBefore);
        $this->assertEquals('on_expiry_date', $occasion3);

        // Occasion 4: 1 day after (Oct 31)
        $occasion4 = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-31'), $daysBefore);
        $this->assertEquals('1_day_after', $occasion4);

        // 2 days after (Nov 1) -> not due
        $occasionAfter = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-11-01'), $daysBefore);
        $this->assertNull($occasionAfter);
    }

    public function test_determine_due_occasion_with_1_day_before_does_not_duplicate()
    {
        $timeline = new ProjectTimeline();
        $timeline->end_date = Carbon::parse('2026-10-30');

        $daysBefore = 1;

        // On Oct 29, days_before=1 matches. It must return a single occasion, not duplicate.
        $occasion = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-29'), $daysBefore);
        $this->assertEquals('days_before_1', $occasion);

        // On Oct 30 (expiry date)
        $occasionExpiry = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-30'), $daysBefore);
        $this->assertEquals('on_expiry_date', $occasionExpiry);

        // On Oct 31 (1 day after)
        $occasionAfter = $this->service->determineDueOccasionForToday($timeline, Carbon::parse('2026-10-31'), $daysBefore);
        $this->assertEquals('1_day_after', $occasionAfter);
    }

    public function test_determine_hosting_due_occasion_with_5_days_before()
    {
        $hosting = new ProjectHostingRecord();
        $hosting->expiry_date = Carbon::parse('2026-10-26');

        $daysBefore = 5;

        // Occasion 1: 5 days before (Oct 21)
        $occasion1 = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-21'), $daysBefore);
        $this->assertEquals('days_before_5', $occasion1);

        // Not due date (Oct 22)
        $occasionNone = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-22'), $daysBefore);
        $this->assertNull($occasionNone);

        // Occasion 2: 1 day before (Oct 25)
        $occasion2 = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-25'), $daysBefore);
        $this->assertEquals('1_day_before', $occasion2);

        // Occasion 3: on expiry date (Oct 26)
        $occasion3 = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-26'), $daysBefore);
        $this->assertEquals('on_expiry_date', $occasion3);

        // Occasion 4: 1 day after (Oct 27)
        $occasion4 = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-27'), $daysBefore);
        $this->assertEquals('1_day_after', $occasion4);

        // 2 days after (Oct 28) -> not due
        $occasionAfter = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-28'), $daysBefore);
        $this->assertNull($occasionAfter);
    }

    public function test_determine_hosting_due_occasion_with_1_day_before_overlap()
    {
        $hosting = new ProjectHostingRecord();
        $hosting->expiry_date = Carbon::parse('2026-10-26');

        $daysBefore = 1;

        // On Oct 25, days_before=1 and 1_day_before coincide -> return single occasion
        $occasion = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-25'), $daysBefore);
        $this->assertEquals('days_before_1', $occasion);

        // On Oct 26 (expiry date)
        $occasionExpiry = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-26'), $daysBefore);
        $this->assertEquals('on_expiry_date', $occasionExpiry);

        // On Oct 27 (1 day after)
        $occasionAfter = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-27'), $daysBefore);
        $this->assertEquals('1_day_after', $occasionAfter);
    }

    public function test_determine_hosting_due_occasion_with_null_expiry_date()
    {
        $hosting = new ProjectHostingRecord();
        $hosting->expiry_date = null;

        $occasion = $this->service->determineHostingDueOccasionForToday($hosting, Carbon::parse('2026-10-21'), 5);
        $this->assertNull($occasion);
    }
}
