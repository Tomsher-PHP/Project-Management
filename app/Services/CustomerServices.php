<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Support\Facades\DB;

class CustomerServices
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. Create Customer
            $customer = Customer::create([
                'customer_code' => Customer::generateCustomerCode(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'industry_id' => $data['industry_id'] ?? null,
                'website' => $data['website'] ?? null,
                'registered_country_id' => $data['registered_country_id'] ?? null,
                'emirate' => $data['emirate'] ?? null,
                'google_map_link' => $data['google_map_link'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'sales_person' => $data['sales_person'],
                'new_to_company' => $data['new_to_company'] ?? 0,
            ]);

            // 2. Store Contacts
            $this->syncContactsInfo($customer, $data);

            return $customer;
        });
    }

    public function update(Customer $customer, array $data)
    {
        return DB::transaction(function () use ($customer, $data) {
            // 1. Update Customer
            $customer->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'industry_id' => $data['industry_id'] ?? null,
                'website' => $data['website'] ?? null,
                'registered_country_id' => $data['registered_country_id'] ?? null,
                'emirate' => $data['emirate'] ?? null,
                'google_map_link' => $data['google_map_link'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'sales_person' => $data['sales_person'],
                'new_to_company' => $data['new_to_company'] ?? 0,
            ]);

            // 2. Sync contacts
            $this->syncContactsInfo($customer, $data);

            return $customer;
        });
    }

    private function syncContactsInfo($customer, $data)
    {
        $existingIds = [];

        // Primary Contact (force update)
        $primary = $customer->contacts()->updateOrCreate(
            ['is_primary' => 1],
            [
                'name' => $data['primary_name'],
                'email' => $data['primary_email'] ?? null,
                'designation' => $data['primary_designation'] ?? null,
                'mobile' => $data['primary_mobile'] ?? null,
                'landline' => $data['primary_landline'] ?? null,
                'whatsapp' => $data['primary_whatsapp'] ?? null,
            ]
        );

        $existingIds[] = $primary->id;

        // Additional Contacts
        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                $record = $customer->contacts()->updateOrCreate(
                    ['id' => $contact['id'] ?? null], // important for edit
                    [
                        'name' => $contact['name'],
                        'email' => $contact['email'] ?? null,
                        'designation' => $contact['designation'] ?? null,
                        'mobile' => $contact['mobile'] ?? null,
                        'landline' => $contact['landline'] ?? null,
                        'whatsapp' => $contact['whatsapp'] ?? null,
                        'is_primary' => 0,
                    ]
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
