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
     * @param  string  $customerCode
     * @param  array<string, mixed>  $data
     * @param  ApiClient|null  $apiClient
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException
     * @throws ConflictHttpException
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(string $customerCode, array $data, ?ApiClient $apiClient = null): array
    {
        return DB::transaction(function () use ($customerCode, $data, $apiClient) {
            /** @var Customer|null $customer */
            $customer = Customer::query()->where('customer_code', $customerCode)->lockForUpdate()->first();

            if (! $customer) {
                throw new NotFoundHttpException('Customer not found.');
            }

            // 1. External Mapping Verification & Enrichment
            $externalCode = array_key_exists('external_code', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_code'])
                : null;
            $externalId = array_key_exists('external_id', $data)
                ? $this->mappingService->sanitizeIdentifier($data['external_id'])
                : null;

            if ($apiClient instanceof ApiClient && ($externalCode !== null || $externalId !== null)) {
                $existingMapping = $this->mappingService->findMapping(
                    $apiClient->id,
                    DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
                    $externalCode,
                    $externalId
                );

                if ($existingMapping !== null && (int) $existingMapping->local_id !== (int) $customer->id) {
                    throw new ConflictHttpException('External mapping belongs to a different customer.');
                }

                $customerMapping = DataSyncEntityMapping::query()
                    ->where('api_client_id', $apiClient->id)
                    ->where('entity_type', DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER)
                    ->where('local_id', $customer->id)
                    ->first();

                if ($customerMapping !== null) {
                    if ($externalCode !== null && $customerMapping->external_code !== null && $customerMapping->external_code !== $externalCode) {
                        throw new ConflictHttpException('Conflicting external customer code.');
                    }

                    if ($externalId !== null && $customerMapping->external_id !== null && $customerMapping->external_id !== $externalId) {
                        throw new ConflictHttpException('Conflicting external customer id.');
                    }

                    // Safe mapping enrichment if missing a secondary identifier
                    $this->mappingService->enrichMapping($customerMapping, $externalCode, $externalId);
                }
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

            // 6. Contact Processing
            $contactsCreatedCount = 0;
            $contactsUpdatedCount = 0;

            if (array_key_exists('contacts', $data) && is_array($data['contacts']) && ! empty($data['contacts'])) {
                // Verify all provided contact IDs belong to this customer
                foreach ($data['contacts'] as $index => $contactData) {
                    if (! is_array($contactData)) {
                        continue;
                    }
                    if (isset($contactData['id']) && $contactData['id'] !== null) {
                        $contactId = (int) $contactData['id'];
                        $belongs = $customer->contacts()->where('id', $contactId)->exists();
                        if (! $belongs) {
                            throw ValidationException::withMessages([
                                "contacts.{$index}.id" => ['The selected contact does not belong to this customer.'],
                            ]);
                        }
                    }
                }

                // Check if any contact in payload is designated as primary
                $designatedPrimaryContactId = null;
                $designatedPrimaryIsNewIndex = null;

                foreach ($data['contacts'] as $index => $contactData) {
                    if (! empty($contactData['is_primary'])) {
                        if (isset($contactData['id']) && $contactData['id'] !== null) {
                            $designatedPrimaryContactId = (int) $contactData['id'];
                        } else {
                            $designatedPrimaryIsNewIndex = $index;
                        }
                        break;
                    }
                }

                if ($designatedPrimaryContactId !== null || $designatedPrimaryIsNewIndex !== null) {
                    // Demote existing primary contacts atomically
                    $customer->contacts()->where('is_primary', true)->update(['is_primary' => false]);
                }

                foreach ($data['contacts'] as $index => $contactData) {
                    if (! is_array($contactData)) {
                        continue;
                    }

                    if (isset($contactData['id']) && $contactData['id'] !== null) {
                        /** @var CustomerContact $contact */
                        $contact = $customer->contacts()->lockForUpdate()->findOrFail((int) $contactData['id']);

                        $contactUpdates = [];
                        if (array_key_exists('name', $contactData) && $contact->name !== $contactData['name']) {
                            $contactUpdates['name'] = $contactData['name'];
                        }
                        if (array_key_exists('email', $contactData) && $contact->email !== $contactData['email']) {
                            $contactUpdates['email'] = $contactData['email'];
                        }
                        if (array_key_exists('mobile', $contactData) && $contact->mobile !== $contactData['mobile']) {
                            $contactUpdates['mobile'] = $contactData['mobile'];
                        }
                        if (array_key_exists('whatsapp', $contactData) && $contact->whatsapp !== $contactData['whatsapp']) {
                            $contactUpdates['whatsapp'] = $contactData['whatsapp'];
                        }
                        if (array_key_exists('landline', $contactData) && $contact->landline !== $contactData['landline']) {
                            $contactUpdates['landline'] = $contactData['landline'];
                        }
                        if (array_key_exists('designation', $contactData) && $contact->designation !== $contactData['designation']) {
                            $contactUpdates['designation'] = $contactData['designation'];
                        }
                        if (array_key_exists('is_active', $contactData) && (bool) $contact->is_active !== (bool) $contactData['is_active']) {
                            $contactUpdates['is_active'] = (bool) $contactData['is_active'];
                        }

                        if ($designatedPrimaryContactId === (int) $contact->id) {
                            $contactUpdates['is_primary'] = true;
                        } elseif (array_key_exists('is_primary', $contactData) && (bool) $contact->is_primary !== (bool) $contactData['is_primary']) {
                            $contactUpdates['is_primary'] = (bool) $contactData['is_primary'];
                        }

                        if (! empty($contactUpdates)) {
                            $contact->update($contactUpdates);
                            $contactsUpdatedCount++;
                        }
                    } else {
                        // New contact creation
                        $isPrimary = ($designatedPrimaryIsNewIndex === $index) || (bool) ($contactData['is_primary'] ?? false);

                        CustomerContact::create([
                            'customer_id' => $customer->id,
                            'name' => (string) ($contactData['name'] ?? ''),
                            'email' => isset($contactData['email']) && $contactData['email'] !== '' ? (string) $contactData['email'] : null,
                            'mobile' => isset($contactData['mobile']) && $contactData['mobile'] !== '' ? (string) $contactData['mobile'] : null,
                            'whatsapp' => isset($contactData['whatsapp']) && $contactData['whatsapp'] !== '' ? (string) $contactData['whatsapp'] : null,
                            'landline' => isset($contactData['landline']) && $contactData['landline'] !== '' ? (string) $contactData['landline'] : null,
                            'designation' => isset($contactData['designation']) && $contactData['designation'] !== '' ? (string) $contactData['designation'] : null,
                            'is_primary' => $isPrimary,
                            'is_active' => isset($contactData['is_active']) ? (bool) $contactData['is_active'] : true,
                        ]);
                        $contactsCreatedCount++;
                    }
                }
            }

            $overallUpdated = $customerUpdated || ($contactsCreatedCount > 0) || ($contactsUpdatedCount > 0);

            return [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'updated' => $overallUpdated,
                'contacts' => [
                    'created' => $contactsCreatedCount,
                    'updated' => $contactsUpdatedCount,
                ],
            ];
        });
    }
}

