<?php

namespace App\Console\Commands;

use App\Models\CustomerPasswordReset;
use Illuminate\Console\Command;

class PruneCustomerPasswordResets extends Command
{
    protected $signature = 'customer-portal:prune-password-resets
        {--dry-run : Preview the number of obsolete records eligible for deletion without deleting them}';

    protected $description = 'Prune obsolete Customer Portal password reset records beyond the configured retention period';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('customer_portal.password_reset.retention_days', 7));
        $cutoff = now()->subDays($retentionDays);
        $now = now();

        $baseQuery = CustomerPasswordReset::query()
            ->where('created_at', '<', $cutoff)
            ->where(function ($query) use ($now) {
                $query->whereNotNull('invalidated_at')
                    ->orWhereNotNull('used_at')
                    ->orWhere(function ($expired) use ($now) {
                        $expired->where('expires_at', '<', $now)
                            ->where(function ($resetExpired) use ($now) {
                                $resetExpired->whereNull('reset_expires_at')
                                    ->orWhere('reset_expires_at', '<', $now);
                            });
                    });
            });

        if ($this->option('dry-run')) {
            $count = (clone $baseQuery)->count();
            $this->info("Dry run: {$count} obsolete customer password reset record(s) eligible for deletion.");

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $batchDeleted = (clone $baseQuery)->limit(1000)->delete();
            $deleted += $batchDeleted;
        } while ($batchDeleted > 0);

        $this->info("Deleted {$deleted} obsolete customer password reset record(s).");

        return self::SUCCESS;
    }
}
