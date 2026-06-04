<?php

namespace App\Jobs;

use App\Models\BreakWorkRequest;
use App\Services\BreakRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessApprovedBreakWorkRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $breakWorkRequestIds
     */
    public function __construct(public array $breakWorkRequestIds)
    {
        $this->afterCommit();

        $this->breakWorkRequestIds = collect($breakWorkRequestIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function handle(BreakRequestService $breakRequestService): void
    {
        $breakRequests = BreakWorkRequest::query()
            ->whereIn('id', $this->breakWorkRequestIds)
            ->where('status', BreakWorkRequest::STATUS_APPROVED)
            ->where('processing_status', BreakWorkRequest::PROCESSING_STATUS_PENDING)
            ->with('user:id,name')
            ->get()
            ->keyBy('id');

        foreach ($this->breakWorkRequestIds as $breakWorkRequestId) {
            $breakRequest = $breakRequests->get($breakWorkRequestId);

            if (! $breakRequest) {
                continue;
            }

            $breakRequestService->processApprovedRequest($breakRequest);
        }
    }
}
