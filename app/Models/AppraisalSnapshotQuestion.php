<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalSnapshotQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_snapshot_category_id',
        'question',
        'question_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => 'string',
            'sort_order' => 'integer',
        ];
    }

    public function snapshotCategory()
    {
        return $this->belongsTo(AppraisalSnapshotCategory::class, 'appraisal_snapshot_category_id');
    }

    public function appraisalSnapshotCategory()
    {
        return $this->belongsTo(AppraisalSnapshotCategory::class);
    }
}
