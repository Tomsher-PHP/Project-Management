<?php

namespace App\Mail;

use App\Models\Broadcast;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BroadcastPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Broadcast $broadcast;
    public User $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Broadcast $broadcast, User $recipient)
    {
        $this->broadcast = $broadcast;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = (int) $this->broadcast->priority === Broadcast::PRIORITY_URGENT
            ? "[Urgent] {$this->broadcast->title}"
            : "Broadcast: {$this->broadcast->title}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $configuration = Configuration::first();
        $companyLogoUrl = $configuration?->logo_url;
        $companyName = $configuration?->company_name ?? config('app.name', 'Tomsher PMS');

        return new Content(
            view: 'emails.broadcasts.broadcast_email_preview',
            with: [
                'broadcast' => $this->broadcast,
                'recipient' => $this->recipient,
                'companyLogoUrl' => $companyLogoUrl,
                'companyName' => $companyName,
                'categoryName' => $this->broadcast->category?->name,
                'categoryColor' => $this->broadcast->category?->color,
                'priorityLabel' => Broadcast::priorityOptions()[$this->broadcast->priority] ?? 'Normal',
                'contentHtml' => $this->broadcast->content,
                'dateRangeFormatted' => $this->formatDateRange(),
                'broadcastUrl' => route('broadcasts.index'),
                'attachmentsList' => $this->formatAttachmentsList(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $mailAttachments = [];
        $maxTotalBytes = 15 * 1024 * 1024; // 15MB total limit for email attachments
        $currentTotalBytes = 0;

        foreach ($this->broadcast->attachments as $attachment) {
            $disk = $attachment->disk ?? config('filesystems.default', 'local');
            $storage = Storage::disk($disk);

            if (! $storage->exists($attachment->file_path)) {
                Log::warning('Broadcast email attachment missing on disk', [
                    'broadcast_id' => $this->broadcast->id,
                    'attachment_id' => $attachment->id,
                    'path' => $attachment->file_path,
                    'disk' => $disk,
                ]);
                continue;
            }

            $size = (int) ($attachment->file_size ?: $storage->size($attachment->file_path));

            if (($currentTotalBytes + $size) > $maxTotalBytes) {
                Log::info('Broadcast email attachment skipped due to combined size limit', [
                    'broadcast_id' => $this->broadcast->id,
                    'attachment_id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_size' => $size,
                ]);
                continue;
            }

            $currentTotalBytes += $size;
            $fileName = $attachment->original_name ?: $attachment->file_name;

            $mailAttachments[] = Attachment::fromStorageDisk($disk, $attachment->file_path)
                ->as($fileName)
                ->withMime($attachment->file_type ?? 'application/octet-stream');
        }

        return $mailAttachments;
    }

    /**
     * Format broadcast date range if available.
     */
    protected function formatDateRange(): ?string
    {
        $dateFormat = config('constants.date_format', 'd M Y');

        if ($this->broadcast->start_date && $this->broadcast->end_date) {
            $start = $this->broadcast->start_date->format($dateFormat);
            $end = $this->broadcast->end_date->format($dateFormat);

            return $start === $end ? $start : "{$start} — {$end}";
        }

        if ($this->broadcast->start_date) {
            return 'From ' . $this->broadcast->start_date->format($dateFormat);
        }

        if ($this->broadcast->end_date) {
            return 'Until ' . $this->broadcast->end_date->format($dateFormat);
        }

        return null;
    }

    /**
     * Format attachment list for email template view.
     */
    protected function formatAttachmentsList(): array
    {
        $list = [];

        foreach ($this->broadcast->attachments as $attachment) {
            $size = (int) ($attachment->file_size ?: 0);
            $sizeFormatted = $this->formatFileSize($size);

            $list[] = [
                'name' => $attachment->original_name ?: $attachment->file_name,
                'size' => $sizeFormatted,
            ];
        }

        return $list;
    }

    /**
     * Human readable file size.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $val = round($bytes / pow(1024, $power), 1);

        return $val . ' ' . ($units[$power] ?? 'B');
    }
}
