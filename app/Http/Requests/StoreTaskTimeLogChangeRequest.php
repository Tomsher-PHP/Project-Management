<?php

namespace App\Http\Requests;

use App\Models\TaskTimeLog;
use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function Symfony\Component\Clock\now;

class StoreTaskTimeLogChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')],
            'task_time_log_id' => ['required', 'integer', Rule::exists('task_time_logs', 'id')],
            'new_started_at' => ['required', 'date'],
            'new_ended_at' => ['required', 'date', 'after:new_started_at'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'task_id.required' => 'The selected task is invalid.',
            'task_id.exists' => 'The selected task is invalid.',
            'task_time_log_id.required' => 'Please choose a time log.',
            'task_time_log_id.exists' => 'The selected time log is invalid.',
            'new_started_at.required' => 'Please select a new start date and time.',
            'new_started_at.date' => 'The new start date and time is invalid.',
            'new_ended_at.required' => 'Please select a new end date and time.',
            'new_ended_at.date' => 'The new end date and time is invalid.',
            'new_ended_at.after' => 'The new end date and time must be later than the new start date and time.',
            'new_ended_at.before_or_equal' => 'The new end date and time cannot be in the future.',
            'reason.required' => 'Please enter a reason for this change request.',
            'reason.max' => 'The reason may not be greater than 1000 characters.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $timeLog = $this->resolveTimeLog();

                if (! $timeLog) {
                    return;
                }

                if ((int) $timeLog->task_id !== (int) $this->integer('task_id')) {
                    $validator->errors()->add('task_time_log_id', 'The selected time log does not belong to this task.');

                    return;
                }

                if ((int) $timeLog->user_id !== (int) $this->user()?->id) {
                    $validator->errors()->add('task_time_log_id', 'You can only request changes for your own time logs.');
                }

                if ((bool) $timeLog->is_running) {
                    $validator->errors()->add('task_time_log_id', 'Stop the running timer before requesting a time change.');
                }

                if ($timeLog->task?->isRejectedRequest()) {
                    $validator->errors()->add('task_id', 'Time changes are unavailable for rejected tasks.');
                }

                $hasPendingChangeRequest = $timeLog->changeRequests()
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPendingChangeRequest) {
                    $validator->errors()->add('task_time_log_id', 'A pending time change request already exists for this log.');
                }

                $newStartedAt = $this->normalizedNewStartedAt();
                $newEndedAt = $this->normalizedNewEndedAt();

                if (! $newStartedAt || ! $newEndedAt) {
                    return;
                }

                if ($newEndedAt->greaterThan(now())) {
                    $validator->errors()->add('new_ended_at', 'The new end date and time cannot be in the future.');

                    return;
                }

                $applyTimeRangeOverlapScope = function ($query) use ($newStartedAt, $newEndedAt) {
                    $query
                        ->where(function ($endedQuery) use ($newStartedAt, $newEndedAt) {
                            $endedQuery
                                ->whereNotNull('ended_at')
                                ->where('started_at', '<', $newEndedAt)
                                ->where('ended_at', '>', $newStartedAt);
                        })
                        ->orWhere(function ($runningQuery) use ($newEndedAt) {
                            $runningQuery
                                ->whereNull('ended_at')
                                ->where('started_at', '<', $newEndedAt);
                        });
                };

                $hasUserOverlapAcrossAnyTask = TaskTimeLog::query()
                    ->where('user_id', $timeLog->user_id)
                    ->whereKeyNot($timeLog->id)
                    ->where($applyTimeRangeOverlapScope)
                    ->exists();

                if ($hasUserOverlapAcrossAnyTask) {
                    $message = 'You already have another time log in the requested time range.';

                    $validator->errors()->add('new_started_at', $message);
                    $validator->errors()->add('new_ended_at', $message);
                }

                $hasTaskOverlapByAnyUser = TaskTimeLog::query()
                    ->where('task_id', $timeLog->task_id)
                    ->whereKeyNot($timeLog->id)
                    ->where($applyTimeRangeOverlapScope)
                    ->exists();

                if ($hasTaskOverlapByAnyUser) {
                    $message = 'Another user already has a time log in this task for the requested time range.';

                    $validator->errors()->add('new_started_at', $message);
                    $validator->errors()->add('new_ended_at', $message);
                }
            },
        ];
    }

    public function normalizedNewStartedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime($this->input('new_started_at'));
    }

    public function normalizedNewEndedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime($this->input('new_ended_at'));
    }

    public function resolveTimeLog(): ?TaskTimeLog
    {
        $timeLogId = $this->integer('task_time_log_id');

        if (! $timeLogId) {
            return null;
        }

        return TaskTimeLog::query()
            ->with('task:id,request_status')
            ->find($timeLogId);
    }

    private function parseCompanyDateTime(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }
        try {
            $timezone = app(CompanyService::class)->timezone();
            return Carbon::parse((string) $value, $timezone)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
