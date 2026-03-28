<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CustomerContact extends Model
{
    use SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'landline',
        'mobile',
        'whatsapp',
        'designation',
        'is_primary',
        'status',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    protected $sortable = [
        'name',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? null;
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
