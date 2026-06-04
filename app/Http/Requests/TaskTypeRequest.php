<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (filled($code)) {
            $code = Str::of($code)
                ->trim()
                ->lower()
                ->replaceMatches('/\s+/', '_')
                ->replaceMatches('/[^a-z0-9_]+/', '_')
                ->replaceMatches('/_+/', '_')
                ->trim('_')
                ->value();
        }

        $this->merge([
            'code' => $code,
        ]);
    }

    public function rules(): array
    {
        $typeId = (int) $this->route('task_type') ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('task_types', 'code')->ignore($typeId)],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
