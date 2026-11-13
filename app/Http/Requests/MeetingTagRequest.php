<?php

namespace App\Http\Requests;

use App\Models\MeetingTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => MeetingTag::generateUniqueCode()]);
        }
    }

    public function rules(): array
    {
        $id = (int) $this->route('meeting_tag') ?: null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meeting_tags', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:meeting_tags,code'];
        }

        return $rules;
    }
}
