<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TaskNote extends Model
{
    use HasFactory, SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'project_task_id',
        'description',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_task_id' => 'integer',
        'is_active' => 'boolean',
        'added_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
