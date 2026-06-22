<?php

namespace Tests\Unit;

use App\Models\TaskSchedule;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftWeekend;
use App\Services\Task\GenerateScheduleTaskService;
use App\Services\TaskServices;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GenerateScheduleTaskServiceTest extends TestCase
{
    public function test_it_evaluates_each_supported_recurrence_rule(): void
    {
        $service = $this->makeService();
        $monday = Carbon::parse('2026-06-22');

        $this->assertTrue($service->isDueOn($this->schedule('weekdays', weekDays: [1, 3, 5]), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('weekdays', weekDays: [2, 4]), $monday));
        $this->assertTrue($service->isDueOn($this->schedule('weekly', weeklyDay: 1), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('weekly', weeklyDay: 2), $monday));
        $this->assertTrue($service->isDueOn($this->schedule('monthly', monthDays: [1, 22]), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('monthly', monthDays: [5, 15]), $monday));
    }

    public function test_daily_schedule_requires_an_assignee_and_active_shift_assignment(): void
    {
        $service = $this->makeService();
        $date = Carbon::parse('2026-07-15');

        $this->assertFalse($service->isDueOn($this->schedule('daily'), $date));
        $this->assertFalse($service->isDueOn($this->schedule('daily', assigneeId: 10), $date));

        $service->assignment = $this->assignment();

        $this->assertTrue($service->isDueOn($this->schedule('daily', assigneeId: 10), $date));
    }

    public function test_daily_schedule_is_skipped_on_assignment_weekend(): void
    {
        $service = $this->makeService();
        $sunday = Carbon::parse('2026-07-12');
        $service->assignment = $this->assignment([
            ['weekday' => 0, 'week_number' => 2],
        ]);

        $this->assertFalse($service->isDueOn($this->schedule('daily', assigneeId: 10), $sunday));

        $service->assignment = $this->assignment([
            ['weekday' => 0, 'week_number' => 3],
        ]);

        $this->assertTrue($service->isDueOn($this->schedule('daily', assigneeId: 10), $sunday));
    }

    private function makeService(): GenerateScheduleTaskService
    {
        $taskServices = (new ReflectionClass(TaskServices::class))->newInstanceWithoutConstructor();

        return new class($taskServices) extends GenerateScheduleTaskService
        {
            public ?UserShiftAssignment $assignment = null;

            protected function resolveShiftAssignmentForDate(int $userId, Carbon $date): ?UserShiftAssignment
            {
                return $this->assignment;
            }
        };
    }

    private function schedule(
        string $frequency,
        array $weekDays = [],
        ?int $weeklyDay = null,
        array $monthDays = [],
        ?int $assigneeId = null,
    ): TaskSchedule {
        return new TaskSchedule([
            'frequency_type' => $frequency,
            'week_days' => $weekDays,
            'weekly_day' => $weeklyDay,
            'month_days' => $monthDays,
            'current_assignee_id' => $assigneeId,
        ]);
    }

    private function assignment(array $weekends = []): UserShiftAssignment
    {
        $assignment = new UserShiftAssignment;
        $assignment->setRelation(
            'weekends',
            collect($weekends)->map(fn (array $weekend) => new UserShiftWeekend($weekend))
        );

        return $assignment;
    }
}
