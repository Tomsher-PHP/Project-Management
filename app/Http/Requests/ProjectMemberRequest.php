<?php

namespace App\Http\Requests;

use App\Rules\SingleRolePerProject;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id', Rule::unique('project_members', 'user_id')->where('project_id', $this->project->id)],
            'project_role' => [
                'required',
                'in:team_leader,coordinator,member',
                new SingleRolePerProject($this->project)
            ],
        ];
    }
}
