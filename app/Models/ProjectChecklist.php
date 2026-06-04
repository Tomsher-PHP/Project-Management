<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectChecklist extends Model
{
    protected $fillable = [
        'project_id',
        'checklist_template_id',
        'title',
        'assigned_to',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'checklist_template_id' => 'integer',
            'title' => 'string',
            'assigned_to' => 'integer',
            'assigned_by' => 'integer',
        ];
    }

    public static function booted()
    {
        static::creating(function (ProjectChecklist $model) {
            $model->assigned_by = Auth::id();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function items()
    {
        return $this->hasMany(ProjectChecklistItem::class)->orderBy('sort_order');
    }
}
