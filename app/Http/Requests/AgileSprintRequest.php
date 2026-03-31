<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgileSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agileSprint = $this->route('agile_sprint');
        $agileSprintId = is_object($agileSprint) ? $agileSprint->id : $agileSprint;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('agile_sprints', 'name')->ignore($agileSprintId)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['required', 'numeric'],
            'default' => ['nullable', 'boolean'],
        ];
    }
}
