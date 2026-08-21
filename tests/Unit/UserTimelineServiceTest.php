<?php

namespace Tests\Unit;

use App\Services\UserTimelineService;
use PHPUnit\Framework\TestCase;

class UserTimelineServiceTest extends TestCase
{
    private UserTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserTimelineService();
    }

    /** @test */
    public function example_1_shift_8h_30m_worked_8h_00m()
    {
        $assignedShift = [
            'is_working_day' => true,
            'is_weekend' => false,
            'timeline_segments' => [
                ['actual_working_duration_seconds' => (8 * 3600) + (30 * 60)],
            ],
        ];
        $workedTotalSeconds = 8 * 3600;

        $result = $this->service->getWorkedShiftDiff($assignedShift, $workedTotalSeconds);

        $this->assertTrue($result['is_negative']);
        $this->assertEquals('(-00h 30m)', $result['formatted']);
        $this->assertEquals('-', $result['sign']);
        $this->assertEquals(-1800, $result['diff_seconds']);
    }

    /** @test */
    public function example_2_shift_8h_30m_worked_8h_30m()
    {
        $assignedShift = [
            'is_working_day' => true,
            'is_weekend' => false,
            'timeline_segments' => [
                ['actual_working_duration_seconds' => (8 * 3600) + (30 * 60)],
            ],
        ];
        $workedTotalSeconds = (8 * 3600) + (30 * 60);

        $result = $this->service->getWorkedShiftDiff($assignedShift, $workedTotalSeconds);

        $this->assertFalse($result['is_negative']);
        $this->assertEquals('(00h 00m)', $result['formatted']);
        $this->assertEquals('', $result['sign']);
        $this->assertEquals(0, $result['diff_seconds']);
    }

    /** @test */
    public function example_3_shift_8h_30m_worked_9h_30m()
    {
        $assignedShift = [
            'is_working_day' => true,
            'is_weekend' => false,
            'timeline_segments' => [
                ['actual_working_duration_seconds' => (8 * 3600) + (30 * 60)],
            ],
        ];
        $workedTotalSeconds = (9 * 3600) + (30 * 60);

        $result = $this->service->getWorkedShiftDiff($assignedShift, $workedTotalSeconds);

        $this->assertFalse($result['is_negative']);
        $this->assertEquals('(+01h 00m)', $result['formatted']);
        $this->assertEquals('+', $result['sign']);
        $this->assertEquals(3600, $result['diff_seconds']);
    }

    /** @test */
    public function example_4_shift_8h_30m_but_day_off_worked_4h_30m()
    {
        $assignedShift = [
            'is_working_day' => false,
            'is_weekend' => true,
            'timeline_segments' => [
                ['actual_working_duration_seconds' => (8 * 3600) + (30 * 60)],
            ],
        ];
        $workedTotalSeconds = (4 * 3600) + (30 * 60);

        $result = $this->service->getWorkedShiftDiff($assignedShift, $workedTotalSeconds);

        $this->assertFalse($result['is_negative']);
        $this->assertEquals('(+04h 30m)', $result['formatted']);
        $this->assertEquals('+', $result['sign']);
        $this->assertEquals(16200, $result['diff_seconds']);
    }
}
