<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSyncOutboundConnection extends Model
{
    use SoftDeletes;

    public const AUTH_TYPE_BEARER = 'bearer';

    public const SUPPORTED_AUTH_TYPES = [
        self::AUTH_TYPE_BEARER,
    ];

    protected $table = 'data_sync_outbound_connections';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'base_url',
        'auth_type',
        'auth_config',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'auth_config',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auth_config' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the outbound APIs associated with this connection.
     */
    public function apis(): HasMany
    {
        return $this->hasMany(DataSyncOutboundApi::class, 'connection_id');
    }

    /**
     * Alias for apis relationship.
     */
    public function outboundApis(): HasMany
    {
        return $this->apis();
    }
}

