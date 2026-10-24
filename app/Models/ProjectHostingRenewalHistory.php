<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHostingRenewalHistory extends Model
{
    protected $fillable = [
        'project_hosting_record_id',
        'renewal_date',
        'previous_start_date',
        'previous_expiry_date',
        'new_start_date',
        'new_expiry_date',
        'hosting_billing_cycle_id',
        'billing_amount',
        'currency_code',
        'reference',
        'notes',
        'renewed_by',
    ];

    protected function casts(): array
    {
        return [
            'renewal_date' => 'date',
            'previous_start_date' => 'date',
            'previous_expiry_date' => 'date',
            'new_start_date' => 'date',
            'new_expiry_date' => 'date',
            'billing_amount' => 'decimal:2',
        ];
    }

    public function hostingRecord(): BelongsTo
    {
        return $this->belongsTo(ProjectHostingRecord::class, 'project_hosting_record_id');
    }

    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(HostingBillingCycle::class, 'hosting_billing_cycle_id');
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
