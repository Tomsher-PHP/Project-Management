<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Filterable;
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
    use HasFactory, Notifiable, HasRoles, SoftDeletes, Filterable;

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

    // public function canByUserType(string $permission): bool
    // {
    //     // Super admin can access everything
    //     if ($this->is_super_admin) {
    //         return true;
    //     }

    //     return $this->getAllPermissions()
    //         ->where('name', $permission)
    //         ->where('user_type', $this->user_type)
    //         ->isNotEmpty();
    // }

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

        return asset('assets/images/avatar/default-avatar.jpeg');
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
}
