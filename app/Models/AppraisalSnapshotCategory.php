<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalSnapshotCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appraisal_id',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function questions()
    {
        return $this->hasMany(AppraisalSnapshotQuestion::class)->orderBy('sort_order');
    }
}
