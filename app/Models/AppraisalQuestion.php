<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalQuestion extends Model
{
    protected $table = 'appraisal_questions';

    protected $fillable = [
        'appraisal_category_id',
        'question',
        'question_type',
        'sort_order',
        'is_active',
    ];

    protected $searchable = ['question'];

    protected function casts(): array
    {
        return [
            'question' => 'string',
            'question_type' => 'string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function appraisalCategory()
    {
        return $this->belongsTo(AppraisalCategory::class);
    }

    public function category()
    {
        return $this->belongsTo(AppraisalCategory::class, 'appraisal_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
