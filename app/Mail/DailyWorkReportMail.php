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
        $report = $this->reportLog->report_data ?? [];

        // For consolidated reports, use the report owner.
        // Fall back to the report log's user relationship for older reports.
        $reportOwnerName = data_get($report, 'report_owner.name')
            ?: $this->reportLog->user?->name
            ?: 'Team';

        $reportDate = $this->reportLog->report_date;

        return $this
            ->subject(
                'Daily Work Report - '
                . $reportOwnerName
                . ' - '
                . $reportDate->format('d M Y')
            )
            ->view('emails.daily-work-report')
            ->with([
                'reportLog' => $this->reportLog,
                'report' => $report,
                'reportOwner' => data_get($report, 'report_owner'),
                'employees' => data_get($report, 'employees', []),
            ]);
    }
}
