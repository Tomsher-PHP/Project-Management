<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerProfileGrade;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerServices
{
    public function loadForDetail(Customer $customer): Customer
    {
        return $customer->load([
            'industry',
            'country',
            'salesPerson',
            'profileGrade',
            'profileDescriptions',
            'addedBy',
            'contacts' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('name'),
        ]);
    }

    public function updateProfileGrade(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $grade = CustomerProfileGrade::query()
                ->where(function ($query) use ($customer) {
                    $query->active();

                    if ($customer->customer_profile_grade_id) {
                        $query->orWhere('id', $customer->customer_profile_grade_id);
                    }
                })
                ->lockForUpdate()
                ->findOrFail($data['customer_profile_grade_id']);

            $customer->update(['customer_profile_grade_id' => $grade->id]);

            $descriptions = collect($data['descriptions'] ?? [])
                ->filter(fn ($description) => filled($description))
                ->map(fn ($description) => trim($description))
                ->values();

            $customer->profileDescriptions()->delete();

            $customer->profileDescriptions()->createMany(
                $descriptions->map(fn ($description, $index) => [
                    'description' => $description,
                    'sort_order' => $index + 1,
                ])->all(),
            );

            return $customer->fresh()->load(['profileGrade', 'profileDescriptions']);
        });
    }

    public function create(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {

                // 1. Create Customer
                $customer = Customer::create(array_merge([
                    'customer_code' => Customer::generateCustomerCode(),
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'industry_id' => $data['industry_id'] ?? null,
                    'website' => $data['website'] ?? null,
                    'registered_country_id' => $data['registered_country_id'] ?? null,
                    'emirate' => $data['emirate'] ?? null,
                    'google_map_link' => $data['google_map_link'] ?? null,
                    'company_address' => $data['company_address'] ?? null,
                    'sales_person_id' => $data['sales_person_id'],
                    'new_to_company' => $data['new_to_company'] ?? 0,
                ], $this->buildAuthAttributes($data)));

                // 2. Store Contacts
                $this->syncContactsInfo($customer, $data);

                return $customer;
            });
        } catch (QueryException $e) {
            $this->rethrowUsernameConflict($e);
        }
    }

    public function update(Customer $customer, array $data)
    {
        try {
            return DB::transaction(function () use ($customer, $data) {
                $previousUsername = $customer->username;
                $previousEmail = strtolower(trim((string) $customer->email));
                $previousLoginEnabled = (bool) $customer->login_enabled;

                // 1. Update Customer
                $customer->update(array_merge([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'industry_id' => $data['industry_id'] ?? null,
                    'website' => $data['website'] ?? null,
                    'registered_country_id' => $data['registered_country_id'] ?? null,
                    'emirate' => $data['emirate'] ?? null,
                    'google_map_link' => $data['google_map_link'] ?? null,
                    'company_address' => $data['company_address'] ?? null,
                    'sales_person_id' => $data['sales_person_id'],
                    'new_to_company' => $data['new_to_company'] ?? 0,
                ], $this->buildAuthAttributes($data, $customer)));

                $usernameChanged = $customer->username !== $previousUsername;
                $passwordChanged = filled($data['password'] ?? null);
                $loginDisabled = $previousLoginEnabled && ! $customer->login_enabled;
                $emailChanged = strtolower(trim((string) $customer->email)) !== $previousEmail;

                if ($usernameChanged || $passwordChanged || $loginDisabled) {
                    app(\App\Services\CustomerPortal\Auth\CustomerAuthService::class)
                        ->revokeCustomerSessions($customer->id);
                    app(\App\Services\CustomerPortal\Auth\CustomerOtpService::class)
                        ->invalidateOutstandingForCustomer($customer->id);
                } elseif ($emailChanged) {
                    app(\App\Services\CustomerPortal\Auth\CustomerOtpService::class)
                        ->invalidateOutstandingForCustomer($customer->id);
                }

                // 2. Sync contacts
                $this->syncContactsInfo($customer, $data);

                return $customer;
            });
        } catch (QueryException $e) {
            $this->rethrowUsernameConflict($e);
        }
    }

    /**
     * Build authentication attributes for creating or updating a customer.
     *
     * @return array<string, mixed>
     */
    private function buildAuthAttributes(array $data, ?Customer $existingCustomer = null): array
    {
        $loginEnabled = (bool) ($data['login_enabled'] ?? false);

        if (! $loginEnabled) {
            if ($existingCustomer === null) {
                return [
                    'login_enabled' => false,
                    'username' => null,
                    'password' => null,
                    'password_set_at' => null,
                ];
            }

            // Preserve existing username, password, and password_set_at when disabling login access.
            return [
                'login_enabled' => false,
            ];
        }

        $attributes = [
            'login_enabled' => true,
            'username' => isset($data['username']) && trim((string) $data['username']) !== ''
                ? mb_strtolower(trim((string) $data['username']))
                : null,
        ];

        // The Customer model's hashed cast hashes the password automatically.
        if (filled($data['password'] ?? null)) {
            $attributes['password'] = $data['password'];
            $attributes['password_set_at'] = now();
        }

        return $attributes;
    }

    private function rethrowUsernameConflict(QueryException $e): never
    {
        if (str_contains($e->getMessage(), 'customers_username_unique')) {
            throw ValidationException::withMessages([
                'username' => ['This username is already taken.'],
            ]);
        }

        throw $e;
    }

    private function syncContactsInfo($customer, $data)
    {
        $existingIds = [];

        // Primary Contact
        $primaryData = [
            'name' => $data['primary_name'],
            'email' => $data['primary_email'] ?? null,
            'designation' => $data['primary_designation'] ?? null,
            'mobile' => $data['primary_mobile'] ?? null,
            'landline' => $data['primary_landline'] ?? null,
            'whatsapp' => $data['primary_whatsapp'] ?? null,
            'is_primary' => 1,
        ];

        $primary = $customer->contacts()->updateOrCreate(
            ['is_primary' => 1],
            $primaryData
        );

        $existingIds[] = $primary->id;

        // Additional Contacts
        if (! empty($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                $contactData = [
                    'name' => $contact['name'],
                    'email' => $contact['email'] ?? null,
                    'designation' => $contact['designation'] ?? null,
                    'mobile' => $contact['mobile'] ?? null,
                    'landline' => $contact['landline'] ?? null,
                    'whatsapp' => $contact['whatsapp'] ?? null,
                    'is_primary' => 0,
                ];

                $record = $customer->contacts()->updateOrCreate(
                    ['id' => $contact['id'] ?? null],
                    $contactData
                );

                $existingIds[] = $record->id;
            }
        }

        // Delete removed contacts
        $customer->contacts()
            ->whereNotIn('id', $existingIds)
            ->delete();
    }
}
