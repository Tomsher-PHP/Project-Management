<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgileModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agileModule = $this->route('agile_module');
        $agileModuleId = is_object($agileModule) ? $agileModule->id : $agileModule;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('agile_modules', 'name')->ignore($agileModuleId)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['required', 'numeric'],
        ];
    }
}
