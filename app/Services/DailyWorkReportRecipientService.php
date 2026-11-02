<?php

namespace App\Services;

use App\Models\DailyWorkReportRecipient;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyWorkReportRecipientService
{
    public function resolve(User $employee, string $reportDate): Collection
    {
        $recipients = collect();

        $add = function (
            ?string $email,
            ?string $name,
            string $source
        ) use (&$recipients): void {
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
        };

        // The employee always receives their own report.
        $add($employee->email, $employee->name, 'employee');

        // Reporting person and manager are stored in user_details.
        $details = $employee->details;

        if ($details) {
            $reporter = User::find($details->reporter_id);
            $manager = User::find($details->manager_id);

            if ($reporter) {
                $add($reporter->email, $reporter->name, 'reporting_person');
            }

            if ($manager) {
                $add($manager->email, $manager->name, 'manager');
            }
        }

        $additionalRecipients = DailyWorkReportRecipient::query()
            ->effective($reportDate)
            ->where(function ($query) use ($employee) {
                $query->where('scope', 'global')
                    ->orWhere(function ($q) use ($employee) {
                        $q->where('scope', 'user')
                            ->where('scope_id', $employee->id);
                    });
            })
            ->get();

        foreach ($additionalRecipients as $recipient) {
            $add(
                $recipient->email,
                $recipient->name,
                $recipient->scope === 'global'
                    ? 'global'
                    : 'user_specific'
            );
        }

        // Use the existing Team::users() relationship and team_user pivot.
        $teams = Team::query()
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($employee) {
                $query->where('users.id', $employee->id);
            })
            ->get();

        $teamIds = $teams->pluck('id');

        if ($teamIds->isNotEmpty()) {
            $teamRecipients = DailyWorkReportRecipient::query()
                ->effective($reportDate)
                ->where('scope', 'team')
                ->whereIn('scope_id', $teamIds)
                ->get();

            foreach ($teamRecipients as $recipient) {
                $add(
                    $recipient->email,
                    $recipient->name,
                    'team'
                );
            }
        }

        return $recipients->values();
    }
}