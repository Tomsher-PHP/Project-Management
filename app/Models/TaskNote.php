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
        'task_id',
        'description',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'task_id' => 'integer',
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
        return $this->belongsTo(Task::class, 'task_id');
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

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'is_active',
        'added_by',
        'updated_by',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'task_id' => 'Task',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'task_id' => Task::find($value)?->name ?? $value,
            default => $value,
        };
    }
}
