<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalQuestion extends Model
{
    public const QUESTION_TYPE_RATING = 'rating';

    public const QUESTION_TYPE_ANSWER = 'answer';

    public const QUESTION_TYPE_TARGET = 'target';

    public const MEASUREMENT_TYPE_NUMBER = 'number';

    public const MEASUREMENT_TYPE_PERCENTAGE = 'percentage';

    public const MEASUREMENT_TYPE_CURRENCY = 'currency';

    public const QUESTION_TYPES = [
        self::QUESTION_TYPE_RATING => 'Rating & Remark',
        self::QUESTION_TYPE_ANSWER => 'Answer Only',
        self::QUESTION_TYPE_TARGET => 'Target',
    ];

    public const MEASUREMENT_TYPES = [
        self::MEASUREMENT_TYPE_NUMBER => 'Number',
        self::MEASUREMENT_TYPE_PERCENTAGE => 'Percentage',
        self::MEASUREMENT_TYPE_CURRENCY => 'Currency',
    ];

    protected $fillable = [
        'appraisal_category_id',
        'question',
        'question_type',
        'measurement_type',
        'target_value',
        'unit',
        'sort_order',
        'is_active',
    ];

    protected $searchable = ['question'];

    protected function casts(): array
    {
        return [
            'question' => 'string',
            'question_type' => 'string',
            'measurement_type' => 'string',
            'target_value' => 'decimal:2',
            'unit' => 'string',
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
