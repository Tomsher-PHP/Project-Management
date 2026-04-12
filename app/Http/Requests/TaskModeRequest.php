<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskModeRequest extends FormRequest
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
        $modeId = (int) $this->route('task_mode') ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('task_modes', 'code')->ignore($modeId)],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_rework' => ['boolean'],
            'is_productive' => ['boolean'],
            'track_performance' => ['boolean'],
            'customer_request' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
