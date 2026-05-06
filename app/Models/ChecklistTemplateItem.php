<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateItem extends Model
{
    protected $fillable = [
        'checklist_template_id',
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

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
