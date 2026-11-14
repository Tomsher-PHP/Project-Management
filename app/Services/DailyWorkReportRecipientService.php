<?php

namespace App\Services;

use App\Models\DailyWorkReportRecipient;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyWorkReportRecipientService
{
    /**
     * Existing individual-report recipient resolution.
     * Kept for backward compatibility.
     */
    public function resolve(
        User $employee,
        string $reportDate,
        string $reportKey = 'daily_work'
    ): Collection {
        $recipients = collect();

        $this->addRecipient(
            $recipients,
            $employee->email,
            $employee->name,
            'employee'
        );

        $details = $employee->details;

        if ($details) {
            $reporter = User::find($details->reporter_id);
            $manager = User::find($details->manager_id);

            if ($reporter) {
                $this->addRecipient(
                    $recipients,
                    $reporter->email,
                    $reporter->name,
                    'reporting_person'
                );
            }

            if ($manager) {
                $this->addRecipient(
                    $recipients,
                    $manager->email,
                    $manager->name,
                    'manager'
                );
            }
        }

        $additionalRecipients = DailyWorkReportRecipient::query()
            ->effective($reportDate)
            ->where('report_key', $reportKey)
            ->where(function ($query) use ($employee) {
                $query->where('scope', 'global')
                    ->orWhere(function ($q) use ($employee) {
                        $q->where('scope', 'user')
                            ->where('scope_id', $employee->id);
                    });
            })
            ->get();

        foreach ($additionalRecipients as $recipient) {
            $this->addRecipient(
                $recipients,
                $recipient->email,
                $recipient->name,
                $recipient->scope === 'global'
                    ? 'global'
                    : 'user_specific'
            );
        }

        $teams = Team::query()
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($employee) {
                $query->where('users.id', $employee->id);
            })
            ->get();

        if ($teams->isNotEmpty()) {
            $teamRecipients = DailyWorkReportRecipient::query()
                ->effective($reportDate)
                ->where('report_key', $reportKey)
                ->where('scope', 'team')
                ->whereIn('scope_id', $teams->pluck('id'))
                ->get();

            foreach ($teamRecipients as $recipient) {
                $this->addRecipient(
                    $recipients,
                    $recipient->email,
                    $recipient->name,
                    'team'
                );
            }
        }

        return $recipients->values();
    }

    /**
     * Resolve recipients for one supervisor's consolidated report.
     *
     * The supervisor receives one report containing all employees
     * passed in the $employees collection.
     *
     * Global recipients receive every consolidated report.
     */
    public function resolveForHierarchy(
        User $reportOwner,
        Collection $employees,
        string $reportDate,
        string $reportKey = 'daily_work'
    ): Collection {
        $recipients = collect();

        // The supervisor receives the consolidated report.
        $this->addRecipient(
            $recipients,
            $reportOwner->email,
            $reportOwner->name,
            'report_owner'
        );

        // Global additional recipients receive every consolidated report.
        $globalRecipients = DailyWorkReportRecipient::query()
            ->effective($reportDate)
            ->where('report_key', $reportKey)
            ->where('scope', 'global')
            ->get();

        foreach ($globalRecipients as $recipient) {
            $this->addRecipient(
                $recipients,
                $recipient->email,
                $recipient->name,
                'global'
            );
        }

        $employees = $employees
            ->filter(fn ($employee) => $employee instanceof User)
            ->unique('id')
            ->values();

        if ($employees->isEmpty()) {
            return $recipients->values();
        }

        $employeeIds = $employees->pluck('id')->all();

        // User-specific additional recipients for all included employees.
        $userRecipients = DailyWorkReportRecipient::query()
            ->effective($reportDate)
            ->where('report_key', $reportKey)
            ->where('scope', 'user')
            ->whereIn('scope_id', $employeeIds)
            ->get();

        foreach ($userRecipients as $recipient) {
            $this->addRecipient(
                $recipients,
                $recipient->email,
                $recipient->name,
                'user_specific'
            );
        }

        // Find all active teams containing at least one included employee.
        $teams = Team::query()
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($employeeIds) {
                $query->whereIn('users.id', $employeeIds);
            })
            ->get();

        // Team-specific additional recipients.
        if ($teams->isNotEmpty()) {
            $teamRecipients = DailyWorkReportRecipient::query()
                ->effective($reportDate)
                ->where('report_key', $reportKey)
                ->where('scope', 'team')
                ->whereIn('scope_id', $teams->pluck('id'))
                ->get();

            foreach ($teamRecipients as $recipient) {
                $this->addRecipient(
                    $recipients,
                    $recipient->email,
                    $recipient->name,
                    'team'
                );
            }
        }

        return $recipients->values();
    }


    /**
     * Resolve global additional recipients separately.
     *
     * These recipients should receive one organization-wide report,
     * not one email per supervisor.
     */
    public function resolveGlobal(
        string $reportDate,
        string $reportKey = 'daily_work'
    ): Collection {
        $recipients = collect();

        $globalRecipients = DailyWorkReportRecipient::query()
            ->effective($reportDate)
            ->where('report_key', $reportKey)
            ->where('scope', 'global')
            ->get();

        foreach ($globalRecipients as $recipient) {
            $this->addRecipient(
                $recipients,
                $recipient->email,
                $recipient->name,
                'global'
            );
        }

        return $recipients->values();
    }

    /**
     * Add a recipient while deduplicating by email address.
     */
    private function addRecipient(
        Collection $recipients,
        ?string $email,
        ?string $name,
        string $source
    ): void {
        $email = trim((string) $email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $key = mb_strtolower($email);

        if (! $recipients->has($key)) {
            $recipients->put($key, [
                'email' => $email,
                'name' => $name,
                'sources' => [],
            ]);
        }

        $recipient = $recipients->get($key);

        if (! in_array($source, $recipient['sources'], true)) {
            $recipient['sources'][] = $source;
        }

        if (empty($recipient['name']) && $name) {
            $recipient['name'] = $name;
        }

        $recipients->put($key, $recipient);
    }
}
