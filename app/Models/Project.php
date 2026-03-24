<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'project_code',
        'name',
        'customer_id',
        'project_type',
        'priority',
        'status_id',
        'project_stage',
        'start_date',
        'internal_end_date',
        'client_end_date',
        'estimated_time_seconds',
        'domain',
        'notes',
        'default_billable',
        'status',
        'sales_person_id',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'internal_end_date' => 'date',
        'client_end_date' => 'date',
        'estimated_time_seconds' => 'integer',
        'default_billable' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
        'status' => 'boolean',
    ];

    protected $sortable = [
        'name',
        'start_date',
        'internal_end_date',
        'client_end_date',
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
        $lastProject = self::orderBy('id', 'desc')->first();
        $lastProjectCode = $lastProject ? $lastProject->project_code : 'PRJ00000';
        $lastNumber = (int) substr($lastProjectCode, 3);
        $newNumber = $lastNumber + 1;
        return 'PRJ' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function projectStatus()
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
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

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function technologies()
    {
        return $this->belongsToMany(Technology::class, 'project_technology');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectStatusHistory::class)->orderBy('added_at', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(ProjectComment::class)->orderBy('created_at', 'desc');
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
        return $this->project_type === 'agile';
    }

    public function getIsSimpleAttribute()
    {
        return $this->project_type === 'simple';
    }

    public function scopeActive($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('is_completed', false);
        });
    }

    public function scopeCompleted($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->where('is_completed', true);
        });
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id');
    }
}
