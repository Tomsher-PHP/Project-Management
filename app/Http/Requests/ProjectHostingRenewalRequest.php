<?php

namespace App\Http\Requests;

use App\Models\ProjectHostingRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectHostingRenewalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');
        if (! $project) {
            return false;
        }

        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if (! $user->can('view', $project)) {
            return false;
        }

        return $user->can('project_hosting.edit') && $user->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var ProjectHostingRecord|null $hostingRecord */
        $hostingRecord = $this->route('hostingRecord');
        $currentExpiry = $hostingRecord?->expiry_date?->format('Y-m-d');

        $expiryRules = ['required', 'date'];
        if ($currentExpiry) {
            $expiryRules[] = 'after:' . $currentExpiry;
        }

        return [
            'renewal_date' => [
                'required',
                'date',
            ],
            'new_expiry_date' => $expiryRules,
            'new_start_date' => [
                'nullable',
                'date',
                'before_or_equal:new_expiry_date',
            ],
            'hosting_billing_cycle_id' => [
                'nullable',
                'integer',
                Rule::exists('hosting_billing_cycles', 'id')->whereNull('deleted_at'),
            ],
            'billing_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'currency_code' => [
                'nullable',
                'string',
                'size:3',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'new_expiry_date.after' => 'The new expiry date must be later than the current expiry date.',
            'new_start_date.before_or_equal' => 'The new start date must be on or before the new expiry date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'renewal_date' => 'renewal date',
            'new_expiry_date' => 'new expiry date',
            'new_start_date' => 'new start date',
            'hosting_billing_cycle_id' => 'billing cycle',
            'billing_amount' => 'billing amount',
            'currency_code' => 'currency',
            'reference' => 'reference',
            'notes' => 'renewal notes',
        ];
    }
}
