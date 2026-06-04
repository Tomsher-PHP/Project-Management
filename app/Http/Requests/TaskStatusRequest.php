<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $flowType = $this->input('flow_type', $this->input('project_flow'));
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
            'flow_type' => $flowType,
            'code' => $code,
        ]);
    }

    public function rules(): array
    {
        $statusId = (int) $this->route('task_status') ?? null;
        $statusTypes = array_keys(config('project_constants.task_status_types', []));
        $projectFlows = array_keys(config('project_constants.project_flows', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('task_statuses', 'code')->where(fn($query) => $query->where('flow_type', $this->flow_type))->ignore($statusId)],
            'flow_type' => ['required', 'string', Rule::in($projectFlows)],
            'type' => ['required', 'string', Rule::in($statusTypes)],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_completed' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
