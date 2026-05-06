<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, Filterable, Sortable, LogsModelActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        'email_verified_at',
        'remember_token',

        'password_otp',
        'password_otp_expires_at',

        'is_active',
        'delete_status',
    ];

    protected $sortable = [
        'name',
    ];

    protected $searchable = ['name', 'email'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'delete_status' => 'boolean',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? null;
        });
    }

    public function scopeAccessibleBy($query, $user)
    {
        // Superadmin or view all userss permission
        if ($user->is_super_admin || $user->can('user.view_all_users')) {
            return $query;
        }

        // Creator, reporter or manager
        return $query->where(function ($q) use ($user) {
            $q->where('added_by', $user->id)
                ->orWhereHas('details', function ($q2) use ($user) {
                    $q2->where('reporter_id', $user->id)
                        ->orWhere('manager_id', $user->id);
                });
        });
    }

    public function details()
    {
        return $this->hasOne(UserDetail::class);
    }

    public function reporter()
    {
        return $this->hasOneThrough(
            User::class,
            UserDetail::class,
            'user_id',      // Foreign key on UserDetail
            'id',           // Foreign key on User (reporter)
            'id',           // Local key on User
            'reporter_id'   // Local key on UserDetail
        );
    }

    public function manager()
    {
        return $this->hasOneThrough(
            User::class,
            UserDetail::class,
            'user_id',      // Foreign key on UserDetail
            'id',           // Foreign key on User (reporter)
            'id',           // Local key on User
            'manager_id'    // Local key on UserDetail
        );
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id');
    }

    public function primaryAttachment()
    {
        return $this->morphOne(Attachment::class, 'link', 'link_type', 'link_id')
            ->where('is_primary', 1);
    }

    public function getProfileImageUrlAttribute()
    {
        if ($this->primaryAttachment) {
            return Storage::disk($this->primaryAttachment->disk)->url($this->primaryAttachment->file_path);
        }

        return asset(config('assets.images.default_avatar'));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRoleIdAttribute()
    {
        return $this->roles->pluck('id')->first();
    }

    public function getRoleNameAttribute()
    {
        return $this->roles->first()?->name;
    }

    //shift relations
    public function shiftAssignments()
    {
        return $this->hasMany(UserShiftAssignment::class);
    }

    public function activeShift()
    {
        return $this->hasOne(UserShiftAssignment::class)
            ->whereDate('date_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_to')
                    ->orWhereDate('date_to', '>=', now());
            });
    }

    public function getDesignationNameAttribute()
    {
        if ($this->details?->designation) {
            return $this->details->designation->name;
        }
        return '';
    }

    // Projects where user is active
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot(['project_role', 'is_active', 'removed_at', 'removed_by'])
            ->wherePivot('is_active', true);
    }

    // All projects including removed
    public function allProjects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot(['project_role', 'is_active', 'removed_at', 'removed_by']);
    }

    public function projectChecklists()
    {
        return $this->hasMany(ProjectChecklist::class, 'assigned_to');
    }

    public function currentAssignedTasks()
    {
        return $this->hasMany(Task::class, 'current_assignee_id');
    }

    public function taskAssignmentLogs()
    {
        return $this->hasMany(TaskAssignmentLog::class);
    }

    public function taskTimeLogs()
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    public function taskStatusHistories()
    {
        return $this->hasMany(TaskStatusHistory::class, 'added_by');
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'email_verified_at',
        'remember_token',
        'password_otp',
        'password_otp_expires_at',
        'is_super_admin',
        'added_by',
        'updated_by',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'project_milestone_id' => 'Milestone',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'estimated_time_seconds' => 'Estimated Time',
            'sort_order' => 'Sort Order',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'is_active' => $value ? 'Active' : 'Inactive',
            'delete_status' => $value ? 'Deleted' : 'Not Deleted',
            default => $value,
        };
    }

    /**
     * Function to get the profile completion percentage.
     *
     * @return array
     */
    public function profileCompletion()
    {
        $userFields = [
            'name' => $this->name,
            'email' => $this->email,
            'profile_photo' => $this->profileImageUrl,
        ];

        $detailsFields = collect($this->details ?? [])->except([
            'id',
            'user_id',
            'created_at',
            'updated_at',
            'leaving_date',
            'deleted_at',
        ])->toArray();

        $allFields = array_merge($userFields, $detailsFields);

        $total = count($allFields);

        $filled = count(array_filter($allFields));

        return [
            'percentage' => $total ? round(($filled / $total) * 100) : 0,
            'filled' => $filled,
            'total' => $total,
            'missing' => array_keys(array_filter($allFields, fn($v) => empty($v)))
        ];
    }

    public function notificationSettings()
    {
        return $this->hasMany(UserNotificationSetting::class);
    }

    public function generalSettings()
    {
        return $this->hasOne(UserGeneralSetting::class, 'user_id');
    }
}
