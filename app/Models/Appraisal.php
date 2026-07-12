<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appraisal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'year',
        'month',
        'user_id',
        'kpi_name',
        'kpi_description',
        'kpi_agreed_at',
        'status',
        'assignee_average_rating',
        'reporter_average_rating',
        'manager_average_rating',
        'published_at',
        'published_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'published_at' => 'datetime',
            'assignee_submitted_at' => 'datetime',
            'reporter_submitted_at' => 'datetime',
            'manager_submitted_at' => 'datetime',
            'kpi_agreed_at' => 'datetime',
            'assignee_average_rating' => 'decimal:2',
            'reporter_average_rating' => 'decimal:2',
            'manager_average_rating' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function snapshotCategories()
    {
        return $this->hasMany(AppraisalSnapshotCategory::class)->orderBy('sort_order');
    }

    public function answers()
    {
        return $this->hasMany(AppraisalAnswer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
