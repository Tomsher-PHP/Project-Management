<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'from_date',
        'to_date',
        'description',
        'is_public',
        'applied_to',
        'is_active',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Users this holiday applies to.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'holiday_users'
        )->withTimestamps();
    }

    /**
     * Shifts this holiday applies to.
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(
            Shift::class,
            'holiday_shifts'
        )->withTimestamps();
    }
}
