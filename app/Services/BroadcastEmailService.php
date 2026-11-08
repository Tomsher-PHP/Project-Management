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
     * Resolve recipients and prepare delivery records for a broadcast.
     */
    public function dispatchBroadcast(int $broadcastId): void
    {
        $broadcast = Broadcast::with(['category', 'attachments'])->find($broadcastId);

        if (! $broadcast || (int) $broadcast->status !== Broadcast::STATUS_PUBLISHED) {
            Log::info("Broadcast [{$broadcastId}] is not published. Skipping dispatch.");
            return;
        }

        // Check if delivery records were already prepared
        $alreadyPrepared = BroadcastEmailDelivery::query()
            ->where('broadcast_id', $broadcast->id)
            ->whereIn('status', [
                BroadcastEmailDelivery::STATUS_PROCESSING,
                BroadcastEmailDelivery::STATUS_SENT,
            ])
            ->exists();

        if ($alreadyPrepared) {
            Log::info("Broadcast [{$broadcast->id}] email deliveries are already in progress or sent.");
            return;
        }

        $recipients = $this->resolveEligibleRecipients($broadcast);

        if ($recipients->isEmpty()) {
            Log::info("Broadcast [{$broadcast->id}] has no eligible active recipients.");
            return;
        }

        $now = now();
        $pendingDeliveryIds = [];

        foreach ($recipients as $recipient) {
            /** @var BroadcastEmailDelivery $delivery */
            $delivery = BroadcastEmailDelivery::firstOrCreate(
                [
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $recipient->id,
                ],
                [
                    'email' => $recipient->email,
                    'status' => BroadcastEmailDelivery::STATUS_PENDING,
                    'attempts' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if ($delivery->wasRecentlyCreated || (int) $delivery->status === BroadcastEmailDelivery::STATUS_PENDING) {
                $pendingDeliveryIds[] = $delivery->id;
            }
        }

        // Queue individual recipient delivery jobs in chunks
        foreach (array_chunk($pendingDeliveryIds, 100) as $chunk) {
            foreach ($chunk as $deliveryId) {
                SendBroadcastEmailJob::dispatch($deliveryId);
            }
        }

        Log::info("Broadcast [{$broadcast->id}] queued " . count($pendingDeliveryIds) . " email deliveries.");
    }

    /**
     * Resolve eligible recipients for a broadcast based on audience type.
     *
     * @return Collection<int, User>
     */
    public function resolveEligibleRecipients(Broadcast $broadcast): Collection
    {
        if ($broadcast->audience_type === Broadcast::AUDIENCE_ALL) {
            return User::query()
                ->active()
                ->where('delete_status', false)
                ->whereNull('deleted_at')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->select(['id', 'email', 'name'])
                ->get()
                ->filter(fn ($user) => filter_var($user->email, FILTER_VALIDATE_EMAIL))
                ->unique('id')
                ->values();
        }

        // Audience type: teams or users (both use stored broadcast_users allocations)
        return User::query()
            ->join('broadcast_users', 'broadcast_users.user_id', '=', 'users.id')
            ->where('broadcast_users.broadcast_id', $broadcast->id)
            ->active()
            ->where('users.delete_status', false)
            ->whereNull('users.deleted_at')
            ->whereNotNull('users.email')
            ->where('users.email', '!=', '')
            ->select(['users.id', 'users.email', 'users.name'])
            ->distinct()
            ->get()
            ->filter(fn ($user) => filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->unique('id')
            ->values();
    }

    /**
     * Process an individual broadcast email delivery record.
     */
    public function sendDelivery(int $deliveryId): void
    {
        // Atomically claim the delivery record to prevent concurrent workers from sending duplicates
        $claimed = BroadcastEmailDelivery::query()
            ->where('id', $deliveryId)
            ->whereIn('status', [
                BroadcastEmailDelivery::STATUS_PENDING,
                BroadcastEmailDelivery::STATUS_FAILED,
            ])
            ->update([
                'status' => BroadcastEmailDelivery::STATUS_PROCESSING,
                'last_attempt_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
            ]);

        if ($claimed === 0) {
            // Already claimed or sent by another worker
            return;
        }

        /** @var BroadcastEmailDelivery|null $delivery */
        $delivery = BroadcastEmailDelivery::with(['broadcast.category', 'broadcast.attachments', 'user'])->find($deliveryId);

        if (! $delivery) {
            return;
        }

        $broadcast = $delivery->broadcast;
        $recipient = $delivery->user;

        // Skip if broadcast missing or was archived prior to delivery
        if (! $broadcast || (int) $broadcast->status !== Broadcast::STATUS_PUBLISHED) {
            $delivery->update([
                'status' => BroadcastEmailDelivery::STATUS_FAILED,
                'error_message' => 'Skipped: Broadcast is not published or was archived prior to delivery.',
            ]);
            return;
        }

        // Verify recipient eligibility
        if (
            ! $recipient
            || ! $recipient->is_active
            || $recipient->delete_status
            || $recipient->deleted_at
            || ! filter_var($delivery->email, FILTER_VALIDATE_EMAIL)
        ) {
            $delivery->update([
                'status' => BroadcastEmailDelivery::STATUS_FAILED,
                'error_message' => 'Recipient account is inactive, deleted, or has an invalid email address.',
            ]);
            return;
        }

        try {
            Mail::to($delivery->email)->send(new BroadcastPublishedMail($broadcast, $recipient));

            $delivery->update([
                'status' => BroadcastEmailDelivery::STATUS_SENT,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => BroadcastEmailDelivery::STATUS_FAILED,
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }
}

