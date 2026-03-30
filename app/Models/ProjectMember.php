<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'project_id',
        'user_id',
        'project_role',
        'is_active',
        'removed_at',
        'removed_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProjectRoleLabelAttribute()
    {
        return config('constants.project_roles')[$this->project_role] ?? $this->project_role;
    }

    //team leader
    public function isTeamLeader()
    {
        return $this->project_role === 'team_leader';
    }

    //coordinator
    public function isCoordinator()
    {
        return $this->project_role === 'coordinator';
    }

    //member
    public function isMember()
    {
        return $this->project_role === 'member';
    }
}
