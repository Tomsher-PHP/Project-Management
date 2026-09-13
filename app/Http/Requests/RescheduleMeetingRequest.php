<?php

namespace App\Http\Requests;

class RescheduleMeetingRequest extends MeetingRequest
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
        parent::prepareForValidation();

        // Strip status and self-reference inputs so backend remains authoritative
        $this->request->remove('meeting_status_id');
        $this->request->remove('status');
        $this->request->remove('status_code');
        $this->request->remove('rescheduled_from_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['meeting_status_id']);

        $rules['reschedule_reason'] = 'required|string|max:2048';

        return $rules;
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'reschedule_reason.required' => 'Please provide a reason for rescheduling the meeting.',
            'reschedule_reason.max' => 'The reschedule reason may not be greater than 2048 characters.',
        ]);
    }
}
