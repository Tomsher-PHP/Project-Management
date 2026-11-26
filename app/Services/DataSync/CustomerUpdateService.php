<?php

namespace App\Services\DataSync;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\DataSyncEntityMapping;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class CustomerUpdateService
{
    public function __construct(
        protected ExternalEntityMappingService $mappingService
    ) {}

    /**
     * Update an existing customer and manage customer contacts atomically.
     *
     * @param  string  $externalCode
     * @param  array<string, mixed>  $data
     * @param  ApiClient|null  $apiClient
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException
     * @throws ConflictHttpException
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(string $externalCode, array $data, ?ApiClient $apiClient = null): array
    {
        return DB::transaction(function () use ($externalCode, $data, $apiClient) {
            if (! $apiClient instanceof ApiClient) {
                throw new NotFoundHttpException('Customer not found.');
            }

            $cleanExternalCode = $this->mappingService->sanitizeIdentifier($externalCode);

            if ($cleanExternalCode === null) {
                throw new NotFoundHttpException('Customer not found.');
            }

            /** @var DataSyncEntityMapping|null $customerMapping */
            $customerMapping = DataSyncEntityMapping::query()
                ->where('api_client_id', $apiClient->id)
                ->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER)
                ->where('external_code', $cleanExternalCode)
                ->first();

            if (! $customerMapping) {
                throw new NotFoundHttpException('Customer not found.');
            }

            /** @var Customer|null $customer */
            $customer = Customer::query()->where('id', $customerMapping->local_id)->lockForUpdate()->first();

            if (! $customer) {
                throw new NotFoundHttpException('Customer not found.');
            }

            // 1. External Mapping Verification & Enrichment
            $bodyExternalCode = array_key_exists('external_code', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_code'])
                : null;
            $bodyExternalId = array_key_exists('external_id', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_id'])
                : null;

            if ($bodyExternalCode !== null || $bodyExternalId !== null) {
                if ($bodyExternalCode !== null && $customerMapping->external_code !== null && $customerMapping->external_code !== $bodyExternalCode) {
                    throw new ConflictHttpException('Conflicting external customer code.');
                }

                $existingMapping = $this->mappingService->findMapping(
                    $apiClient->id,
                    DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                    $bodyExternalCode,
                    $bodyExternalId
                );

                if ($existingMapping !== null && (int) $existingMapping->local_id !== (int) $customer->id) {
                    throw new ConflictHttpException('External mapping belongs to a different customer.');
                }

                if ($bodyExternalId !== null && $customerMapping->external_id !== null && $customerMapping->external_id !== $bodyExternalId) {
                    throw new ConflictHttpException('Conflicting external customer id.');
                }

                // Safe mapping enrichment if missing a secondary identifier
                $this->mappingService->enrichMapping($customerMapping, $bodyExternalCode, $bodyExternalId);
            }

            // 2. Industry Resolution
            $resolvedIndustryId = null;
            $industrySpecified = false;

            if (array_key_exists('industry_code', $data)) {
                $industrySpecified = true;
                $indCode = $data['industry_code'];
                if ($indCode !== null && trim((string) $indCode) !== '') {
                    $industry = Industry::query()->where('code', trim((string) $indCode))->first();
                    if (! $industry) {
                        throw ValidationException::withMessages([
                            'industry_code' => ['The selected industry code does not exist.'],
                        ]);
                    }
                    $resolvedIndustryId = (int) $industry->id;
                } else {
                    $resolvedIndustryId = null;
                }
            }

            // 3. Country Resolution
            $resolvedCountryId = null;
            $countrySpecified = false;

            if (array_key_exists('country_code', $data)) {
                $countrySpecified = true;
                $countryCode = $data['country_code'];
                if ($countryCode !== null && trim((string) $countryCode) !== '') {
                    $cleanCountryCode = strtoupper(trim((string) $countryCode));
                    $country = Country::query()->where('code', $cleanCountryCode)->first();
                    if (! $country) {
                        throw ValidationException::withMessages([
                            'country_code' => ['The selected country code does not exist.'],
                        ]);
                    }
                    $resolvedCountryId = (int) $country->id;
                } else {
                    $resolvedCountryId = null;
                }
            }

            // 4. Salesperson Resolution
            $resolvedSalesPersonId = null;
            $salesPersonSpecified = false;

            if (array_key_exists('sales_person_email', $data)) {
                $salesPersonSpecified = true;
                $salesEmail = $data['sales_person_email'];
                if ($salesEmail !== null && trim((string) $salesEmail) !== '') {
                    $salesUser = User::query()->where('email', trim((string) $salesEmail))->first();
                    if (! $salesUser) {
                        throw ValidationException::withMessages([
                            'sales_person_email' => ['The selected sales person email does not exist.'],
                        ]);
                    }
                    $resolvedSalesPersonId = (int) $salesUser->id;
                } else {
                    $resolvedSalesPersonId = null;
                }
            }

            // 5. Customer Attribute Updates
            $customerAttributesToUpdate = [];

            if (array_key_exists('name', $data) && $customer->name !== $data['name']) {
                $customerAttributesToUpdate['name'] = $data['name'];
            }
            if (array_key_exists('email', $data) && $customer->email !== $data['email']) {
                $customerAttributesToUpdate['email'] = $data['email'];
            }
            if ($industrySpecified && (int) ($customer->industry_id ?? 0) !== (int) ($resolvedIndustryId ?? 0)) {
                $customerAttributesToUpdate['industry_id'] = $resolvedIndustryId;
            }
            if ($countrySpecified && (int) ($customer->registered_country_id ?? 0) !== (int) ($resolvedCountryId ?? 0)) {
                $customerAttributesToUpdate['registered_country_id'] = $resolvedCountryId;
            }
            if ($salesPersonSpecified && (int) ($customer->sales_person_id ?? 0) !== (int) ($resolvedSalesPersonId ?? 0)) {
                $customerAttributesToUpdate['sales_person_id'] = $resolvedSalesPersonId;
            }
            if (array_key_exists('website', $data) && $customer->website !== $data['website']) {
                $customerAttributesToUpdate['website'] = $data['website'];
            }
            if (array_key_exists('emirate', $data) && $customer->emirate !== $data['emirate']) {
                $customerAttributesToUpdate['emirate'] = $data['emirate'];
            }
            if (array_key_exists('company_address', $data) && $customer->company_address !== $data['company_address']) {
                $customerAttributesToUpdate['company_address'] = $data['company_address'];
            }
            if (array_key_exists('is_active', $data) && (bool) $customer->is_active !== (bool) $data['is_active']) {
                $customerAttributesToUpdate['is_active'] = (bool) $data['is_active'];
            }

            $customerUpdated = false;
            if (! empty($customerAttributesToUpdate)) {
                $customer->update($customerAttributesToUpdate);
                $customerUpdated = true;
            }

            // 6. Contact Replacement
            $contactsDeletedCount = 0;
            $contactsCreatedCount = 0;

            if (array_key_exists('contacts', $data) && is_array($data['contacts'])) {
                $this->validateReplacementContacts($customer, $data['contacts']);

                $existingContacts = $customer->contacts()->lockForUpdate()->get();
                $preservedAuthByEmail = $this->collectPreservedAuthByEmail($existingContacts);

                $contactsDeletedCount = $existingContacts->count();
                if ($contactsDeletedCount > 0) {
                    CustomerContact::query()
                        ->where('customer_id', $customer->id)
                        ->whereIn('id', $existingContacts->pluck('id'))
                        ->forceDelete();
                }

                foreach ($data['contacts'] as $contactData) {
                    if (! is_array($contactData)) {
                        continue;
                    }

                    $email = isset($contactData['email']) && trim((string) $contactData['email']) !== ''
                        ? trim((string) $contactData['email'])
                        : null;
                    $normalizedEmail = $email !== null ? strtolower($email) : null;
                    $authData = $normalizedEmail !== null ? ($preservedAuthByEmail[$normalizedEmail] ?? null) : null;

                    CustomerContact::create([
                        'customer_id' => $customer->id,
                        'name' => (string) ($contactData['name'] ?? ''),
                        'email' => $email,
                        'mobile' => isset($contactData['mobile']) && $contactData['mobile'] !== '' ? (string) $contactData['mobile'] : null,
                        'whatsapp' => isset($contactData['whatsapp']) && $contactData['whatsapp'] !== '' ? (string) $contactData['whatsapp'] : null,
                        'landline' => isset($contactData['landline']) && $contactData['landline'] !== '' ? (string) $contactData['landline'] : null,
                        'designation' => isset($contactData['designation']) && $contactData['designation'] !== '' ? (string) $contactData['designation'] : null,
                        'is_primary' => (bool) ($contactData['is_primary'] ?? false),
                        'is_active' => isset($contactData['is_active']) ? (bool) $contactData['is_active'] : true,
                        'login_enabled' => $authData['login_enabled'] ?? false,
                        'password' => $authData['password'] ?? null,
                        'password_set_at' => $authData['password_set_at'] ?? null,
                        'last_login_at' => $authData['last_login_at'] ?? null,
                        'email_verified_at' => $authData['email_verified_at'] ?? null,
                        'force_password_reset' => $authData['force_password_reset'] ?? false,
                    ]);
                    $contactsCreatedCount++;
                }
            }

            $overallUpdated = $customerUpdated || ($contactsDeletedCount > 0) || ($contactsCreatedCount > 0);

            return [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'updated' => $overallUpdated,
                'contacts' => [
                    'deleted' => $contactsDeletedCount,
                    'created' => $contactsCreatedCount,
                    'updated' => 0,
                ],
            ];
        });
    }

    /**
     * Validate replacement contacts against payload duplicates, soft-deleted records, and other customers.
     *
     * @param  Customer  $customer
     * @param  array<int, mixed>  $contacts
     *
     * @throws ValidationException
     */
    protected function validateReplacementContacts(Customer $customer, array $contacts): void
    {
        $seenEmails = [];

        foreach ($contacts as $index => $contactData) {
            if (! is_array($contactData)) {
                continue;
            }

            $rawEmail = $contactData['email'] ?? null;
            if (! is_string($rawEmail) || trim($rawEmail) === '') {
                continue;
            }

            $normalizedEmail = strtolower(trim($rawEmail));

            if (isset($seenEmails[$normalizedEmail])) {
                throw ValidationException::withMessages([
                    "contacts.{$index}.email" => ['Duplicate contact email in payload is not allowed.'],
                ]);
            }
            $seenEmails[$normalizedEmail] = true;

            $trashedConflict = CustomerContact::onlyTrashed()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->exists();

            if ($trashedConflict) {
                throw ValidationException::withMessages([
                    "contacts.{$index}.email" => ['The contact email conflicts with a previously deleted contact record.'],
                ]);
            }

            $otherCustomerConflict = CustomerContact::query()
                ->where('customer_id', '!=', $customer->id)
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->exists();

            if ($otherCustomerConflict) {
                throw ValidationException::withMessages([
                    "contacts.{$index}.email" => ['The contact email is already in use by another customer.'],
                ]);
            }
        }
    }

    /**
     * Collect portal authentication attributes from existing active contacts keyed by normalized email.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, CustomerContact>  $existingContacts
     * @return array<string, array<string, mixed>>
     */
    protected function collectPreservedAuthByEmail($existingContacts): array
    {
        $preserved = [];

        foreach ($existingContacts as $existingContact) {
            $rawEmail = $existingContact->email;
            if (! is_string($rawEmail) || trim($rawEmail) === '') {
                continue;
            }

            $normalizedEmail = strtolower(trim($rawEmail));
            $preserved[$normalizedEmail] = [
                'login_enabled' => (bool) $existingContact->login_enabled,
                'password' => $existingContact->getRawOriginal('password'),
                'password_set_at' => $existingContact->password_set_at,
                'last_login_at' => $existingContact->last_login_at,
                'email_verified_at' => $existingContact->email_verified_at,
                'force_password_reset' => (bool) $existingContact->force_password_reset,
            ];
        }

        return $preserved;
    }
}

