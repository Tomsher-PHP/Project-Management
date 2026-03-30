<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use SoftDeletes, Filterable, Sortable, LogsModelActivity;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'industry_id',
        'website',
        'registered_country_id',
        'emirate',
        'google_map_link',
        'company_address',
        'sales_person',
        'new_to_company',
        'status',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'new_to_company' => 'boolean',
        'status' => 'boolean',
    ];

    protected $sortable = [
        'name',
    ];

    protected $searchable = ['name', 'email'];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? null;
        });
    }

    public static function generateCustomerCode()
    {
        $lastCustomer = self::withTrashed()->orderBy('id', 'desc')->first();
        $lastCustomerCode = $lastCustomer ? $lastCustomer->customer_code : 'CUS00000';
        $lastNumber = (int) substr($lastCustomerCode, 3);
        $newNumber = $lastNumber + 1;
        return 'CUS' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'registered_country_id');
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(CustomerContact::class)->where('is_primary', true);
    }

    public function extraContacts()
    {
        return $this->hasMany(CustomerContact::class)->where('is_primary', false);
    }


    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
