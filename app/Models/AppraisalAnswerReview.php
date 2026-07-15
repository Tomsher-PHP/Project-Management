<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalAnswerReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_answer_id',
        'appraisal_reviewer_id',
        'rating',
        'remark',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_answer_id' => 'integer',
            'appraisal_reviewer_id' => 'integer',
            'rating' => 'decimal:1',
            'remark' => 'string',
            'submitted_at' => 'datetime',
        ];
    }

    public function answer()
    {
        return $this->belongsTo(AppraisalAnswer::class, 'appraisal_answer_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(AppraisalReviewer::class, 'appraisal_reviewer_id');
    }
}
