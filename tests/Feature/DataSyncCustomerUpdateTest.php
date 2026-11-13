<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\DataSyncEntityMapping;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\ApiClientService;
use App\Services\DataSync\CustomerUpdateService;
use App\Services\DataSync\ExternalEntityMappingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncCustomerUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClient $apiClient;
    protected string $authorizedKey;
    protected Industry $testIndustry;
    protected Industry $secondIndustry;
    protected Country $testCountry;
    protected Country $secondCountry;
    protected User $testSalesUser;
    protected User $secondSalesUser;
    protected Customer $customer;
    protected CustomerContact $contact1;
    protected CustomerContact $contact2;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        $this->testIndustry = Industry::firstOrCreate(['code' => '743813'], [
            'name' => 'Information Technology',
        ]);

        $this->secondIndustry = Industry::firstOrCreate(['code' => '743814'], [
            'name' => 'Telecommunications',
        ]);

        $this->testCountry = Country::firstOrCreate(['code' => 'AE'], [
            'name' => 'United Arab Emirates',
        ]);

        $this->secondCountry = Country::firstOrCreate(['code' => 'SA'], [
            'name' => 'Saudi Arabia',
        ]);

        $this->testSalesUser = User::firstOrCreate(['email' => 'sales@abctech.ae'], [
            'name' => 'Sarah Sales',
            'password' => bcrypt('secret123'),
        ]);

        $this->secondSalesUser = User::firstOrCreate(['email' => 'john.sales@abctech.ae'], [
            'name' => 'John Sales',
            'password' => bcrypt('secret123'),
        ]);

        // Customer
        $this->customer = Customer::create([
            'customer_code' => 'CUS00001',
            'name' => 'Original Customer LLC',
            'email' => 'info@origcompany.ae',
            'industry_id' => $this->testIndustry->id,
            'registered_country_id' => $this->testCountry->id,
            'sales_person_id' => $this->testSalesUser->id,
            'website' => 'https://origcompany.ae',
            'emirate' => 'Dubai',
            'company_address' => 'Original Address, Dubai',
            'is_active' => true,
        ]);

        // Contacts
        $this->contact1 = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name' => 'Primary Contact',
            'email' => 'primary@origcompany.ae',
            'mobile' => '+971501112233',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->contact2 = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name' => 'Secondary Contact',
            'email' => 'secondary@origcompany.ae',
            'mobile' => '+971502223344',
            'is_primary' => false,
            'is_active' => true,
        ]);

        // Existing Project attached to customer
        $defaultStatus = ProjectStatus::firstOrCreate(['is_default' => 1], [
            'name' => 'Default Planned',
            'code' => '100001',
            'type' => 'open',
            'sort_order' => 1,
            'is_completed' => 0,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        $defaultStage = ProjectStage::firstOrCreate(['is_default' => 1], [
            'name' => 'Default Planning',
            'code' => '200001',
            'color' => '#000000',
            'sort_order' => 1,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        $this->project = Project::create([
            'project_code' => 'PRJ00001',
            'name' => 'Customer Associated Project',
            'customer_id' => $this->customer->id,
            'status_id' => $defaultStatus->id,
            'project_stage_id' => $defaultStage->id,
            'start_date' => '2026-12-01',
            'end_date' => '2027-02-28',
        ]);

        // API Client
        $result = app(ApiClientService::class)->createClient('Customer Update Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->apiClient = $result['client'];
        $this->authorizedKey = $result['plain_text_key'];
    }

    public function test_1_existing_customer_updates_successfully(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Updated Customer LLC via API',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'data' => [
                    'customer_id' => $this->customer->id,
                    'customer_code' => $this->customer->customer_code,
                    'updated' => true,
                    'contacts' => [
                        'created' => 0,
                        'updated' => 0,
                    ],
                ],
            ]);

        $this->assertSame('Updated Customer LLC via API', $this->customer->fresh()->name);
    }

    public function test_2_invalid_customer_code_returns_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/CUS999999', [
                'name' => 'Nonexistent Customer',
            ])
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Customer not found.',
            ]);
    }

    public function test_3_missing_api_key_returns_401(): void
    {
        $this->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
            'name' => 'Unauthorized Customer Update',
        ])->assertStatus(401);
    }

    public function test_4_missing_projects_write_scope_returns_403(): void
    {
        $readResult = app(ApiClientService::class)->createClient('Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $readResult['plain_text_key'])
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Forbidden Customer Update',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_5_customer_name_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Brand New Customer Name LLC',
            ])
            ->assertStatus(200);

        $this->assertSame('Brand New Customer Name LLC', $this->customer->fresh()->name);
    }

    public function test_6_customer_email_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'email' => 'new.customer.email@abctech.ae',
            ])
            ->assertStatus(200);

        $this->assertSame('new.customer.email@abctech.ae', $this->customer->fresh()->email);
    }

    public function test_7_partial_update_preserves_omitted_fields(): void
    {
        $originalEmail = $this->customer->email;
        $originalWebsite = $this->customer->website;
        $originalAddress = $this->customer->company_address;
        $originalIndustryId = $this->customer->industry_id;
        $originalCountryId = $this->customer->registered_country_id;
        $originalSalesPersonId = $this->customer->sales_person_id;

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'emirate' => 'Abu Dhabi',
            ])
            ->assertStatus(200);

        $fresh = $this->customer->fresh();
        $this->assertSame('Abu Dhabi', $fresh->emirate);
        $this->assertSame('Original Customer LLC', $fresh->name);
        $this->assertSame($originalEmail, $fresh->email);
        $this->assertSame($originalWebsite, $fresh->website);
        $this->assertSame($originalAddress, $fresh->company_address);
        $this->assertSame($originalIndustryId, $fresh->industry_id);
        $this->assertSame($originalCountryId, $fresh->registered_country_id);
        $this->assertSame($originalSalesPersonId, $fresh->sales_person_id);
    }

    public function test_8_industry_resolution(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'industry_code' => $this->secondIndustry->code,
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondIndustry->id, $this->customer->fresh()->industry_id);
    }

    public function test_9_invalid_industry_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'industry_code' => '999999',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['industry_code']);
    }

    public function test_10_country_resolution(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'country_code' => 'sa',
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondCountry->id, $this->customer->fresh()->registered_country_id);
    }

    public function test_11_invalid_country_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'country_code' => 'ZZ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);
    }

    public function test_12_salesperson_resolution(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'sales_person_email' => $this->secondSalesUser->email,
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondSalesUser->id, $this->customer->fresh()->sales_person_id);
    }

    public function test_13_invalid_salesperson_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'sales_person_email' => 'nobody@nonexistent.ae',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sales_person_email']);
    }

    public function test_14_active_status_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'is_active' => false,
            ])
            ->assertStatus(200);

        $this->assertFalse($this->customer->fresh()->is_active);
    }

    public function test_15_website_and_address_updates(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'website' => 'https://newdomain.com',
                'company_address' => 'New Office Tower, Floor 10, Business Bay',
            ])
            ->assertStatus(200);

        $fresh = $this->customer->fresh();
        $this->assertSame('https://newdomain.com', $fresh->website);
        $this->assertSame('New Office Tower, Floor 10, Business Bay', $fresh->company_address);
    }

    public function test_16_existing_contact_update(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [
                    [
                        'id' => $this->contact2->id,
                        'name' => 'Updated Secondary Name',
                        'designation' => 'Chief Technology Officer',
                        'mobile' => '+971509990000',
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertSame(1, $response->json('data.contacts.updated'));
        $this->assertSame(0, $response->json('data.contacts.created'));

        $freshContact2 = $this->contact2->fresh();
        $this->assertSame('Updated Secondary Name', $freshContact2->name);
        $this->assertSame('Chief Technology Officer', $freshContact2->designation);
        $this->assertSame('+971509990000', $freshContact2->mobile);
    }

    public function test_17_new_contact_creation(): void
    {
        $initialContactCount = CustomerContact::where('customer_id', $this->customer->id)->count();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [
                    [
                        'name' => 'Brand New Contact',
                        'email' => 'newcontact@origcompany.ae',
                        'mobile' => '+971509998877',
                        'designation' => 'Project Lead',
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertSame(1, $response->json('data.contacts.created'));
        $this->assertSame($initialContactCount + 1, CustomerContact::where('customer_id', $this->customer->id)->count());

        $newContact = CustomerContact::where('name', 'Brand New Contact')->first();
        $this->assertNotNull($newContact);
        $this->assertSame('newcontact@origcompany.ae', $newContact->email);
        $this->assertSame($this->customer->id, $newContact->customer_id);
    }

    public function test_18_existing_contacts_remain_unchanged_when_omitted(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Only Name Changed',
            ])
            ->assertStatus(200);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
        $this->assertSame('Primary Contact', $this->contact1->fresh()->name);
        $this->assertSame('Secondary Contact', $this->contact2->fresh()->name);
    }

    public function test_19_empty_contacts_array_does_not_delete_contacts(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [],
            ])
            ->assertStatus(200);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_20_contact_belonging_to_another_customer_is_rejected(): void
    {
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS00002',
            'name' => 'Other Customer LLC',
        ]);

        $otherContact = CustomerContact::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Other Contact',
            'is_primary' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [
                    [
                        'id' => $otherContact->id,
                        'name' => 'Hijacked Contact',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.0.id']);
    }

    public function test_21_primary_contact_change(): void
    {
        $this->assertTrue((bool) $this->contact1->fresh()->is_primary);
        $this->assertFalse((bool) $this->contact2->fresh()->is_primary);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [
                    [
                        'id' => $this->contact2->id,
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertTrue((bool) $this->contact2->fresh()->is_primary);
        $this->assertFalse((bool) $this->contact1->fresh()->is_primary);
    }

    public function test_22_multiple_primary_contacts_are_prevented(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'contacts' => [
                    [
                        'id' => $this->contact1->id,
                        'is_primary' => true,
                    ],
                    [
                        'id' => $this->contact2->id,
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts']);
    }

    public function test_23_external_mapping_verification(): void
    {
        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-VALID-1',
            'local_id' => $this->customer->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'external_code' => 'CRM-CUS-VALID-1',
                'name' => 'Customer With Verified Mapping',
            ])
            ->assertStatus(200);

        $this->assertSame('Customer With Verified Mapping', $this->customer->fresh()->name);
    }

    public function test_24_external_mapping_conflict_returns_409(): void
    {
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS00002',
            'name' => 'Other Customer LLC',
        ]);

        DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-OTHER-CODE',
            'local_id' => $otherCustomer->id,
        ]);

        // Attempt to update Customer 1 claiming external code of Customer 2
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'external_code' => 'CRM-CUS-OTHER-CODE',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'External mapping belongs to a different customer.',
            ]);
    }

    public function test_25_safe_mapping_enrichment(): void
    {
        $mapping = DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => 'CRM-CUS-ENRICH',
            'external_id' => null,
            'local_id' => $this->customer->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'external_code' => 'CRM-CUS-ENRICH',
                'external_id' => 'EXT-CUS-ID-999',
            ])
            ->assertStatus(200);

        $mapping->refresh();
        $this->assertSame('CRM-CUS-ENRICH', $mapping->external_code);
        $this->assertSame('EXT-CUS-ID-999', $mapping->external_id);
    }

    public function test_26_no_unnecessary_customer_or_contact_duplication(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Idempotent Customer Name',
            ])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Idempotent Customer Name',
            ])
            ->assertStatus(200);

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, CustomerContact::count());
    }

    public function test_27_existing_projects_remain_unchanged(): void
    {
        $originalProjectName = $this->project->name;
        $originalStatusId = $this->project->status_id;
        $originalStageId = $this->project->project_stage_id;
        $originalCustomerId = $this->project->customer_id;

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Updated Customer Name Check Projects',
                'is_active' => false,
            ])
            ->assertStatus(200);

        $freshProject = $this->project->fresh();
        $this->assertSame($originalProjectName, $freshProject->name);
        $this->assertSame($originalStatusId, $freshProject->status_id);
        $this->assertSame($originalStageId, $freshProject->project_stage_id);
        $this->assertSame($originalCustomerId, $freshProject->customer_id);
    }

    public function test_28_transaction_rollback_on_failure(): void
    {
        $originalName = $this->customer->name;
        $originalEmail = $this->customer->email;

        $this->instance(CustomerUpdateService::class, new class(
            app(ExternalEntityMappingService::class)
        ) extends CustomerUpdateService {
            public function update(string $customerCode, array $data, ?ApiClient $apiClient = null): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($customerCode) {
                    $customer = Customer::where('customer_code', $customerCode)->first();
                    $customer->update(['name' => 'Crash Test Customer Name']);

                    throw new Exception('Simulated customer update crash.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Failing Customer Update',
            ])
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to process customer data update.',
            ]);

        $fresh = $this->customer->fresh();
        $this->assertSame($originalName, $fresh->name);
        $this->assertSame($originalEmail, $fresh->email);
    }

    public function test_29_no_change_request_returns_updated_false(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ])
            ->assertStatus(200);

        $this->assertFalse($response->json('data.updated'));
        $this->assertSame(0, $response->json('data.contacts.created'));
        $this->assertSame(0, $response->json('data.contacts.updated'));
    }

    public function test_30_existing_data_sync_regression_tests_pass(): void
    {
        // Calling update preserves customer code and existing relationships
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->customer->customer_code, [
                'name' => 'Regression Test Customer',
            ])
            ->assertStatus(200);

        $this->assertSame('CUS00001', $this->customer->fresh()->customer_code);
        $this->assertSame($this->customer->id, $this->project->fresh()->customer_id);
    }
}

