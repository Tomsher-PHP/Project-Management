<?php

use Illuminate\Support\HtmlString;

if (!function_exists('taskStatusIsCompleted')) {
    function taskStatusIsCompleted($status = null): bool
    {
        return (bool) ($status?->is_completed ?? false);
    }
}

if (!function_exists('taskDueColor')) {
    function taskDueColor($dueAt, $estimateSeconds = null, $status = null, $completedAt = null): string
    {
        if ($status instanceof \App\Models\Task) {
            $completedAt = $status->completed_at;
            $status = $status->status;
        }

        if (!$dueAt) {
            return 'normal';
        }

        $isCompleted = taskStatusIsCompleted($status);

        if ($isCompleted) {
            if ($completedAt) {
                $completedAtCarbon = \Illuminate\Support\Carbon::parse($completedAt);
                $dueAtCarbon = \Illuminate\Support\Carbon::parse($dueAt);

                if ($completedAtCarbon->gt($dueAtCarbon)) {
                    return 'red';
                }
            }
            return 'normal';
        }

        $now = now();
        $minutesBefore = (int) env('TASK_START_NOTIFICATION_MINUTES_BEFORE', 10);

        if (!$estimateSeconds) {
            return $dueAt->gt($now) ? 'normal' : 'red';
        }

        $startThreshold = $dueAt->copy()->subSeconds($estimateSeconds);
        $notifyAt = $startThreshold->copy()->subMinutes($minutesBefore);

        if ($now->lt($notifyAt)) {
            return 'normal';
        }

        if ($now->lt($dueAt)) {
            return 'orange';
        }

        return 'red';
    }
}

if (!function_exists('taskDueDateClass')) {
    function taskDueDateClass($dueAt, $estimateSeconds = null, $status = null, $completedAt = null): string
    {
        if (!$dueAt) {
            return 'task-due-date--normal';
        }

        return 'task-due-date--' . taskDueColor($dueAt, $estimateSeconds, $status, $completedAt);
    }
}

if (!function_exists('taskDueDateIcon')) {
    function taskDueDateIcon($dueAt, $estimateSeconds = null, $status = null, $completedAt = null): HtmlString
    {
        if ($status instanceof \App\Models\Task) {
            $completedAt = $status->completed_at;
            $status = $status->status;
        }

        if (!$dueAt) {
            return new HtmlString('');
        }

        $isCompleted = taskStatusIsCompleted($status);
        $dueColor = taskDueColor($dueAt, $estimateSeconds, $status, $completedAt);

        if ($dueColor === 'orange') {
            return new HtmlString('
                <svg class="task-due-date__icon task-due-date__icon--orange" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M6 3.5H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M6 16.5H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M7 3.5C7 6 8.6 7.4 10 8.5C11.4 7.4 13 6 13 3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 16.5C7 14 8.6 12.6 10 11.5C11.4 12.6 13 14 13 16.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 9.2H12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M8.4 12.1H11.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            ');
        }

        if ($dueColor === 'red') {
            if ($isCompleted) {
                return new HtmlString('
                    <svg class="task-due-date__icon task-due-date__icon--red" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M9 5v4l2.5 1.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.5 15.5C12.2 16.5 10.7 17 9 17C4.6 17 1 13.4 1 9C1 4.6 4.6 1 9 1C13.4 1 17 4.6 17 9C17 10.2 16.7 11.4 16.2 12.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M16.5 12.5v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <circle cx="16.5" cy="17.5" r="0.9" fill="currentColor"/>
                    </svg>
                ');
            }

            return new HtmlString('
                <svg class="task-due-date__icon task-due-date__icon--red" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M10 2.75L17 15.25C17.1667 15.5278 17.1667 15.8611 17 16.1389C16.8056 16.3796 16.5093 16.5 16.1111 16.5H3.88889C3.49074 16.5 3.19444 16.3796 3 16.1389C2.83333 15.8611 2.83333 15.5278 3 15.25L10 2.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="M10 7V10.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <circle cx="10" cy="13.5" r="0.9" fill="currentColor"/>
                </svg>
            ');
        }

        return new HtmlString('');
    }
}

if (!function_exists('limitStringChar')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param string|null $string
     * @param int $count
     * @param string $end
     * @return string
     */
    function limitStringChar(?string $string, int $count, string $end = '..'): string
    {
        return \Illuminate\Support\Str::limit($string ?? '', $count, $end);
    }
}
