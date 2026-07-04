<?php

namespace App\Http\Requests;

use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class TaskTimeLogChangeRequestActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isApprove = $this->route('action') === 'approve';

        return [
            'reason' => [
                $isApprove ? 'nullable' : 'required',
                'string',
                'max:2000',
            ],
            'new_started_at' => [$isApprove ? 'required' : 'nullable', 'date_format:Y-m-d H:i:s'],
            'new_ended_at' => [$isApprove ? 'required' : 'nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please enter a rejection reason.',
            'reason.max' => 'The rejection reason cannot be longer than 2000 characters.',
            'new_started_at.required' => 'Please select a requested start date and time.',
            'new_started_at.date_format' => 'The requested start date and time is invalid.',
            'new_ended_at.required' => 'Please select a requested end date and time.',
            'new_ended_at.date_format' => 'The requested end date and time is invalid.',
        ];
    }

    public function approvalTimeRange(): array
    {
        $timezone = app(CompanyService::class)->timezone();

        return [
            'new_started_at' => $this->parseDateTime($this->validated('new_started_at'), $timezone),
            'new_ended_at' => $this->parseDateTime($this->validated('new_ended_at'), $timezone),
        ];
    }

    private function parseDateTime(?string $dateTime, string $timezone): ?Carbon
    {
        if (! $dateTime) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime, $timezone)->utc();
    }
}
