<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_id',
        'appraisal_snapshot_question_id',
        'assignee_rating',
        'assignee_remark',
        'assignee_answer',
        'assignee_submitted_at',
        'reporter_user_id',
        'reporter_rating',
        'reporter_remark',
        'reporter_submitted_at',
        'manager_user_id',
        'manager_rating',
        'manager_remark',
        'manager_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'assignee_rating' => 'decimal:1',
            'reporter_rating' => 'decimal:1',
            'manager_rating' => 'decimal:1',
            'assignee_submitted_at' => 'datetime',
            'reporter_submitted_at' => 'datetime',
            'manager_submitted_at' => 'datetime',
            'assignee_answer' => 'string',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function snapshotQuestion()
    {
        return $this->belongsTo(AppraisalSnapshotQuestion::class, 'appraisal_snapshot_question_id');
    }
}
