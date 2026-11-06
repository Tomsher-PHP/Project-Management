<?php

namespace App\Http\Requests;

use App\Models\Broadcast;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('post') ? 'broadcast.create' : 'broadcast.edit';

        return (bool) $this->user()?->can($permission);
    }

    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        if (is_string($content)) {
            $plainText = trim(str_replace("\xc2\xa0", ' ', strip_tags($content)));
            $this->merge(['content' => $plainText === '' ? null : $content]);
        }

        $this->merge([
            'status' => (int) $this->input('status', Broadcast::STATUS_DRAFT),
            'team_ids' => array_values(array_unique(array_filter((array) $this->input('team_ids', []), 'filled'))),
            'user_ids' => array_values(array_unique(array_filter((array) $this->input('user_ids', []), 'filled'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'broadcast_category_id' => [
                'required',
                'integer',
                Rule::exists('broadcast_categories', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'priority' => ['required', 'integer', Rule::in(array_keys(Broadcast::priorityOptions()))],
            'audience_type' => ['required', Rule::in(array_keys(Broadcast::audienceOptions()))],
            'team_ids' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('audience_type') === Broadcast::AUDIENCE_TEAMS)],
            'team_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('teams', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'user_ids' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('audience_type') === Broadcast::AUDIENCE_USERS)],
            'user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')
                    ->where('is_active', true)
                    ->where('delete_status', false)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', 'integer', Rule::in($this->isMethod('post')
                ? [Broadcast::STATUS_DRAFT, Broadcast::STATUS_PUBLISHED]
                : [Broadcast::STATUS_DRAFT, Broadcast::STATUS_PUBLISHED, Broadcast::STATUS_ARCHIVED])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('start_date'), ['after_or_equal:start_date']),
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,xls,xlsx,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'broadcast_category_id.required' => 'Please select a category.',
            'team_ids.required' => 'Please select at least one team.',
            'user_ids.required' => 'Please select at least one user.',
            'end_date.after_or_equal' => 'The end date must be equal to or after the start date.',
            'attachments.max' => 'You can upload a maximum of 5 files at a time.',
            'attachments.*.max' => 'Each attachment may not be larger than 15 MB.',
        ];
    }
}

