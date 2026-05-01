<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectChecklistItem extends Model
{
    protected $fillable = [
        'project_checklist_id',
        'question',
        'sort_order',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'project_checklist_id' => 'integer',
            'question' => 'string',
            'sort_order' => 'integer',
            'status' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(ProjectChecklist::class);
    }
}
