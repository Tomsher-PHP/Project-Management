<?php

namespace App\Http\Requests;

use App\Models\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => ProjectStatus::generateUniqueCode()]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = array_keys(config('project_constants.project_status_types'));
        $routeParam = $this->route('project_status');
        $id = is_object($routeParam) ? $routeParam->id : $routeParam;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_statuses', 'name')->ignore($id)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'type' => ['required', 'string', Rule::in($types)],
            'sort_order' => ['required', 'numeric'],
            'is_default' => ['boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:project_statuses,code'];
        }

        return $rules;
    }
}
