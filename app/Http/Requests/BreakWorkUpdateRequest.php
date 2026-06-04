<?php

namespace App\Http\Requests;

use App\Models\BreakWorkRequest;

class BreakWorkUpdateRequest extends BreakWorkStoreRequest
{
    public function authorize(): bool
    {
        $breakWorkRequest = $this->route('breakWorkRequest');

        return $breakWorkRequest instanceof BreakWorkRequest
            && (int) $breakWorkRequest->user_id === (int) $this->user()?->id
            && $breakWorkRequest->isPending();
    }

    protected function currentBreakWorkRequestId(): ?int
    {
        $breakWorkRequest = $this->route('breakWorkRequest');

        return $breakWorkRequest instanceof BreakWorkRequest
            ? (int) $breakWorkRequest->id
            : null;
    }
}
