<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectPayment extends Model
{
    protected $fillable = [
        'project_id',
        'amount',
        'paid_date',
        'coverage_start_date',
        'coverage_end_date',
        'payment_method',
        'reference',
        'notes',
        'added_by',
        'added_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_date' => 'date',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectPayment $model) {
            $model->added_by = Auth::id();
            $model->added_at = now();
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
