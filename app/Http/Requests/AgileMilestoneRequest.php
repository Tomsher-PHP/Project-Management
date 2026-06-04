<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgileMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agileMilestone = $this->route('agile_milestone');
        $agileMilestoneId = is_object($agileMilestone) ? $agileMilestone->id : $agileMilestone;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('agile_milestones', 'name')->ignore($agileMilestoneId)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['required', 'numeric'],
        ];
    }
}
