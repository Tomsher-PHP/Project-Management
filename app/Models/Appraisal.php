<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appraisal extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'year',
        'month',
        'user_id',
        'kpi_name',
        'kpi_description',
        'kpi_agreed_at',
        'assignee_average_rating',
        'final_rating',
        'status',
        'published_at',
        'completed_at',
        'published_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'user_id' => 'integer',
            'kpi_name' => 'string',
            'kpi_description' => 'string',
            'kpi_agreed_at' => 'datetime',
            'assignee_average_rating' => 'decimal:2',
            'final_rating' => 'decimal:2',
            'status' => 'string',
            'published_at' => 'datetime',
            'completed_at' => 'datetime',
            'published_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
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

    public function reviewers()
    {
        return $this->hasMany(AppraisalReviewer::class);
    }

    public function answers()
    {
        return $this->hasMany(AppraisalAnswer::class);
    }

    public function comments()
    {
        return $this->hasMany(AppraisalComment::class);
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
