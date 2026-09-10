<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('start_at') && (! $this->has('end_at') || empty($this->input('end_at')))) {
            $durationMinutes = (int) $this->input('duration_minutes', 60);
            if ($durationMinutes > 0) {
                try {
                    $start = \Carbon\Carbon::parse($this->input('start_at'));
                    $this->merge([
                        'end_at' => $start->copy()->addMinutes($durationMinutes)->format('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                    // ignore invalid date error to be caught by rules
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'meeting_type_id' => 'required|exists:meeting_types,id',
            'meeting_location_id' => 'nullable|exists:meeting_locations,id',
            'meeting_status_id' => 'nullable|exists:meeting_statuses,id',
            'organizer_id' => 'required|exists:users,id',
            'start_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'end_at' => 'required|date|after_or_equal:start_at',
            'url' => 'nullable|string|max:2048',
            'location_details' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:meeting_tags,id',

            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,xls,xlsx,doc,docx,ppt,pptx,jpg,jpeg,png|max:15360',

            'participants' => 'nullable|array',
            'participants.*.user_id' => 'nullable|exists:users,id',
            'participants.*.is_external' => 'nullable|boolean',
            'participants.*.name' => 'nullable|string|max:255',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
            'participants.*.send_email' => 'nullable|boolean',
        ];
    }

    /**
     * Custom validation after rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $participants = $this->input('participants', []);
            if (! is_array($participants)) {
                return;
            }

            foreach ($participants as $index => $participant) {
                $isExternal = filter_var($participant['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $hasUserId = ! empty($participant['user_id']);
                $hasName = ! empty($participant['name']);
                $hasEmail = ! empty($participant['email']);

                // If completely empty participant row, skip validation
                if (! $hasUserId && ! $hasName && ! $hasEmail) {
                    continue;
                }

                if ($isExternal) {
                    if (empty($participant['name'])) {
                        $validator->errors()->add("participants.{$index}.name", 'External participant name is required.');
                    }
                    if (empty($participant['email'])) {
                        $validator->errors()->add("participants.{$index}.email", 'External participant email is required.');
                    } elseif (! filter_var($participant['email'], FILTER_VALIDATE_EMAIL)) {
                        $validator->errors()->add("participants.{$index}.email", 'External participant email must be a valid email address.');
                    }
                } else {
                    if (empty($participant['user_id'])) {
                        $validator->errors()->add("participants.{$index}.user_id", 'Internal participant user selection is required.');
                    }
                }
            }
        });
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'end_at.after_or_equal' => 'The end date and time must be equal to or after the start date and time.',
            'meeting_type_id.required' => 'Please select a meeting type.',
            'organizer_id.required' => 'Please select an organizer.',
            'attachments.max' => 'You can upload a maximum of 5 files at a time.',
        ];
    }
}
