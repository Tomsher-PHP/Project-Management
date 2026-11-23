<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyWorkReportRecipient extends Model
{
    protected $fillable = [
        'report_setting_id',
        'user_id',
        'report_key',
        'scope',
        'scope_id',
        'name',
        'email',
        'is_active',
        'effective_from',
        'effective_until',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'report_setting_id' => 'integer',
            'user_id' => 'integer',
            'scope_id' => 'integer',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function reportSetting(): BelongsTo
    {
        return $this->belongsTo(DailyWorkReportSetting::class, 'report_setting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEffective(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $date);
            });
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', 'global');
    }
}
