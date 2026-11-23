<?php

namespace App\Mail;

use App\Models\DailyWorkReportLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;
    public ?DailyWorkReportLog $reportLog;
    public ?string $customSubject;

    public function __construct(
        array|DailyWorkReportLog $report,
        ?DailyWorkReportLog $reportLog = null,
        ?string $subject = null
    ) {
        if ($report instanceof DailyWorkReportLog) {
            $this->reportLog = $report;
            $this->reportData = (array) ($report->report_data ?? []);
            $this->customSubject = $subject;
        } else {
            $this->reportData = $report;
            $this->reportLog = $reportLog;
            $this->customSubject = $subject;
        }
    }

    public function build(): static
    {
        $reportName = (string) data_get($this->reportData, 'metadata.report_name', 'Project Report');
        $generatedAt = data_get($this->reportData, 'metadata.generated_at');
        $dateFormatted = $generatedAt
            ? Carbon::parse($generatedAt)->format('d M Y')
            : now()->format('d M Y');

        $subject = $this->customSubject ?: "{$reportName} - {$dateFormatted}";

        return $this
            ->subject($subject)
            ->view('emails.project-report')
            ->with([
                'report' => $this->reportData,
                'metadata' => data_get($this->reportData, 'metadata', []),
                'columns' => data_get($this->reportData, 'columns', []),
                'headers' => data_get($this->reportData, 'headers', []),
                'columnKeys' => data_get($this->reportData, 'column_keys', []),
                'flatRows' => data_get($this->reportData, 'flat_rows', []),
                'projects' => data_get($this->reportData, 'projects', []),
                'stats' => data_get($this->reportData, 'stats', []),
                'reportLog' => $this->reportLog,
            ]);
    }
}

