<?php

namespace App\Http\Requests;

use App\Models\MeetingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => MeetingType::generateUniqueCode()]);
        }
    }

    public function rules(): array
    {
        $id = (int) $this->route('meeting_type') ?: null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meeting_types', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:meeting_types,code'];
        }

        return $rules;
    }
}
