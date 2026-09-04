<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DailyShiftHoursShortNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $employee;
    protected string $workDate;
    protected string $shiftName;
    protected string $shiftTimeRange;
    protected int $requiredSeconds;
    protected int $workedSeconds;
    protected int $shortSeconds;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        User $employee,
        string $workDate,
        string $shiftName,
        string $shiftTimeRange,
        int $requiredSeconds,
        int $workedSeconds,
        int $shortSeconds
    ) {
        $this->employee = $employee;
        $this->workDate = $workDate;
        $this->shiftName = $shiftName;
        $this->shiftTimeRange = $shiftTimeRange;
        $this->requiredSeconds = $requiredSeconds;
        $this->workedSeconds = $workedSeconds;
        $this->shortSeconds = $shortSeconds;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subjectPrefix = filled(env('APP_NAME', '')) ? env('APP_NAME') . ' - ' : '';
        $subject = 'Daily Shift Hours Not Completed';

        $formattedWorkDate = Carbon::parse($this->workDate)->format('l, F j, Y');
        $formattedRequired = $this->formatDuration($this->requiredSeconds);
        $formattedWorked = $this->formatDuration($this->workedSeconds);
        $formattedShort = $this->formatDuration($this->shortSeconds);

        $details = [
            ['label' => 'Employee', 'value' => $this->employee->name],
            ['label' => 'Date', 'value' => $formattedWorkDate],
            ['label' => 'Shift', 'value' => $this->shiftTimeRange],
            ['label' => 'Required', 'value' => $formattedRequired],
            ['label' => 'Worked', 'value' => $formattedWorked],
            ['label' => 'Short', 'value' => $formattedShort],
        ];

        return (new MailMessage)
            ->subject($subjectPrefix . $subject)
            ->view('emails.notifications.custom', [
                'title' => $subject,
                'messageText' => "Daily shift hours were not completed for {$this->employee->name} on {$formattedWorkDate}.",
                'url' => null,
                'details' => $details,
            ]);
    }

    /**
     * Format seconds into "Xh Ym" string (e.g. 8h 00m, 6h 42m).
     */
    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%dh %02dm', $hours, $minutes);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'work_date' => $this->workDate,
            'shift_name' => $this->shiftName,
            'shift_time_range' => $this->shiftTimeRange,
            'required_seconds' => $this->requiredSeconds,
            'worked_seconds' => $this->workedSeconds,
            'short_seconds' => $this->shortSeconds,
        ];
    }
}
