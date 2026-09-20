<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\HasFormOptions;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Expense extends Model
{
    use SoftDeletes, Filterable, Sortable, HasFormOptions;

    public const VAT_PAYMENT_VAT = 'vat';
    public const VAT_PAYMENT_NO_VAT = 'no_vat';
    public const VAT_PAYMENT_RCM = 'rcm';

    public const VAT_PAYMENT_OPTIONS = [
        self::VAT_PAYMENT_VAT => 'VAT',
        self::VAT_PAYMENT_NO_VAT => 'No VAT',
        self::VAT_PAYMENT_RCM => 'RCM',
    ];

    public const DEFAULT_VAT_PAYMENT = self::VAT_PAYMENT_VAT;
    public const DEFAULT_VAT_PERCENTAGE = 5;

    public const LOCAL_INTL_LOCAL = 'local';
    public const LOCAL_INTL_INTERNATIONAL = 'international';

    public const LOCAL_INTL_OPTIONS = [
        self::LOCAL_INTL_LOCAL => 'Local',
        self::LOCAL_INTL_INTERNATIONAL => 'International',
    ];

    public const DEFAULT_LOCAL_INTL_PAYMENT = self::LOCAL_INTL_LOCAL;

    protected $fillable = [
        'payment_mode_id',
        'vat_payment',
        'local_intl_payment',
        'paid_date',
        'invoice_date',
        'payment_amount',
        'other_currency',
        'other_amount',
        'vat_percentage',
        'vat_amount',
        'bank_charges',
        'invoice_number',
        'service_provider_id',
        'category_id',
        'vendor_id',
        'service_product',
        'customer_id',
        'comment',
        'added_by',
        'updated_by',
    ];

    protected $sortable = [
        'paid_date',
        'invoice_date',
        'payment_amount',
        'invoice_number',
    ];

    protected $searchable = [
        'invoice_number',
        'service_product',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'paid_date' => 'date',
            'invoice_date' => 'date',
            'payment_amount' => 'decimal:2',
            'other_amount' => 'decimal:2',
            'vat_percentage' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'bank_charges' => 'decimal:2',
            'payment_mode_id' => 'integer',
            'service_provider_id' => 'integer',
            'category_id' => 'integer',
            'customer_id' => 'integer',
            'added_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $model) {
            if (Auth::check() && blank($model->added_by)) {
                $model->added_by = Auth::id();
            }
        });

        static::updating(function (Expense $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function paymentMode()
    {
        return $this->belongsTo(ExpensePaymentMode::class, 'payment_mode_id')->withTrashed();
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ExpenseServiceProvider::class, 'service_provider_id')->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id')->withTrashed();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
