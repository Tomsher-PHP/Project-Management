<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\MeetingStatus;
use App\Providers\AppServiceProvider;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyMeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:notify-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify internal meeting participants, organizer, and creator before an upcoming meeting starts.';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $timezone = AppServiceProvider::getTimezone();
        $now = now()->setTimezone($timezone);
        $sentCount = 0;
        $minutesBefore = (int) config('constants.meeting_reminder_notification_min', 10);

        $rescheduledStatusId = MeetingStatus::query()
            ->where('code', MeetingStatus::STATUS_RESCHEDULED)
            ->value('id');

        $meetings = Meeting::query()
            ->with([
                'project:id,name',
                'meetingType:id,name',
                'meetingLocation:id,name',
                'meetingStatus:id,name',
                'organizer:id,name,email',
                'addedBy:id,name,email',
                'participants',
            ])
            ->whereNull('reminder_sent_at')
            ->whereNotNull('start_at')
            ->where('start_at', '>', $now)
            ->where('start_at', '<=', $now->copy()->addMinutes($minutesBefore))
            ->when($rescheduledStatusId, function ($q) use ($rescheduledStatusId) {
                $q->where('meeting_status_id', '!=', $rescheduledStatusId);
            }, function ($q) {
                $q->whereDoesntHave('meetingStatus', fn($sq) => $sq->where('code', MeetingStatus::STATUS_RESCHEDULED));
            })
            ->get();

        if ($meetings->isNotEmpty()) {
            foreach ($meetings as $meeting) {
                if ($notificationService->notifyMeetingReminder($meeting)) {
                    $meeting->update([
                        'reminder_sent_at' => now(),
                    ]);
                    $sentCount++;
                }
            }

            if ($sentCount > 0) {
                Log::info("Meeting reminder notifications sent: {$sentCount}");
            }
        }

        return self::SUCCESS;
    }
}
