<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserKpi extends Model
{
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}