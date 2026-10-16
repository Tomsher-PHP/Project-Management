<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectHostingRecord extends Model
{
    use SoftDeletes;

    public const OWNERSHIP_COMPANY = 'company';
    public const OWNERSHIP_CUSTOMER = 'customer';

    public const OWNERSHIP_TYPES = [
        self::OWNERSHIP_COMPANY => 'Company Provided',
        self::OWNERSHIP_CUSTOMER => 'Customer Provided',
    ];

    protected $fillable = [
        'project_id',
        'hosting_provider_id',
        'hosting_service_id',
        'hosting_service_status_id',
        'hosting_billing_cycle_id',
        'hosting_environment_id',
        'ownership_type',
        'name',
        'domain',
        'reference',
        'start_date',
        'expiry_date',
        'next_renewal_date',
        'billing_amount',
        'currency_code',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expiry_date' => 'date',
            'next_renewal_date' => 'date',
            'billing_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hostingProvider(): BelongsTo
    {
        return $this->belongsTo(HostingProvider::class);
    }

    public function hostingService(): BelongsTo
    {
        return $this->belongsTo(HostingService::class);
    }

    public function hostingServiceStatus(): BelongsTo
    {
        return $this->belongsTo(HostingServiceStatus::class);
    }

    public function hostingBillingCycle(): BelongsTo
    {
        return $this->belongsTo(HostingBillingCycle::class);
    }

    public function hostingEnvironment(): BelongsTo
    {
        return $this->belongsTo(HostingEnvironment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
