<?php

namespace App\Http\Requests;

use App\Models\TeamUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
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
        $teamId = $this->route('team') ?? null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($teamId)],

            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048', // 2MB
            ],
            
            'remove_profile_image' => 'nullable',

            'members' => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.team_role' => 'required|string|in:team_leader,member'
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $teamId = $this->route('team');
                $teamId = is_object($teamId) ? $teamId->id : $teamId;
                $members = collect(array_values($this->input('members', [])));
                $teamLeaderCount = $members
                    ->pluck('team_role')
                    ->filter(fn ($role) => $role === 'team_leader')
                    ->count();
                $memberUserIds = $members
                    ->pluck('user_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($teamLeaderCount > 1) {
                    $validator->errors()->add('members', 'Only one team leader is allowed in a team.');
                }

                if ($memberUserIds->isEmpty()) {
                    return;
                }

                $conflictingUserIds = TeamUser::query()
                    ->whereNull('deleted_at')
                    ->whereIn('user_id', $memberUserIds)
                    ->when($teamId, fn ($query) => $query->where('team_id', '!=', $teamId))
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique();

                if ($conflictingUserIds->isNotEmpty()) {
                    $validator->errors()->add('members', 'One or more selected users already belong to another team.');
                }
            },
        ];
    }
}
