<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phonecode',
        'currency',
        'currency_symbol',
    ];

    public function timezones()
    {
        return $this->hasMany(CountryTimezones::class);
    }
}
