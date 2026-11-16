<?php

namespace App\Http\Requests;

use App\Models\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectStageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => ProjectStage::generateUniqueCode()]);
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
        $routeParam = $this->route('project_stage');
        $id = is_object($routeParam) ? $routeParam->id : $routeParam;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_stages', 'name')->ignore($id)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'sort_order' => ['required', 'numeric'],
            'is_default' => ['boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'digits:6', 'unique:project_stages,code'];
        }

        return $rules;
    }
}
