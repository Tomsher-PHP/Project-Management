<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalSnapshotQuestion extends Model
{
    use SoftDeletes;

    public const QUESTION_TYPE_RATING = 'rating';

    public const QUESTION_TYPE_ANSWER = 'answer';

    public const QUESTION_TYPE_TARGET = 'target';

    public const MEASUREMENT_TYPE_NUMBER = 'number';

    public const MEASUREMENT_TYPE_CURRENCY = 'currency';

    public const MEASUREMENT_TYPE_PERCENTAGE = 'percentage';

    public const QUESTION_TYPES = [
        self::QUESTION_TYPE_RATING => 'Rating & Remark',
        self::QUESTION_TYPE_ANSWER => 'Answer Only',
        self::QUESTION_TYPE_TARGET => 'Target',
    ];

    public const MEASUREMENT_TYPES = [
        self::MEASUREMENT_TYPE_NUMBER => 'Number',
        self::MEASUREMENT_TYPE_CURRENCY => 'Currency',
        self::MEASUREMENT_TYPE_PERCENTAGE => 'Percentage',
    ];

    protected $fillable = [
        'appraisal_snapshot_category_id',
        'question',
        'question_type',
        'measurement_type',
        'target_value',
        'unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_snapshot_category_id' => 'integer',
            'question' => 'string',
            'question_type' => 'string',
            'measurement_type' => 'string',
            'target_value' => 'decimal:2',
            'unit' => 'string',
            'sort_order' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(AppraisalSnapshotCategory::class, 'appraisal_snapshot_category_id');
    }

    public function answers()
    {
        return $this->hasMany(AppraisalAnswer::class, 'appraisal_snapshot_question_id');
    }
}
