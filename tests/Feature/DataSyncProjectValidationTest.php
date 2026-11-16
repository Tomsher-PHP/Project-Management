<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Industry;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\ApiClientService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DataSyncProjectValidationTest extends TestCase
{
    use RefreshDatabase;

    protected string $authorizedKey;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api-client:*');

        ProjectStatus::firstOrCreate(['is_default' => 1], [
            'name' => 'Planned',
            'code' => '839533',
            'type' => 'open',
            'sort_order' => 1,
            'is_completed' => 0,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        ProjectStage::firstOrCreate(['is_default' => 1], [
            'name' => 'Planning',
            'code' => '123456',
            'color' => '#000000',
            'sort_order' => 1,
            'is_system' => 1,
            'is_active' => 1,
        ]);

        Industry::firstOrCreate(['code' => '743813'], [
            'name' => 'Technology',
        ]);

        Country::firstOrCreate(['code' => 'AE'], [
            'name' => 'United Arab Emirates',
        ]);

        User::firstOrCreate(['email' => 'sales@abctech.ae'], [
            'name' => 'Sales Person',
            'password' => bcrypt('password'),
        ]);

        $result = app(ApiClientService::class)->createClient('Validation Test Client', [
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $this->authorizedKey = $result['plain_text_key'];
    }

    protected function validPayload(array $overrides = []): array
    {
        $base = [
            'customer' => [
                'external_code' => 'CRM-CUS-1025',
                'external_id' => '125',
                'name' => 'ABC Technologies LLC',
                'email' => 'info@abctech.ae',
                'industry_code' => '743813',
                'website' => 'https://abctech.ae',
                'country_code' => 'AE',
                'emirate' => 'Dubai',
                'company_address' => 'Business Bay, Dubai, UAE',
                'sales_person_email' => 'sales@abctech.ae',
                'is_active' => true,
                'contacts' => [
                    [
                        'name' => 'Ahmed Mohammed',
                        'email' => 'ahmed@abctech.ae',
                        'mobile' => '+971501234567',
                        'whatsapp' => '+971501234567',
                        'landline' => null,
                        'designation' => 'General Manager',
                        'is_primary' => true,
                        'is_active' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'external_code' => 'CRM-PRJ-1001',
                    'external_id' => '201',
                    'name' => 'Corporate Website Development',
                    'project_flow' => 'agile',
                    'priority' => 'high',
                    'status_code' => '839533',
                    'stage_code' => '123456',
                    'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                    'end_date' => Carbon::now()->addDays(35)->format('Y-m-d'),
                    'customer_end_date' => Carbon::now()->addDays(40)->format('Y-m-d'),
                    'estimated_time_minutes' => 12000,
                    'customer_estimate_minutes' => 10000,
                    'default_task_estimate_seconds' => 3600,
                    'domain' => 'abctech.ae',
                    'category_codes' => ['397711'],
                    'technology_codes' => ['112233'],
                    'service_type_codes' => ['445566'],
                    'parent_project_code' => null,
                    'sales_person_email' => 'sales@abctech.ae',
                    'default_billable' => true,
                    'is_active' => true,
                ],
            ],
        ];

        return array_replace_recursive($base, $overrides);
    }

    public function test_1_valid_single_project_payload_returns_201(): void
    {
        $payload = $this->validPayload();

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer and projects processed successfully.',
            ]);
    }

    public function test_2_valid_multiple_project_payload_returns_201(): void
    {
        $payload = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Project Phase 1',
                    'priority' => 'medium',
                ],
                [
                    'name' => 'Project Phase 2',
                    'priority' => 'high',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer and projects processed successfully.',
            ]);
    }

    public function test_3_missing_customer_object_fails_validation(): void
    {
        $payload = [
            'projects' => [
                ['name' => 'Stand-alone Project'],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['customer']);
    }

    public function test_4_missing_project_array_fails_validation(): void
    {
        $payload = [
            'customer' => [
                'name' => 'Customer Only',
                'contacts' => [
                    ['name' => 'Contact 1', 'email' => 'c1@test.com', 'is_primary' => true],
                ],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['projects']);
    }

    public function test_5_empty_project_array_fails_validation(): void
    {
        $payload = [
            'customer' => [
                'name' => 'Customer Only',
                'contacts' => [
                    ['name' => 'Contact 1', 'email' => 'c1@test.com', 'is_primary' => true],
                ],
            ],
            'projects' => [],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonValidationErrors(['projects']);
    }

    public function test_6_invalid_customer_email_fails_validation(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'email' => 'not-an-email',
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.email']);
    }

    public function test_7_missing_customer_name_for_new_customer_fails_validation(): void
    {
        $payload = $this->validPayload();
        unset($payload['customer']['name']);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.name']);
    }

    public function test_8_missing_contacts_for_new_customer_fails_validation(): void
    {
        $payload = $this->validPayload();
        $payload['customer']['contacts'] = [];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.contacts']);
    }

    public function test_9_missing_primary_contact_fails_validation(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'John Non-Primary',
                        'email' => 'john@test.com',
                        'is_primary' => false,
                    ],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.contacts'])
            ->assertJsonFragment([
                'customer.contacts' => ['A primary contact is required.'],
            ]);
    }

    public function test_10_multiple_primary_contacts_fails_validation(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Primary 1',
                        'email' => 'p1@test.com',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Primary 2',
                        'email' => 'p2@test.com',
                        'is_primary' => true,
                    ],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.contacts'])
            ->assertJsonFragment([
                'customer.contacts' => ['Multiple primary contacts are not allowed.'],
            ]);
    }

    public function test_11_primary_contact_without_communication_details_fails_validation(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Silent Primary',
                        'email' => null,
                        'mobile' => null,
                        'whatsapp' => null,
                        'landline' => null,
                        'is_primary' => true,
                    ],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.contacts.0.email']);
    }

    public function test_12_valid_secondary_contacts_are_accepted(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'contacts' => [
                    [
                        'name' => 'Primary Contact',
                        'email' => 'primary@test.com',
                        'is_primary' => true,
                    ],
                    [
                        'name' => 'Secondary Contact',
                        'email' => null,
                        'mobile' => '+971509999999',
                        'is_primary' => false,
                        'is_active' => true,
                    ],
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);
    }

    public function test_13_valid_external_code_accepted(): void
    {
        $payload = $this->validPayload([
            'customer' => ['external_code' => 'EXT-CUS-999'],
            'projects' => [['name' => 'P1', 'external_code' => 'EXT-PRJ-888']],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);
    }

    public function test_14_valid_external_id_accepted_as_string_or_integer(): void
    {
        $payload = $this->validPayload([
            'customer' => ['external_id' => 9999],
            'projects' => [['name' => 'P1', 'external_id' => 8888]],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);
    }

    public function test_15_missing_external_identifiers_accepted(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'external_code' => null,
                'external_id' => null,
            ],
            'projects' => [
                [
                    'name' => 'P1',
                    'external_code' => null,
                    'external_id' => null,
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);
    }

    public function test_16_invalid_project_flow_fails_validation(): void
    {
        $payload = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Invalid Flow Project',
                    'project_flow' => 'waterfall',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.project_flow']);
    }

    public function test_17_invalid_priority_fails_validation(): void
    {
        $payload = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Invalid Priority Project',
                    'priority' => 'critical',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.priority']);
    }

    public function test_18_invalid_industry_code_format_fails_validation(): void
    {
        $payload = $this->validPayload([
            'customer' => [
                'industry_code' => 'ABC12', // Not 6 digits
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.industry_code']);
    }

    public function test_19_invalid_project_dates_fail_validation(): void
    {
        // end_date before start_date
        $payload1 = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Backwards Dates',
                    'start_date' => '2027-02-01',
                    'end_date' => '2027-01-01',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload1)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.end_date']);

        // customer_end_date before end_date
        $payload2 = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Backwards Customer End Date',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-02-01',
                    'customer_end_date' => '2027-01-15',
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload2)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['projects.0.customer_end_date']);
    }

    public function test_20_invalid_project_estimate_values_fail_validation(): void
    {
        $payload = $this->validPayload([
            'projects' => [
                [
                    'name' => 'Negative Estimates',
                    'estimated_time_minutes' => -50,
                    'customer_estimate_minutes' => -10,
                    'default_task_estimate_seconds' => -300,
                ],
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'projects.0.estimated_time_minutes',
                'projects.0.customer_estimate_minutes',
                'projects.0.default_task_estimate_seconds',
            ]);
    }

    public function test_21_project_default_normalization(): void
    {
        $payload = [
            'customer' => [
                'name' => 'Minimal Corp',
                'sales_person_email' => 'lead_sales@corp.ae',
                'contacts' => [
                    [
                        'name' => 'Main Contact',
                        'email' => 'main@corp.ae',
                        'is_primary' => true,
                    ],
                ],
            ],
            'projects' => [
                [
                    'name' => 'Minimal Project',
                ],
            ],
        ];

        // Resolves request and verifies normalized payload values
        $request = \App\Http\Requests\DataSync\ProjectSyncRequest::create(
            '/api/data-sync/projects',
            'POST',
            $payload
        );
        $keyId = explode('.', $this->authorizedKey)[0];
        $client = ApiClient::where('key_id', $keyId)->first();
        $request->attributes->set('api_client', $client);
        $request->setContainer(app());
        $request->validateResolved();

        $normalized = $request->normalizedPayload();

        $timezone = (string) config('constants.timezone', config('app.timezone', 'UTC'));
        $expectedStartDate = Carbon::now($timezone)->format('Y-m-d');
        $expectedEndDate = Carbon::parse($expectedStartDate, $timezone)->addDays(30)->format('Y-m-d');

        $this->assertSame('agile', $normalized['projects'][0]['project_flow']);
        $this->assertSame('medium', $normalized['projects'][0]['priority']);
        $this->assertTrue($normalized['projects'][0]['default_billable']);
        $this->assertTrue($normalized['projects'][0]['is_active']);
        $this->assertSame([], $normalized['projects'][0]['category_codes']);
        $this->assertSame([], $normalized['projects'][0]['technology_codes']);
        $this->assertSame([], $normalized['projects'][0]['service_type_codes']);
        $this->assertSame($expectedStartDate, $normalized['projects'][0]['start_date']);
        $this->assertSame($expectedEndDate, $normalized['projects'][0]['end_date']);
        $this->assertNull($normalized['projects'][0]['customer_end_date']);
        $this->assertSame('lead_sales@corp.ae', $normalized['projects'][0]['sales_person_email']);
    }

    public function test_22_array_indexed_validation_errors(): void
    {
        $payload = $this->validPayload([
            'projects' => [
                ['name' => 'Valid Project 0'],
                ['name' => ''], // Project 1 invalid
                ['name' => 'Valid Project 2', 'priority' => 'unsupported_priority'], // Project 2 invalid
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'projects.1.name',
                'projects.2.priority',
            ]);
    }

    public function test_23_missing_api_key_returns_401(): void
    {
        $this->postJson('/api/data-sync/projects', $this->validPayload())
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_24_missing_projects_write_scope_returns_403_before_validation(): void
    {
        $result = app(ApiClientService::class)->createClient('Read Only Client', [
            ApiClient::SCOPE_SETTINGS_READ,
        ]);
        $key = $result['plain_text_key'];

        // Even with a completely invalid payload, returns 403
        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/projects', [
                'invalid' => 'garbage payload',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient API permissions.',
            ]);
    }

    public function test_25_valid_payload_returns_success_201(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $this->validPayload())
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer and projects processed successfully.',
            ]);
    }

    public function test_26_no_customers_contacts_or_projects_are_created_on_validation_failure(): void
    {
        $customerCount = Customer::count();
        $contactCount = CustomerContact::count();
        $projectCount = Project::count();

        // Invalid payload: customer without name and contacts
        $invalidPayload = [
            'customer' => [],
            'projects' => [['name' => 'Invalid Project']],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $invalidPayload)
            ->assertStatus(422);

        $this->assertSame($customerCount, Customer::count());
        $this->assertSame($contactCount, CustomerContact::count());
        $this->assertSame($projectCount, Project::count());
    }

    public function test_27_existing_meeting_and_settings_apis_remain_unaffected(): void
    {
        $multiClient = app(ApiClientService::class)->createClient('Multi Client', [
            ApiClient::SCOPE_MEETINGS_READ,
            ApiClient::SCOPE_SETTINGS_READ,
            ApiClient::SCOPE_SETTINGS_WRITE,
            ApiClient::SCOPE_PROJECTS_WRITE,
        ]);
        $key = $multiClient['plain_text_key'];

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/meetings/active')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/data-sync/settings/technologies')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/data-sync/settings/technologies', ['name' => 'Kotlin'])
            ->assertStatus(201);
    }

    public function test_existing_customer_with_pms_customer_code_accepts_omitted_name_and_contacts(): void
    {
        Customer::create([
            'customer_code' => 'CUS00001',
            'name' => 'Existing Customer',
            'is_active' => true,
        ]);

        $payload = [
            'customer' => [
                'pms_customer_code' => 'CUS00001',
            ],
            'projects' => [
                [
                    'name' => 'Phase 3 for Existing Customer',
                ],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->authorizedKey)
            ->postJson('/api/data-sync/projects', $payload)
            ->assertStatus(201);
    }
}

