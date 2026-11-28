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
    protected string $externalCode = 'CRM-CUS-00001';
    protected DataSyncEntityMapping $mapping;

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

        $this->mapping = DataSyncEntityMapping::create([
            'api_client_id' => $this->apiClient->id,
            'entity_type' => DataSyncEntityMapping::ENTITY_TYPE_CUSTOMER,
            'external_code' => $this->externalCode,
            'local_id' => $this->customer->id,
        ]);
    }

    public function test_1_existing_customer_updates_successfully(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
                        'deleted' => 0,
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
            ->putJson('/api/integrations/customers/NONEXISTENT_EXTERNAL_CODE', [
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
        $this->putJson('/api/integrations/customers/' . $this->externalCode, [
            'name' => 'Unauthorized Customer Update',
        ])->assertStatus(401);
    }

    public function test_4_missing_projects_write_scope_returns_403(): void
    {
        $readResult = app(ApiClientService::class)->createClient('Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $readResult['plain_text_key'])
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Brand New Customer Name LLC',
            ])
            ->assertStatus(200);

        $this->assertSame('Brand New Customer Name LLC', $this->customer->fresh()->name);
    }

    public function test_6_customer_email_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'industry_code' => $this->secondIndustry->code,
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondIndustry->id, $this->customer->fresh()->industry_id);
    }

    public function test_9_invalid_industry_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'industry_code' => '999999',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['industry_code']);
    }

    public function test_10_country_resolution(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'country_code' => 'sa',
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondCountry->id, $this->customer->fresh()->registered_country_id);
    }

    public function test_11_invalid_country_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'country_code' => 'ZZ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);
    }

    public function test_12_salesperson_resolution(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'sales_person_email' => $this->secondSalesUser->email,
            ])
            ->assertStatus(200);

        $this->assertSame($this->secondSalesUser->id, $this->customer->fresh()->sales_person_id);
    }

    public function test_13_invalid_salesperson_returns_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'sales_person_email' => 'nobody@nonexistent.ae',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sales_person_email']);
    }

    public function test_14_active_status_update(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'is_active' => false,
            ])
            ->assertStatus(200);

        $this->assertFalse($this->customer->fresh()->is_active);
    }

    public function test_15_website_and_address_updates(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'website' => 'https://newdomain.com',
                'company_address' => 'New Office Tower, Floor 10, Business Bay',
            ])
            ->assertStatus(200);

        $fresh = $this->customer->fresh();
        $this->assertSame('https://newdomain.com', $fresh->website);
        $this->assertSame('New Office Tower, Floor 10, Business Bay', $fresh->company_address);
    }

    public function test_16_replace_multiple_existing_contacts_with_one_new_contact(): void
    {
        $oldId1 = $this->contact1->id;
        $oldId2 = $this->contact2->id;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Single Replacement Contact',
                        'email' => 'single@origcompany.ae',
                        'designation' => 'Chief Technology Officer',
                        'mobile' => '+971509990000',
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated' => true,
                    'contacts' => [
                        'deleted' => 2,
                        'created' => 1,
                        'updated' => 0,
                    ],
                ],
            ]);

        $this->assertNull(CustomerContact::withTrashed()->find($oldId1));
        $this->assertNull(CustomerContact::withTrashed()->find($oldId2));

        $contacts = CustomerContact::where('customer_id', $this->customer->id)->get();
        $this->assertCount(1, $contacts);
        $this->assertSame('Single Replacement Contact', $contacts->first()->name);
        $this->assertNotSame($oldId1, $contacts->first()->id);
        $this->assertNotSame($oldId2, $contacts->first()->id);
    }

    public function test_17_replace_existing_contacts_with_multiple_new_contacts(): void
    {
        $oldIds = [$this->contact1->id, $this->contact2->id];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Brand New Contact 1',
                        'email' => 'new1@origcompany.ae',
                        'mobile' => '+971509998877',
                        'designation' => 'Project Lead',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Brand New Contact 2',
                        'email' => 'new2@origcompany.ae',
                        'whatsapp' => '+971509998878',
                        'landline' => '+97143334455',
                        'is_primary' => false,
                    ],
                    [
                        'name' => 'Brand New Contact 3',
                        'mobile' => '+971509998879',
                        'is_active' => false,
                    ],
                ],
            ])
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'updated' => true,
                    'contacts' => [
                        'deleted' => 2,
                        'created' => 3,
                        'updated' => 0,
                    ],
                ],
            ]);

        $contacts = CustomerContact::where('customer_id', $this->customer->id)->orderBy('id')->get();
        $this->assertCount(3, $contacts);
        foreach ($contacts as $contact) {
            $this->assertNotContains($contact->id, $oldIds);
        }
        $this->assertFalse($contacts[2]->is_active);
    }

    public function test_18_existing_contacts_remain_unchanged_when_omitted(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Only Name Changed',
            ])
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'updated' => true,
                    'contacts' => [
                        'deleted' => 0,
                        'created' => 0,
                        'updated' => 0,
                    ],
                ],
            ]);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
        $this->assertSame('Primary Contact', $this->contact1->fresh()->name);
        $this->assertSame('Secondary Contact', $this->contact2->fresh()->name);
    }

    public function test_19_empty_contacts_array_removes_all_existing_contacts(): void
    {
        $oldId1 = $this->contact1->id;
        $oldId2 = $this->contact2->id;

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [],
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated' => true,
                    'contacts' => [
                        'deleted' => 2,
                        'created' => 0,
                        'updated' => 0,
                    ],
                ],
            ]);

        $this->assertSame(0, CustomerContact::where('customer_id', $this->customer->id)->count());
        $this->assertNull(CustomerContact::withTrashed()->find($oldId1));
        $this->assertNull(CustomerContact::withTrashed()->find($oldId2));
    }

    public function test_20_payload_containing_contact_ids_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'id' => $this->contact1->id,
                        'name' => 'Contact With ID',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.0.id']);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_21_contact_without_name_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'email' => 'noname@origcompany.ae',
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.0.name']);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_22_multiple_primary_contacts_are_prevented(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Primary One',
                        'email' => 'p1@origcompany.ae',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Primary Two',
                        'email' => 'p2@origcompany.ae',
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts']);
    }

    public function test_23_external_mapping_verification(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'external_code' => $this->externalCode,
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
            'external_id' => 'EXT-CUS-OTHER-ID',
            'local_id' => $otherCustomer->id,
        ]);

        // Attempt to update Customer 1 claiming external id of Customer 2
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'external_id' => 'EXT-CUS-OTHER-ID',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'External mapping belongs to a different customer.',
            ]);
    }

    public function test_24b_conflicting_external_code_returns_409(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'external_code' => 'CRM-CUS-OTHER-CODE',
            ])
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Conflicting external customer code.',
            ]);
    }

    public function test_25_safe_mapping_enrichment(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'external_code' => $this->externalCode,
                'external_id' => 'EXT-CUS-ID-999',
            ])
            ->assertStatus(200);

        $this->mapping->refresh();
        $this->assertSame($this->externalCode, $this->mapping->external_code);
        $this->assertSame('EXT-CUS-ID-999', $this->mapping->external_id);
    }

    public function test_26_no_unnecessary_customer_or_contact_duplication(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Idempotent Customer Name',
            ])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            public function update(string $externalCode, array $data, ?ApiClient $apiClient = null): array
            {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($externalCode) {
                    $mapping = DataSyncEntityMapping::where('external_code', $externalCode)->first();
                    $customer = Customer::where('id', $mapping->local_id)->first();
                    $customer->update(['name' => 'Crash Test Customer Name']);

                    throw new Exception('Simulated customer update crash.');
                });
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
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
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ])
            ->assertStatus(200);

        $this->assertFalse($response->json('data.updated'));
        $this->assertSame(0, $response->json('data.contacts.deleted'));
        $this->assertSame(0, $response->json('data.contacts.created'));
        $this->assertSame(0, $response->json('data.contacts.updated'));
    }

    public function test_30_existing_data_sync_regression_tests_pass(): void
    {
        // Calling update preserves customer code and existing relationships
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Regression Test Customer',
            ])
            ->assertStatus(200);

        $this->assertSame('CUS00001', $this->customer->fresh()->customer_code);
        $this->assertSame($this->customer->id, $this->project->fresh()->customer_id);
    }

    public function test_31_create_contacts_for_customer_without_existing_contacts(): void
    {
        $this->customer->contacts()->forceDelete();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'First Contact',
                        'email' => 'first@origcompany.ae',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Second Contact',
                        'email' => 'second@origcompany.ae',
                    ],
                ],
            ])
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'updated' => true,
                    'contacts' => [
                        'deleted' => 0,
                        'created' => 2,
                        'updated' => 0,
                    ],
                ],
            ]);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_32_duplicate_emails_within_payload_are_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Contact A',
                        'email' => 'Same@OrigCompany.ae',
                    ],
                    [
                        'name' => 'Contact B',
                        'email' => 'same@origcompany.ae',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.1.email']);

        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_33_email_belonging_to_other_customer_is_rejected_and_other_contacts_unaffected(): void
    {
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS00002',
            'name' => 'Other Customer LLC',
        ]);

        $otherContact = CustomerContact::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Other Contact',
            'email' => 'taken@othercompany.ae',
            'is_primary' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Hijack Email Contact',
                        'email' => 'TAKEN@othercompany.ae',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.0.email']);

        // Both customers' contacts remain untouched
        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
        $this->assertNotNull($otherContact->fresh());
    }

    public function test_34_contacts_of_other_customers_remain_unaffected_on_replacement(): void
    {
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS00002',
            'name' => 'Other Customer LLC',
        ]);

        $otherContact = CustomerContact::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Other Contact',
            'email' => 'other@othercompany.ae',
            'is_primary' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Replaced Contact',
                        'email' => 'replaced@origcompany.ae',
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertSame(1, CustomerContact::where('customer_id', $otherCustomer->id)->count());
        $this->assertSame('Other Contact', $otherContact->fresh()->name);
    }

    public function test_36_previously_soft_deleted_email_conflict_returns_422(): void
    {
        $trashedContact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name' => 'Archived Contact',
            'email' => 'archived@origcompany.ae',
        ]);
        $trashedContact->delete();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'contacts' => [
                    [
                        'name' => 'Reused Archived Email',
                        'email' => 'archived@origcompany.ae',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts.0.email']);

        // Active contacts and soft-deleted record remain intact
        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
        $this->assertNotNull(CustomerContact::onlyTrashed()->find($trashedContact->id));
    }

    public function test_37_transaction_rollback_restores_deleted_contacts_when_creation_fails(): void
    {
        $originalId1 = $this->contact1->id;
        $originalId2 = $this->contact2->id;

        CustomerContact::creating(function (CustomerContact $contact) {
            if ($contact->name === 'Trigger Failure') {
                throw new Exception('Simulated contact creation failure.');
            }
        });

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Should Rollback Name',
                'contacts' => [
                    [
                        'name' => 'Valid First',
                        'email' => 'valid@origcompany.ae',
                    ],
                    [
                        'name' => 'Trigger Failure',
                        'email' => 'fail@origcompany.ae',
                    ],
                ],
            ])
            ->assertStatus(500);

        $this->assertSame('Original Customer LLC', $this->customer->fresh()->name);
        $this->assertNotNull(CustomerContact::find($originalId1));
        $this->assertNotNull(CustomerContact::find($originalId2));
        $this->assertSame(2, CustomerContact::where('customer_id', $this->customer->id)->count());
    }

    public function test_38_spatie_activity_log_records_customer_update_and_contact_replacement(): void
    {
        \Spatie\Activitylog\Models\Activity::query()->delete();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->putJson('/api/integrations/customers/' . $this->externalCode, [
                'name' => 'Activity Logged Customer LLC',
                'contacts' => [
                    [
                        'name' => 'Primary Contact',
                        'email' => 'primary@origcompany.ae',
                        'is_primary' => true,
                    ],
                ],
            ])
            ->assertStatus(200);

        $customerUpdateActivity = \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'customers')
            ->where('subject_type', Customer::class)
            ->where('subject_id', $this->customer->id)
            ->where('description', 'customers.updated')
            ->first();

        $this->assertNotNull($customerUpdateActivity);
        $this->assertSame('Activity Logged Customer LLC', $customerUpdateActivity->properties['attributes']['name'] ?? null);
        $this->assertSame('Original Customer LLC', $customerUpdateActivity->properties['old']['name'] ?? null);

        $contactsReplacedActivity = \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'customers')
            ->where('subject_type', Customer::class)
            ->where('subject_id', $this->customer->id)
            ->where('description', 'customers.contacts_replaced')
            ->first();

        $this->assertNotNull($contactsReplacedActivity);
        $this->assertSame(ApiClient::class, $contactsReplacedActivity->causer_type);
        $this->assertSame($this->apiClient->id, $contactsReplacedActivity->causer_id);
        $this->assertSame(2, $contactsReplacedActivity->properties['deleted_count'] ?? null);
        $this->assertSame(1, $contactsReplacedActivity->properties['created_count'] ?? null);
        $this->assertStringNotContainsString('PortalPass123', json_encode($contactsReplacedActivity->properties));
        $this->assertStringNotContainsString('password', json_encode($contactsReplacedActivity->properties));

        $deletedContactActivities = \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'customer_contacts')
            ->where('description', 'customer_contacts.deleted')
            ->get();
        $this->assertCount(2, $deletedContactActivities);
        foreach ($deletedContactActivities as $deletedActivity) {
            $this->assertSame('Customer', $deletedActivity->properties['labels']['customer_id'] ?? null);
            $this->assertSame('Activity Logged Customer LLC', $deletedActivity->properties['display_old']['customer_id'] ?? null);
            $this->assertSame('Activity Logged Customer LLC', $deletedActivity->properties['customer_name'] ?? null);
        }

        $createdContactActivity = \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'customer_contacts')
            ->where('description', 'customer_contacts.created')
            ->sole();
        $this->assertSame('Customer', $createdContactActivity->properties['labels']['customer_id'] ?? null);
        $this->assertSame('Activity Logged Customer LLC', $createdContactActivity->properties['display_attributes']['customer_id'] ?? null);
        $this->assertSame('Activity Logged Customer LLC', $createdContactActivity->properties['customer_name'] ?? null);
    }
}

