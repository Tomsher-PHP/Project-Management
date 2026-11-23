<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataSyncOutboundApi extends Model
{
    protected $table = 'data_sync_outbound_apis';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'connection_id',
        'module',
        'operation',
        'method',
        'endpoint',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'connection_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the outbound connection that owns the API.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(DataSyncOutboundConnection::class, 'connection_id');
    }

    /**
     * Alias for connection relationship.
     */
    public function outboundConnection(): BelongsTo
    {
        return $this->connection();
    }

    /**
     * Get the outbound histories associated with the API.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(DataSyncOutboundHistory::class, 'outbound_api_id');
    }

    /**
     * Alias for histories relationship.
     */
    public function outboundHistories(): HasMany
    {
        return $this->histories();
    }
}

