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

class MeetingExternalParticipantAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Meeting $meeting;
    public ?string $participantName;
    public string $startTimeFormatted;
    public string $endTimeFormatted;
    public string $durationFormatted;

    /**
     * Create a new message instance.
     */
    public function __construct(Meeting $meeting, ?string $participantName = null)
    {
        $this->meeting = $meeting;
        $this->participantName = $participantName;

        $timezone = AppServiceProvider::getTimezone();

        $this->startTimeFormatted = $meeting->start_at
            ? AppServiceProvider::formatAppDateTime($meeting->start_at)
            : 'N/A';

        $this->endTimeFormatted = $meeting->end_at
            ? AppServiceProvider::formatAppDateTime($meeting->end_at)
            : 'N/A';

        if ($meeting->start_at && $meeting->end_at) {
            $totalMins = (int) $meeting->start_at->diffInMinutes($meeting->end_at);
            if ($totalMins < 60) {
                $this->durationFormatted = "{$totalMins} mins";
            } else {
                $h = floor($totalMins / 60);
                $m = $totalMins % 60;
                $this->durationFormatted = $m > 0 ? "{$h}h {$m}m" : "{$h} " . ($h > 1 ? 'hours' : 'hour');
            }
        } else {
            $this->durationFormatted = 'N/A';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation: Meeting '{$this->meeting->title}'",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.meetings.external-assigned',
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
