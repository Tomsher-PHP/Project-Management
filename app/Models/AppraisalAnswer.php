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
        'rating',
        'answer',
        'achieved_value',
        'achievement_percentage',
        'remark',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_id' => 'integer',
            'appraisal_snapshot_question_id' => 'integer',
            'rating' => 'decimal:1',
            'answer' => 'string',
            'achieved_value' => 'decimal:2',
            'achievement_percentage' => 'decimal:2',
            'remark' => 'string',
            'submitted_at' => 'datetime',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function question()
    {
        return $this->belongsTo(AppraisalSnapshotQuestion::class, 'appraisal_snapshot_question_id');
    }

    public function reviews()
    {
        return $this->hasMany(AppraisalAnswerReview::class);
    }
}
