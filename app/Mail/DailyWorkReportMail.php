<?php

namespace App\Mail;

use App\Models\DailyWorkReportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyWorkReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DailyWorkReportLog $reportLog
    ) {
    }

    public function build(): static
    {
        return $this
            ->subject(
                'Daily Work Report - '
                . $this->reportLog->user?->name
                . ' - '
                . $this->reportLog->report_date->format('d M Y')
            )
            ->view('emails.daily-work-report')
            ->with([
                'reportLog' => $this->reportLog,
                'report' => $this->reportLog->report_data ?? [],
            ]);
    }
}