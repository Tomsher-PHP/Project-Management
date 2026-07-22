<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_id',
        'appraisal_reviewer_id',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_id' => 'integer',
            'appraisal_reviewer_id' => 'integer',
            'comment' => 'string',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(AppraisalReviewer::class, 'appraisal_reviewer_id');
    }
}
