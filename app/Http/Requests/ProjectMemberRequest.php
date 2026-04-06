<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'array', 'min:1'],
            'user_id.*' => [
                'required',
                'exists:users,id',
                Rule::unique('project_members', 'user_id')
                    ->where('project_id', $this->project->id)
                    ->whereNull('removed_at'),
            ],

            'project_role' => [
                'required',
                'in:team_leader,coordinator,member',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'At least one user is required.',
            'user_id.array' => 'Invalid users format.',
            'user_id.*.exists' => 'User not found.',
            'user_id.*.unique' => 'One or more users are already added.',
        ];
    }
}
