<?php

namespace App\Http\Requests;

use App\Models\TaskTimeLogChangeRequest;
use Illuminate\Validation\Validator;

class UpdateTaskTimeLogChangeRequest extends StoreTaskTimeLogChangeRequest
{
    public function authorize(): bool
    {
        $changeRequest = $this->route('changeRequest');

        return $changeRequest instanceof TaskTimeLogChangeRequest
            && (int) $changeRequest->user_id === (int) $this->user()?->id
            && $changeRequest->isPending();
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator) {
                $changeRequest = $this->route('changeRequest');

                if (
                    $changeRequest instanceof TaskTimeLogChangeRequest
                    && (int) $changeRequest->task_time_log_id !== (int) $this->integer('task_time_log_id')
                ) {
                    $validator->errors()->add('task_time_log_id', 'The selected time log does not belong to this change request.');
                }
            },
        ];
    }

    protected function currentChangeRequestId(): ?int
    {
        $changeRequest = $this->route('changeRequest');

        return $changeRequest instanceof TaskTimeLogChangeRequest
            ? (int) $changeRequest->id
            : null;
    }
}
