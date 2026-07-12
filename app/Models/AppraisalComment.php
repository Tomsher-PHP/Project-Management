<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_id',
        'role',
        'commented_by',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_id' => 'integer',
            'commented_by' => 'integer',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function commentator()
    {
        return $this->belongsTo(User::class, 'commented_by');
    }
}
