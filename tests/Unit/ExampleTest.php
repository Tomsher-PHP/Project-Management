<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_limit_string_char_helper()
    {
        $this->assertEquals('Hello..', limitStringChar('Hello World', 5));
        $this->assertEquals('Hello World', limitStringChar('Hello World', 15));
        $this->assertEquals('', limitStringChar(null, 5));
    }

    public function test_task_due_color_helpers()
    {
        // 1. No due date -> normal
        $this->assertEquals('normal', taskDueColor(null));

        // 2. Active task (not completed) -> keep existing due date logic
        $statusActive = (object) ['is_completed' => false];
        
        $dueAtFuture = now()->addDays(2);
        $dueAtPast = now()->subDays(2);

        $this->assertEquals('normal', taskDueColor($dueAtFuture, null, $statusActive));
        $this->assertEquals('red', taskDueColor($dueAtPast, null, $statusActive));

        // 3. Completed on time
        $statusCompleted = (object) ['is_completed' => true];
        $dueAt = now();
        $completedAtOnTime = now()->subMinutes(10);
        $this->assertEquals('normal', taskDueColor($dueAt, null, $statusCompleted, $completedAtOnTime));

        // 4. Completed exactly on due date
        $completedAtExactly = $dueAt;
        $this->assertEquals('normal', taskDueColor($dueAt, null, $statusCompleted, $completedAtExactly));

        // 5. Completed late
        $completedAtLate = now()->addMinutes(10);
        $this->assertEquals('red', taskDueColor($dueAt, null, $statusCompleted, $completedAtLate));

        // 6. Test Icon output
        $iconOnTime = taskDueDateIcon($dueAt, null, $statusCompleted, $completedAtOnTime);
        $this->assertEquals('', (string) $iconOnTime);

        $iconLate = taskDueDateIcon($dueAt, null, $statusCompleted, $completedAtLate);
        $this->assertStringContainsString('task-due-date__icon--red', (string) $iconLate);
        $this->assertStringContainsString('circle cx="16.5"', (string) $iconLate); // clock alert indicator
    }
}
