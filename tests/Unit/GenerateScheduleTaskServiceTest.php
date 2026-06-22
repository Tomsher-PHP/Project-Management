<?php

namespace Tests\Unit;

use App\Models\TaskSchedule;
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

        $this->assertTrue($service->isDueOn($this->schedule('daily'), $monday));
        $this->assertTrue($service->isDueOn($this->schedule('weekdays', weekDays: [1, 3, 5]), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('weekdays', weekDays: [2, 4]), $monday));
        $this->assertTrue($service->isDueOn($this->schedule('weekly', weeklyDay: 1), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('weekly', weeklyDay: 2), $monday));
        $this->assertTrue($service->isDueOn($this->schedule('monthly', monthDays: [1, 22]), $monday));
        $this->assertFalse($service->isDueOn($this->schedule('monthly', monthDays: [5, 15]), $monday));
    }

    private function makeService(): GenerateScheduleTaskService
    {
        $taskServices = (new ReflectionClass(TaskServices::class))->newInstanceWithoutConstructor();

        return new GenerateScheduleTaskService($taskServices);
    }

    private function schedule(
        string $frequency,
        array $weekDays = [],
        ?int $weeklyDay = null,
        array $monthDays = [],
    ): TaskSchedule {
        return new TaskSchedule([
            'frequency_type' => $frequency,
            'week_days' => $weekDays,
            'weekly_day' => $weeklyDay,
            'month_days' => $monthDays,
        ]);
    }
}
