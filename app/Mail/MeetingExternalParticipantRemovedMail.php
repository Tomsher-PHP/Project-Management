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

class MeetingExternalParticipantRemovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Meeting $meeting;
    public ?string $participantName;
    public string $startTimeFormatted;

    /**
     * Create a new message instance.
     */
    public function __construct(Meeting $meeting, ?string $participantName = null)
    {
        $this->meeting = $meeting;
        $this->participantName = $participantName;

        $this->startTimeFormatted = $meeting->start_at
            ? AppServiceProvider::formatAppDateTime($meeting->start_at)
            : 'N/A';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update: Removed from Meeting '{$this->meeting->title}'",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.meetings.external-removed',
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
