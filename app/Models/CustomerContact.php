<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerContact extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'landline',
        'mobile',
        'whatsapp',
        'designation',
        'password',
        'is_primary',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $sortable = [
        'name',
    ];

    protected static function booted(): void
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
