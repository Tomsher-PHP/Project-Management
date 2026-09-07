<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('meeting_tag') ?: null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meeting_tags', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
