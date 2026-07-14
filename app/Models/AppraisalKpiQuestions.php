<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalKpiQuestions extends Model
{
    protected $fillable = [
        'appraisal_kpi_category_id',
        'question',
        'sort_order',
        'is_active',
    ];

    protected $searchable = ['question'];

    protected function casts(): array
    {
        return [
            'question' => 'string',
            'sort_order' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(AppraisalKpiCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
