<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SingleRolePerProject implements ValidationRule
{
    protected $project;

    public function __construct($project)
    {
        $this->project = $project;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only restrict these roles
        if (!in_array($value, ['team_leader', 'coordinator'])) {
            return;
        }

        $exists = $this->project->members()
            ->where('project_role', $value)
            ->exists();

        if ($exists) {
            $role = str_replace('_', ' ', $value);
            $fail("A {$role} already exists for this project.");
        }
    }
}
