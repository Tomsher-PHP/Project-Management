<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ProjectMember extends Model
{
    use LogsModelActivity {
        tapActivity as baseTapActivity;
    }

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

    public function getNameAttribute(): ?string
    {
        return $this->relationLoaded('user')
            ? $this->user?->name
            : User::find($this->user_id)?->name;
    }

    public function getProjectRoleLabelAttribute()
    {
        return config('project_constants.project_roles')[$this->project_role] ?? $this->project_role;
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

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'project_id' => 'Project Name',
            'user_id' => 'User Name',
            'project_role' => 'Project Role',
            'is_active' => 'Is Active',
            'removed_at' => 'Removed At',
            'removed_by' => 'Removed By',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'project_id' => $this->project_id === $value && $this->relationLoaded('project')
                ? $this->project?->name ?? $value
                : Project::find($value)?->name ?? $value,
            'user_id' => $this->user_id === $value && $this->relationLoaded('user')
                ? $this->user?->name ?? $value
                : User::find($value)?->name ?? $value,
            'project_role' => config('project_constants.project_roles')[$value] ?? $value,
            'is_active' => $value ? 'Active' : 'Inactive',
            'removed_by' => User::find($value)?->name ?? $value,
            default => $value,
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $this->baseTapActivity($activity, $eventName);

        if ($eventName === 'updated' && $this->wasChanged('removed_at') && filled($this->removed_at)) {
            $activity->event = 'deleted';
            $activity->description = $this->getActivityDescription('deleted');

            $properties = $activity->properties->toArray();
            $old = $properties['old'] ?? [];
            $displayOld = $properties['display_old'] ?? [];

            $old['is_active'] = false;
            $old['removed_at'] = $this->removed_at;
            $old['removed_by'] = $this->removed_by;

            $displayOld['is_active'] = $this->getActivityAttributeDisplayValue('is_active', false);
            $displayOld['removed_at'] = $this->getActivityAttributeDisplayValue('removed_at', $this->removed_at);
            $displayOld['removed_by'] = $this->getActivityAttributeDisplayValue('removed_by', $this->removed_by);

            $properties['old'] = $old;
            $properties['display_old'] = $displayOld;
            $activity->properties = collect($properties);
        }
    }
}
