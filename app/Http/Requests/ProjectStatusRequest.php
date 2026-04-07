<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $rawCode = $this->input('code');
        $rawName = $this->input('name');
        $source = filled($rawCode) ? $rawCode : $rawName;

        $normalizedCode = filled($source)
            ? Str::of($source)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->value()
            : null;

        $this->merge([
            'code' => $normalizedCode,
        ]);
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
        $id = $this->route('project_status') ?? null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('project_statuses', 'name')->ignore($id)],
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::unique('project_statuses', 'code')->ignore($id)],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'type' => ['required', 'string', Rule::in(['open', 'in_progress', 'closed'])],
            'sort_order' => ['required', 'numeric'],
            'is_completed' => ['nullable', 'boolean'],
        ];
    }
}
