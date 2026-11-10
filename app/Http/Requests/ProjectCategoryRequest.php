<?php

namespace App\Http\Requests;

use App\Models\ProjectCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post')) {
            $this->merge(['code' => ProjectCategory::generateUniqueCode()]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeParam = $this->route('project_category');
        $id = is_object($routeParam) ? $routeParam->id : $routeParam;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_categories', 'name')->ignore($id)->whereNull('deleted_at')],
            'sort_order' => ['required', 'numeric'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'string', 'regex:/^[a-z]{3}-\d{6}$/', 'unique:project_categories,code'];
        }

        return $rules;
    }
}
