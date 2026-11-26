<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSyncOutboundHistory extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $table = 'data_sync_outbound_histories';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'outbound_api_id',
        'entity_id',
        'request_url',
        'request_payload',
        'response_code',
        'response_body',
        'status',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outbound_api_id' => 'integer',
            'entity_id' => 'integer',
            'request_payload' => 'array',
            'response_code' => 'integer',
        ];
    }

    /**
     * Get the outbound API that owns the history record.
     */
    public function outboundApi(): BelongsTo
    {
        return $this->belongsTo(DataSyncOutboundApi::class, 'outbound_api_id');
    }

    /**
     * Alias for outboundApi relationship.
     */
    public function api(): BelongsTo
    {
        return $this->outboundApi();
    }
}

