<?php

namespace App\Services;

use App\Jobs\DispatchBroadcastEmailsJob;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\BroadcastPublishedMail;
use App\Models\Broadcast;
use App\Models\BroadcastEmailDelivery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BroadcastEmailService
{
    /**
     * Default per-message recipient limit (To + CC + BCC) matching Gmail SMTP / RFC 5321 minimum.
     */
    public const DEFAULT_MAX_RECIPIENTS_PER_MESSAGE = 100;

    /**
     * Initiate broadcast email queueing if publication conditions are satisfied.
     */
    public function queueBroadcastPublication(Broadcast $broadcast): void
    {
        if ((int) $broadcast->status !== Broadcast::STATUS_PUBLISHED) {
            return;
        }

        // Avoid re-queueing if delivery records already exist for this broadcast
        if ($broadcast->emailDeliveries()->exists()) {
            Log::info("Broadcast [{$broadcast->id}] already has email delivery records. Skipping queue.");
            return;
        }

        DispatchBroadcastEmailsJob::dispatch($broadcast->id);
    }

    /**
     * Resolve recipients, prepare delivery tracking, and queue one send job for the broadcast.
     */
    public function dispatchBroadcast(int $broadcastId): void
    {
        $broadcast = Broadcast::with(['category', 'attachments'])->find($broadcastId);

        if (! $broadcast || (int) $broadcast->status !== Broadcast::STATUS_PUBLISHED) {
            Log::info("Broadcast [{$broadcastId}] is not published. Skipping dispatch.");
            return;
        }

        $recipients = $this->resolveEligibleRecipients($broadcast);

        if ($recipients->isEmpty()) {
            Log::info("Broadcast [{$broadcast->id}] has no eligible active recipients.");
            return;
        }

        $prepared = DB::transaction(function () use ($broadcast, $recipients): bool {
            $lockedBroadcast = Broadcast::query()->whereKey($broadcast->id)->lockForUpdate()->first();

            if (! $lockedBroadcast || (int) $lockedBroadcast->status !== Broadcast::STATUS_PUBLISHED) {
                return false;
            }

            if (BroadcastEmailDelivery::query()->where('broadcast_id', $broadcast->id)->exists()) {
                return false;
            }

            $now = now();
            $rows = $recipients->map(fn (User $recipient) => [
                'broadcast_id' => $broadcast->id,
                'user_id' => $recipient->id,
                'email' => trim((string) $recipient->email),
                'status' => BroadcastEmailDelivery::STATUS_PENDING,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                BroadcastEmailDelivery::insert($chunk);
            }

            return true;
        });

        if (! $prepared) {
            Log::info("Broadcast [{$broadcast->id}] email deliveries are already prepared or broadcast is no longer published.");
            return;
        }

        try {
            SendBroadcastEmailJob::dispatch($broadcast->id);
        } catch (\Throwable $e) {
            // Roll back freshly created pending records so a job retry can re-prepare cleanly
            BroadcastEmailDelivery::query()
                ->where('broadcast_id', $broadcast->id)
                ->where('status', BroadcastEmailDelivery::STATUS_PENDING)
                ->delete();

            throw $e;
        }

        Log::info("Broadcast [{$broadcast->id}] prepared " . $recipients->count() . ' recipient(s) and queued 1 BCC email job.');
    }

    /**
     * Resolve eligible recipients for a broadcast based on audience type, deduplicated by email.
     *
     * @return Collection<int, User>
     */
    public function resolveEligibleRecipients(Broadcast $broadcast): Collection
    {
        $query = $broadcast->audience_type === Broadcast::AUDIENCE_ALL
            ? User::query()
                ->active()
                ->where('delete_status', false)
                ->whereNull('deleted_at')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->select(['id', 'email', 'name'])
            : User::query()
                ->join('broadcast_users', 'broadcast_users.user_id', '=', 'users.id')
                ->where('broadcast_users.broadcast_id', $broadcast->id)
                ->active()
                ->where('users.delete_status', false)
                ->whereNull('users.deleted_at')
                ->whereNotNull('users.email')
                ->where('users.email', '!=', '')
                ->select(['users.id', 'users.email', 'users.name']);

        $uniqueByEmail = [];
        $column = $broadcast->audience_type === Broadcast::AUDIENCE_ALL ? 'id' : 'users.id';

        $query->chunkById(500, function (Collection $users) use (&$uniqueByEmail): void {
            foreach ($users as $user) {
                $email = trim((string) $user->email);

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $normalized = strtolower($email);
                if (! isset($uniqueByEmail[$normalized])) {
                    $user->email = $email;
                    $uniqueByEmail[$normalized] = $user;
                }
            }
        }, $column, 'id');

        return collect(array_values($uniqueByEmail));
    }

    /**
     * Send a single BCC email for all prepared eligible recipients of a broadcast.
     */
    public function sendBroadcast(int $broadcastId): void
    {
        if (! $this->claimBroadcastForSending($broadcastId)) {
            return;
        }

        /** @var Broadcast|null $broadcast */
        $broadcast = Broadcast::with(['category', 'attachments'])->find($broadcastId);

        if (! $broadcast || (int) $broadcast->status !== Broadcast::STATUS_PUBLISHED) {
            $this->markProcessingDeliveriesFailed(
                $broadcastId,
                'Skipped: Broadcast is not published or was archived prior to delivery.'
            );
            return;
        }

        $senderEmail = trim((string) config('mail.from.address', ''));
        if ($senderEmail === '' || ! filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Skipped: MAIL_FROM_ADDRESS is not configured or invalid.';
            Log::error("Broadcast [{$broadcastId}] email delivery aborted: {$message}");
            $this->markProcessingDeliveriesFailed($broadcastId, $message);
            return;
        }

        $deliveries = BroadcastEmailDelivery::query()
            ->with('user')
            ->where('broadcast_id', $broadcastId)
            ->where('status', BroadcastEmailDelivery::STATUS_PROCESSING)
            ->orderBy('id')
            ->get();

        [$eligibleIds, $ineligibleIds, $bccEmails] = $this->partitionRecipientsForSending($deliveries, $senderEmail);

        if ($ineligibleIds !== []) {
            BroadcastEmailDelivery::query()
                ->whereIn('id', $ineligibleIds)
                ->update([
                    'status' => BroadcastEmailDelivery::STATUS_FAILED,
                    'error_message' => 'Recipient account is inactive, deleted, duplicate, or has an invalid email address.',
                ]);
        }

        if ($eligibleIds === []) {
            Log::info("Broadcast [{$broadcastId}] has no eligible recipients remaining at send time.");
            return;
        }

        $maxRecipients = $this->getMaxRecipientsPerMessage();
        // Account for the configured To address (MAIL_FROM_ADDRESS) plus all BCC recipients.
        $totalRecipients = 1 + count($bccEmails);

        if ($totalRecipients > $maxRecipients) {
            $message = "Recipient count ({$totalRecipients}, including To and " . count($bccEmails) . " BCC) exceeds the configured SMTP recipient limit of {$maxRecipients} per message.";
            Log::error("Broadcast [{$broadcastId}] email delivery failed: {$message}", [
                'broadcast_id' => $broadcastId,
                'bcc_count' => count($bccEmails),
                'total_recipients' => $totalRecipients,
                'max_recipients' => $maxRecipients,
            ]);

            BroadcastEmailDelivery::query()
                ->whereIn('id', $eligibleIds)
                ->update([
                    'status' => BroadcastEmailDelivery::STATUS_FAILED,
                    'error_message' => Str::limit($message, 1000),
                ]);

            return;
        }

        try {
            $pendingMail = Mail::to($senderEmail);
            if ($bccEmails !== []) {
                $pendingMail->bcc($bccEmails);
            }
            $pendingMail->send(new BroadcastPublishedMail($broadcast));

            BroadcastEmailDelivery::query()
                ->whereIn('id', $eligibleIds)
                ->update([
                    'status' => BroadcastEmailDelivery::STATUS_SENT,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

            Log::info("Broadcast [{$broadcastId}] sent 1 email (To: {$senderEmail}, BCC: " . count($bccEmails) . ' recipient(s)).');
        } catch (\Throwable $e) {
            BroadcastEmailDelivery::query()
                ->whereIn('id', $eligibleIds)
                ->update([
                    'status' => BroadcastEmailDelivery::STATUS_FAILED,
                    'error_message' => Str::limit($e->getMessage(), 1000),
                ]);

            throw $e;
        }
    }

    /**
     * Atomically claim pending or failed delivery records for a broadcast to prevent concurrent sends.
     */
    protected function claimBroadcastForSending(int $broadcastId): bool
    {
        return DB::transaction(function () use ($broadcastId): bool {
            Broadcast::query()->whereKey($broadcastId)->lockForUpdate()->first();

            $deliveries = BroadcastEmailDelivery::query()
                ->where('broadcast_id', $broadcastId)
                ->lockForUpdate()
                ->get();

            if ($deliveries->isEmpty()) {
                return false;
            }

            $alreadyActiveOrSent = $deliveries->contains(function (BroadcastEmailDelivery $delivery): bool {
                return in_array((int) $delivery->status, [
                    BroadcastEmailDelivery::STATUS_PROCESSING,
                    BroadcastEmailDelivery::STATUS_SENT,
                ], true);
            });

            if ($alreadyActiveOrSent) {
                return false;
            }

            $updated = BroadcastEmailDelivery::query()
                ->where('broadcast_id', $broadcastId)
                ->whereIn('status', [
                    BroadcastEmailDelivery::STATUS_PENDING,
                    BroadcastEmailDelivery::STATUS_FAILED,
                ])
                ->update([
                    'status' => BroadcastEmailDelivery::STATUS_PROCESSING,
                    'last_attempt_at' => now(),
                    'attempts' => DB::raw('attempts + 1'),
                ]);

            return $updated > 0;
        });
    }

    /**
     * Re-validate delivery records at send time and extract unique BCC email addresses.
     *
     * @param  Collection<int, BroadcastEmailDelivery>  $deliveries
     * @return array{0: array<int, int>, 1: array<int, int>, 2: array<int, string>}
     */
    protected function partitionRecipientsForSending(Collection $deliveries, string $senderEmail): array
    {
        $eligibleIds = [];
        $ineligibleIds = [];
        $bccEmails = [];
        $seenEmails = [];

        foreach ($deliveries as $delivery) {
            $recipient = $delivery->user;
            $email = trim((string) $delivery->email);
            $normalizedEmail = strtolower($email);

            $isUserEligible = $recipient
                && $recipient->is_active
                && ! $recipient->delete_status
                && ! $recipient->deleted_at;

            if (! $isUserEligible || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $ineligibleIds[] = $delivery->id;
                continue;
            }

            if (isset($seenEmails[$normalizedEmail])) {
                $ineligibleIds[] = $delivery->id;
                continue;
            }

            $seenEmails[$normalizedEmail] = true;
            $eligibleIds[] = $delivery->id;

            // Avoid duplicating MAIL_FROM_ADDRESS in BCC since it is already in To.
            if (strcasecmp($email, $senderEmail) !== 0) {
                $bccEmails[] = $email;
            }
        }

        return [$eligibleIds, $ineligibleIds, $bccEmails];
    }

    /**
     * Mark all currently processing deliveries for a broadcast as failed.
     */
    protected function markProcessingDeliveriesFailed(int $broadcastId, string $message): void
    {
        BroadcastEmailDelivery::query()
            ->where('broadcast_id', $broadcastId)
            ->where('status', BroadcastEmailDelivery::STATUS_PROCESSING)
            ->update([
                'status' => BroadcastEmailDelivery::STATUS_FAILED,
                'error_message' => Str::limit($message, 1000),
            ]);
    }

    /**
     * Resolve the configured per-message recipient limit.
     */
    public function getMaxRecipientsPerMessage(): int
    {
        $configured = config('mail.max_recipients');

        return is_numeric($configured) && (int) $configured > 0
            ? (int) $configured
            : self::DEFAULT_MAX_RECIPIENTS_PER_MESSAGE;
    }
}
