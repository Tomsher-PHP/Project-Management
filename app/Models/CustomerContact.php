<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\LogsModelActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class CustomerContact extends Model
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
        'is_primary',
        'is_active',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
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
