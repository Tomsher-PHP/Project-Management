<?php

namespace App\Models;


use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalKpiCategory extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    protected $fillable = [
        'title',
        'is_system',
        'is_active'
    ];

    protected $sortable = [
        'name',
    ];

    protected $searchable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(AppraisalKpiQuestions::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
