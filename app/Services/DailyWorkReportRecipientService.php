<?php

namespace App\Services;

use App\Models\DailyWorkReportRecipient;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyWorkReportRecipientService
{
    /**
     * Resolve recipients for an individual employee report.
     *
     * Includes the employee, reporting person, manager,
     * and applicable global, team, and user-specific recipients.
     */
    public function resolve(
        User $employee,
        string $reportDate,
        string $reportKey = 'daily_work'
    ): Collection {
        $recipients = collect();

        /* Employee */
        $this->addRecipient(
            $recipients,
            $employee->email,
            $employee->name,
            'employee'
        );

        /* Reporting Person and Manager */
        $details = $employee->details;

        if ($details) {
            $relatedUserIds = array_filter([
                $details->reporter_id,
                $details->manager_id,
            ]);

            $relatedUsers = User::query()
                ->whereIn('id', $relatedUserIds)
                ->get()
                ->keyBy('id');

            $reporter = $relatedUsers->get($details->reporter_id);
            $manager = $relatedUsers->get($details->manager_id);

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

        /* Global and User-specific Recipients */
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

        /* Active Team Recipients */
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

        /* Report Owner */
        $this->addRecipient(
            $recipients,
            $reportOwner->email,
            $reportOwner->name,
            'report_owner'
        );

        /* Global Recipients */
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

        /* Normalize Employees */
        $employees = $employees
            ->filter(fn ($employee) => $employee instanceof User)
            ->reject(fn ($employee) => $employee->id == $reportOwner->id)
            ->unique('id')
            ->values();

        if ($employees->isEmpty()) {
            return $recipients->values();
        }

        $employeeIds = $employees->pluck('id')->all();

        /* User-specific Recipients */
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

        /* Active Teams Containing Included Employees */
        $teams = Team::query()
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($employeeIds) {
                $query->whereIn('users.id', $employeeIds);
            })
            ->get();

        /* Team-specific Recipients */
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
     *
     * Multiple recipient sources are retained for the same email.
     */
    private function addRecipient(
        Collection $recipients,
        ?string $email,
        ?string $name,
        string $source
    ): void {
        $email = trim((string) $email);
        $name = trim((string) $name);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $email = mb_strtolower($email);
        $key = $email;

        if (! $recipients->has($key)) {
            $recipients->put($key, [
                'email' => $email,
                'name' => $name ?: null,
                'sources' => [],
            ]);
        }

        $recipient = $recipients->get($key);

        if (! in_array($source, $recipient['sources'], true)) {
            $recipient['sources'][] = $source;
        }

        if (empty($recipient['name']) && $name !== '') {
            $recipient['name'] = $name;
        }

        $recipients->put($key, $recipient);
    }
}
