<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Filterable;
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
    use HasFactory, Notifiable, HasRoles, SoftDeletes, Filterable, Sortable;

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

        'status',
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
            'status' => 'boolean',
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
        return $query->where('status', true);
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
}
