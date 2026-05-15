<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoffRequestAction extends Model
{
    public const REQUEST_CREATED = 0;
    public const REQUEST_NOTED = 1;
    public const REQUEST_ASSIGNED = 2;

    protected $fillable = [
        'handoff_request_id',
        'user_id',
        'action',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'handoff_request_id' => 'integer',
            'user_id' => 'integer',
            'action' => 'integer',
            'comment' => 'string',
        ];
    }

    public function handoffRequest()
    {
        return $this->belongsTo(HandoffRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
