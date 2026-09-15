<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Providers\AppServiceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingExternalParticipantRescheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Meeting $originalMeeting;
    public Meeting $newMeeting;
    public ?string $participantName;
    public ?string $rescheduleReason;
    public string $originalStartTimeFormatted;
    public string $newStartTimeFormatted;
    public string $newEndTimeFormatted;
    public string $newDurationFormatted;

    /**
     * Create a new message instance.
     */
    public function __construct(Meeting $originalMeeting, Meeting $newMeeting, ?string $participantName = null)
    {
        $this->originalMeeting = $originalMeeting;
        $this->newMeeting = $newMeeting;
        $this->participantName = $participantName;
        $this->rescheduleReason = $newMeeting->reschedule_reason;

        $this->originalStartTimeFormatted = $originalMeeting->start_at
            ? AppServiceProvider::formatAppDateTime($originalMeeting->start_at)
            : 'N/A';

        $this->newStartTimeFormatted = $newMeeting->start_at
            ? AppServiceProvider::formatAppDateTime($newMeeting->start_at)
            : 'N/A';

        $this->newEndTimeFormatted = $newMeeting->end_at
            ? AppServiceProvider::formatAppDateTime($newMeeting->end_at)
            : 'N/A';

        if ($newMeeting->start_at && $newMeeting->end_at) {
            $totalMins = (int) $newMeeting->start_at->diffInMinutes($newMeeting->end_at);
            if ($totalMins < 60) {
                $this->newDurationFormatted = "{$totalMins} mins";
            } else {
                $h = floor($totalMins / 60);
                $m = $totalMins % 60;
                $this->newDurationFormatted = $m > 0 ? "{$h}h {$m}m" : "{$h} " . ($h > 1 ? 'hours' : 'hour');
            }
        } else {
            $this->newDurationFormatted = 'N/A';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rescheduled: Meeting '{$this->originalMeeting->title}'",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.meetings.external-rescheduled',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
