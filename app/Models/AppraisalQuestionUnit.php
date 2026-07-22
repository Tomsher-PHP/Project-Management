<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalQuestionUnit extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_system',
        'is_active',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
