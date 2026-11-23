<?php

namespace App\Console\Commands;

use App\Models\DailyWorkReportSetting;
use App\Services\Reports\ProjectReportAutomationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class GenerateProjectReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-project-reports {--reference-date= : Optional reference datetime (e.g. 2026-11-22 08:00:00)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and queue scheduled project reports according to configured settings.';

    public function __construct(
        protected ProjectReportAutomationService $automationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawReferenceDate = $this->option('reference-date');
        $referenceDate = $rawReferenceDate ? Carbon::parse($rawReferenceDate) : null;

        $settings = DailyWorkReportSetting::query()
            ->where('report_key', 'project_report')
            ->where('enabled', true)
            ->get();

        if ($settings->isEmpty()) {
            $this->line('No enabled Project Report configurations found.');
            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($settings as $setting) {
            try {
                if (! $this->automationService->isConfigurationDue($setting, $referenceDate)) {
                    $skipped++;
                    continue;
                }

                $this->info("Processing Project Report [{$setting->id}]: {$setting->report_name}");
                $log = $this->automationService->generateAndDeliver($setting, $referenceDate);

                if ($log) {
                    $processed++;
                }
            } catch (Throwable $e) {
                $failed++;
                $this->error("Failed processing Project Report [{$setting->id}] {$setting->report_name}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->line("Project Reports execution completed. Processed: {$processed}, Skipped: {$skipped}, Failures: {$failed}");

        return self::SUCCESS;
    }
}
