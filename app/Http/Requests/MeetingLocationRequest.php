<?php

namespace App\Http\Requests;

use App\Models\MeetingLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => MeetingLocation::generateUniqueCode()]);
        }
    }

    public function rules(): array
    {
        $id = (int) $this->route('meeting_location') ?: null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meeting_locations', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:meeting_locations,code'];
        }

        return $rules;
    }
}
