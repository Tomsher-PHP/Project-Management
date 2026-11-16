<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProjectNote extends Model
{
    use HasFactory, SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'project_id',
        'description',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'project_id' => 'integer',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
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
            'project_id' => 'Project',
            'description' => 'Description',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'project_id' => Project::find($value)?->name ?? $value,
            'description' => $value,
            default => $value,
        };
    }
}
