<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SprintGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sprintGroup = $this->route('sprint_group');
        $sprintGroupId = is_object($sprintGroup) ? $sprintGroup->id : $sprintGroup;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('sprint_groups', 'name')->ignore($sprintGroupId)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'sort_order' => ['required', 'numeric'],
        ];
    }
}
