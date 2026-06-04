<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryTimezones extends Model
{
    protected $fillable = [
        'country_id',
        'zone_name',
        'gmt_offset',
        'gmt_offset_name',
        'abbreviation',
        'tz_name',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
