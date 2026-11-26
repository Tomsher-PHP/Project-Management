<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class CustomerContact extends Authenticatable
{
    use HasFactory, SoftDeletes, Filterable, Sortable;
    use LogsModelActivity {
        tapActivity as baseTapActivity;
    }

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'landline',
        'mobile',
        'whatsapp',
        'designation',
        'password',
        'is_primary',
        'is_active',

        // Customer Portal Authentication
        'login_enabled',
        'password_set_at',
        'last_login_at',
        'email_verified_at',
        'force_password_reset',


        'added_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',

        // Customer Portal Authentication
        'login_enabled' => 'boolean',
        'password_set_at' => 'datetime',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'force_password_reset' => 'boolean',
    ];

    protected $sortable = [
        'name',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->added_by = Auth::id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? null;
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*----------------Activity Log Customization----------------*/

    // Never show these fields in activity log details.
    protected array $activityLogExceptAttributes = [
        'password',
        'password_set_at',
        'last_login_at',
        'email_verified_at',
        'force_password_reset',
        'added_by',
        'updated_by',
    ];

    // For activity log attribute labels
    public function getActivityAttributeLabels(): array
    {
        return [
            'customer_id' => 'Customer',
            'is_primary' => 'Primary',
            'is_active' => 'Active',
        ];
    }

    // For activity log attribute value display
    public function getActivityAttributeDisplayValue(string $attribute, mixed $value): mixed
    {
        return match ($attribute) {
            'customer_id' => Customer::withTrashed()->find($value)?->name ?? $value,
            default => is_array($value) ? json_encode($value) : $value,
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $this->baseTapActivity($activity, $eventName);

        $customerName = $this->relationLoaded('customer')
            ? $this->customer?->name
            : Customer::withTrashed()->find($this->customer_id)?->name;

        if ($customerName !== null) {
            $activity->properties = $activity->properties->merge([
                'customer_name' => $customerName,
            ]);
        }
    }

    protected function getActivityParent(): array
    {
        return [
            'type' => Customer::class,
            'id' => $this->customer_id,
        ];
    }
}
