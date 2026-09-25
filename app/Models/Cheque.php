<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Cheque extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_DEBITED = 'Debited';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_DEBITED => 'Debited',
    ];

    public const DEFAULT_STATUS = self::STATUS_PENDING;

    protected $fillable = [
        'cheque_number',
        'amount',
        'cheque_date',
        'cheque_given',
        'cheque_to',
        'purpose',
        'cheque_status',
        'debited_date',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'cheque_date',
        'cheque_given',
        'debited_date',
        'amount',
        'cheque_number',
        'cheque_status',
    ];

    protected $searchable = [
        'cheque_number',
        'cheque_to',
        'purpose',
    ];

    protected function casts(): array
    {
        return [
            'cheque_date' => 'date',
            'cheque_given' => 'date',
            'debited_date' => 'date',
            'amount' => 'decimal:2',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cheque $model) {
            if (Auth::check() && blank($model->added_by)) {
                $model->added_by = Auth::id();
            }
        });

        static::updating(function (Cheque $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
