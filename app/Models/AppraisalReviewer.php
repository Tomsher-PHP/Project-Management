<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalReviewer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_id',
        'reviewer_user_id',
        'role',
        'level',
        'average_rating',
        'submitted_at',
        'acknowledged_at',
        'acknowledgement_remark',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_id' => 'integer',
            'reviewer_user_id' => 'integer',
            'role' => 'string',
            'level' => 'integer',
            'average_rating' => 'decimal:2',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'acknowledgement_remark' => 'string',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function answerReviews()
    {
        return $this->hasMany(AppraisalAnswerReview::class);
    }

    public function comments()
    {
        return $this->hasMany(AppraisalComment::class);
    }
}
