<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'project_code',
        'name',
        'customer_id',
        'project_flow',
        'priority',
        'status_id',
        'project_stage_id',
        'start_date',
        'end_date',
        'customer_end_date',
        'estimated_time_seconds',
        'default_task_estimate_seconds',
        'domain',
        'project_category_id',
        'default_billable',
        'is_active',
        'sales_person_id',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'customer_end_date' => 'date',
        'estimated_time_seconds' => 'integer',
        'default_task_estimate_seconds' => 'integer',
        'default_billable' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $sortable = [
        'name',
        'customer.name',
        'start_date',
        'end_date',
        'customer_end_date',
    ];

    protected $searchable = ['name', 'project_code'];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    public static function generateProjectCode()
    {
        $lastProject = self::withTrashed()->orderBy('id', 'desc')->first();
        $lastProjectCode = $lastProject ? $lastProject->project_code : 'PRJ00000';
        $lastNumber = (int) substr($lastProjectCode, 3);
        $newNumber = $lastNumber + 1;
        return 'PRJ' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function scopeAccessibleBy($query, $user)
    {
        // Superadmin or view all projects permission
        if ($user->is_super_admin || $user->can('project.view_all_projects')) {
            return $query;
        }

        // Creator or member
        return $query->where(function ($q) use ($user) {
            $q->where('added_by', $user->id)
                ->orWhereHas('members', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function projectStatus()
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function projectStage()
    {
        return $this->belongsTo(ProjectStage::class);
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function technologies()
    {
        return $this->belongsToMany(Technology::class, 'project_technology');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectStatusHistory::class)->orderBy('added_at', 'desc');
    }

    public function stageHistories()
    {
        return $this->hasMany(ProjectStageHistory::class)->orderBy('added_at', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(ProjectComment::class)->orderBy('created_at', 'desc');
    }

    public function projectNotes()
    {
        return $this->hasMany(ProjectNote::class)->orderBy('created_at', 'desc');
    }

    public function projectModules()
    {
        return $this->hasMany(ProjectModule::class)->orderBy('sort_order');
    }

    public function projectSprints()
    {
        return $this->hasMany(ProjectSprint::class)->orderBy('sort_order');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(ProjectStatusHistory::class)->latestOfMany();
    }

    public function getEstimatedTimeHoursAttribute()
    {
        return $this->estimated_time_seconds ? $this->estimated_time_seconds / 3600 : null;
    }

    public function setEstimatedTimeHoursAttribute($value)
    {
        $this->attributes['estimated_time_seconds'] = $value ? (int) ($value * 3600) : null;
    }

    public function getIsAgileAttribute()
    {
        return $this->project_flow === 'agile';
    }

    public function getIsLinearAttribute()
    {
        return $this->project_flow === 'linear';
    }

    public function getIsSimpleAttribute()
    {
        return $this->is_linear;
    }

    public function scopeActive($query)
    {
        return $query->whereHas('is_active', function ($q) {
            $q->where('is_completed', false);
        });
    }

    public function scopeCompleted($query)
    {
        return $query->whereHas('is_active', function ($q) {
            $q->where('is_completed', true);
        });
    }

    /*----------------Attachments----------------*/

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id');
    }

    public function scopeFiles()
    {
        return $this->attachments()->where('category', 'scope_files');
    }

    /*----------------Members relationship----------------*/


    // All members, including removed
    public function membersAll()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['project_role', 'is_active', 'removed_at', 'removed_by']);
    }

    // Only active members (default)
    public function members()
    {
        return $this->membersAll()
            ->whereNull('removed_at');
    }

    public function activeMembers()
    {
        return $this->membersAll()
            ->whereNull('removed_at')
            ->wherePivot('is_active', true);
    }

    public function inactiveMembers()
    {
        return $this->membersAll()
            ->whereNull('removed_at')
            ->wherePivot('is_active', false);
    }

    public function removedMembers()
    {
        return $this->membersAll()
            ->whereNotNull('removed_at');
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'added_by',
        'updated_by',
        'actual_time_seconds',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'customer_id' => 'Customer',
            'project_flow' => 'Project Flow',
            'status_id' => 'Status',
            'project_stage_id' => 'Project Stage',
            'project_category_id' => 'Project Category',
            'sales_person_id' => 'Sales Person',
            'default_task_estimate_seconds' => 'Default Task Estimate',
            'estimated_time_seconds' => 'Estimated Time',
            'default_billable' => 'Default Billable',
            'is_active' => 'Active',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'status_id' => ProjectStatus::find($value)?->name ?? $value,
            'project_stage_id' => ProjectStage::find($value)?->name ?? $value,
            'project_category_id' => ProjectCategory::find($value)?->name ?? $value,
            'sales_person_id' => User::find($value)?->name ?? $value,
            'customer_id' => Customer::find($value)?->name ?? $value,
            'default_billable' => $value ? 'Yes' : 'No',
            'is_active' => $value ? 'Active' : 'Inactive',
            'estimated_time_seconds' => $this->secondsToReadable($value),
            'default_task_estimate_seconds' => $this->secondsToReadable($value),
            default => $value,
        };
    }
}
