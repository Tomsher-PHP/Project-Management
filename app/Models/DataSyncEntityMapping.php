<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSyncEntityMapping extends Model
{
    public const ENTITY_TYPE_CUSTOMER = 'customer';
    public const ENTITY_TYPE_PROJECT = 'project';

    protected $table = 'data_sync_entity_mappings';

    protected $fillable = [
        'api_client_id',
        'entity_type',
        'external_code',
        'external_id',
        'local_id',
    ];

    protected $casts = [
        'api_client_id' => 'integer',
        'local_id' => 'integer',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}

