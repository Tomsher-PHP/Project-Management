<?php

namespace App\Http\Requests;

use App\Models\BreakWorkRequest;
use App\Models\TaskTimeLog;
use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class BreakWorkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'original_break_start' => ['required', 'date_format:H:i'],
            'original_break_end' => ['required', 'date_format:H:i'],
            'description' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_date.required' => 'Please select a work date.',
            'work_date.date' => 'The selected work date is invalid.',
            'start_time.required' => 'Please select a start time.',
            'start_time.date_format' => 'The start time must be in HH:MM format.',
            'end_time.required' => 'Please select an end time.',
            'end_time.date_format' => 'The end time must be in HH:MM format.',
            'original_break_start.required' => 'The original break start time is missing.',
            'original_break_start.date_format' => 'The original break start time is invalid.',
            'original_break_end.required' => 'The original break end time is missing.',
            'original_break_end.date_format' => 'The original break end time is invalid.',
            'description.required' => 'Please enter a description.',
            'description.string' => 'The description must be a valid text value.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $startedAt = $this->normalizedStartedAt();
                $endedAt = $this->normalizedEndedAt();

                if (! $startedAt) {
                    $validator->errors()->add('start_time', 'The selected start time is invalid.');
                    return;
                }

                if (! $endedAt) {
                    $validator->errors()->add('end_time', 'The selected end time is invalid.');
                    return;
                }

                if (! $endedAt->greaterThan($startedAt)) {
                    $validator->errors()->add('end_time', 'The end time must be after the start time.');
                    return;
                }

                if ($startedAt->diffInSeconds($endedAt) < 180) {
                    $validator->errors()->add('end_time', 'The requested work duration must be at least 3 minutes.');
                    return;
                }

                if (! $this->fitsWithinOriginalBreakRange($startedAt, $endedAt)) {
                    $validator->errors()->add('end_time', 'The selected time must stay within the original break range.');
                    return;
                }

                if ($this->hasOverlappingTaskTimeLog($startedAt, $endedAt)) {
                    $validator->errors()->add('end_time', 'The selected time overlaps with an existing task time log.');
                    return;
                }

                if ($this->hasOverlappingPendingBreakWorkRequest($startedAt, $endedAt)) {
                    $validator->errors()->add('end_time', 'The selected time overlaps with a pending break work request.');
                }
            },
        ];
    }

    public function normalizedStartedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime(
            $this->input('work_date'),
            $this->input('start_time')
        );
    }

    public function normalizedEndedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime(
            $this->input('work_date'),
            $this->input('end_time')
        );
    }

    public function durationSeconds(): int
    {
        $startedAt = $this->normalizedStartedAt();
        $endedAt = $this->normalizedEndedAt();

        if (! $startedAt || ! $endedAt || ! $endedAt->greaterThan($startedAt)) {
            return 0;
        }

        return $startedAt->diffInSeconds($endedAt);
    }

    public function normalizedOriginalBreakStartedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime(
            $this->input('work_date'),
            $this->input('original_break_start')
        );
    }

    public function normalizedOriginalBreakEndedAt(): ?Carbon
    {
        return $this->parseCompanyDateTime(
            $this->input('work_date'),
            $this->input('original_break_end')
        );
    }

    private function hasOverlappingTaskTimeLog(Carbon $startedAt, Carbon $endedAt): bool
    {
        $userId = $this->user()?->id;

        if (! $userId) {
            return false;
        }

        return TaskTimeLog::query()
            ->where('user_id', $userId)
            ->whereNotNull('started_at')
            ->where(function ($query) use ($startedAt, $endedAt) {
                $query
                    ->where(function ($endedQuery) use ($startedAt, $endedAt) {
                        $endedQuery
                            ->whereNotNull('ended_at')
                            ->where('started_at', '<', $endedAt)
                            ->where('ended_at', '>', $startedAt);
                    })
                    ->orWhere(function ($runningQuery) use ($endedAt) {
                        $runningQuery
                            ->whereNull('ended_at')
                            ->where('started_at', '<', $endedAt);
                    });
            })
            ->exists();
    }

    private function hasOverlappingPendingBreakWorkRequest(Carbon $startedAt, Carbon $endedAt): bool
    {
        $userId = $this->user()?->id;

        if (! $userId) {
            return false;
        }

        return BreakWorkRequest::query()
            ->where('user_id', $userId)
            ->where('status', BreakWorkRequest::STATUS_PENDING)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $endedAt)
            ->where('ended_at', '>', $startedAt)
            ->exists();
    }

    private function fitsWithinOriginalBreakRange(Carbon $startedAt, Carbon $endedAt): bool
    {
        $originalStartedAt = $this->normalizedOriginalBreakStartedAt();
        $originalEndedAt = $this->normalizedOriginalBreakEndedAt();

        if (! $originalStartedAt || ! $originalEndedAt || ! $originalEndedAt->greaterThan($originalStartedAt)) {
            return false;
        }

        return $startedAt->greaterThanOrEqualTo($originalStartedAt)
            && $endedAt->lessThanOrEqualTo($originalEndedAt);
    }

    private function parseCompanyDateTime(mixed $date, mixed $time): ?Carbon
    {
        if (! filled($date) || ! filled($time)) {
            return null;
        }

        try {
            $timezone = config('constants.timezone', 'UTC');

            return Carbon::createFromFormat('Y-m-d H:i', trim((string) $date) . ' ' . trim((string) $time), $timezone)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
