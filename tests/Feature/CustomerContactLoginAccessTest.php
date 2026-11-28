<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveLoginSession;
use App\Http\Middleware\PermissionByType;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CustomerContactLoginAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $salesPerson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesPerson = User::factory()->create();
        $this->actingAs($this->salesPerson);
        $this->withoutMiddleware([
            EnsureActiveLoginSession::class,
            PermissionByType::class,
        ]);
    }

    public function test_customer_contact_model_is_a_standard_eloquent_model_without_auth_attributes(): void
    {
        $contact = new CustomerContact();

        $this->assertInstanceOf(Model::class, $contact);
        $this->assertNotInstanceOf(Authenticatable::class, $contact);

        $authAttributes = [
            'login_enabled',
            'password',
            'password_set_at',
            'last_login_at',
            'email_verified_at',
            'force_password_reset',
        ];

        foreach ($authAttributes as $attribute) {
            $this->assertNotContains($attribute, $contact->getFillable());
            $this->assertArrayNotHasKey($attribute, $contact->getCasts());
            $this->assertNotContains($attribute, $contact->getHidden());
        }
    }

    public function test_customer_creation_and_primary_contact_accept_each_contact_method_individually(): void
    {
        foreach (['primary_email', 'primary_mobile', 'primary_whatsapp', 'primary_landline'] as $index => $method) {
            $value = $method === 'primary_email' ? "contact{$index}@example.test" : '+971501234567';

            $this->post(route('customers.store'), $this->payload([
                'name' => "Contact Method Customer {$index}",
                'primary_name' => "Contact Method Person {$index}",
                'primary_email' => null,
                'primary_mobile' => null,
                'primary_whatsapp' => null,
                'primary_landline' => null,
                $method => $value,
            ]))->assertRedirect(route('customers.index'));

            $contact = CustomerContact::where('name', "Contact Method Person {$index}")->sole();
            $this->assertTrue($contact->is_primary);
        }
    }

    public function test_primary_contact_requires_at_least_one_contact_method(): void
    {
        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'primary_email' => null,
                'primary_mobile' => null,
                'primary_whatsapp' => null,
                'primary_landline' => null,
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors([
                'primary_email' => 'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
            ]);
    }

    public function test_extra_contact_creation_updating_and_removal(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'contacts' => [[
                'name' => 'Extra Contact One',
                'email' => 'extra1@example.test',
                'designation' => 'Manager',
            ], [
                'name' => 'Extra Contact Two',
                'email' => null,
                'landline' => '+97141234567',
            ]],
        ]))->assertRedirect(route('customers.index'));

        $customer = Customer::where('name', 'Test Customer')->sole();
        $extraOne = $customer->extraContacts()->where('name', 'Extra Contact One')->sole();
        $extraTwo = $customer->extraContacts()->where('name', 'Extra Contact Two')->sole();

        $this->assertSame('extra1@example.test', $extraOne->email);
        $this->assertSame('+97141234567', $extraTwo->landline);

        // Update Extra Contact One and remove Extra Contact Two
        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'name' => 'Updated Test Customer',
            'primary_name' => 'Updated Primary Contact',
            'contacts' => [[
                'id' => $extraOne->id,
                'name' => 'Extra Contact One Updated',
                'email' => 'extra1-updated@example.test',
                'designation' => 'Senior Manager',
            ]],
        ]))->assertRedirect();

        $customer->refresh();
        $this->assertSame('Updated Test Customer', $customer->name);
        $this->assertSame('Updated Primary Contact', $customer->primaryContact->name);

        $extraOne->refresh();
        $this->assertSame('Extra Contact One Updated', $extraOne->name);
        $this->assertSame('extra1-updated@example.test', $extraOne->email);

        $this->assertSoftDeleted('customer_contacts', ['id' => $extraTwo->id]);
    }

    public function test_extra_contact_requires_one_contact_method_and_valid_email_format(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'contacts' => [[
                'name' => 'Unreachable Contact',
                'email' => null,
                'mobile' => null,
                'landline' => null,
                'whatsapp' => null,
            ]],
        ]))->assertSessionHasErrors([
            'contacts.0.email' => 'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
        ]);

        $this->post(route('customers.store'), $this->payload([
            'contacts' => [[
                'name' => 'Invalid Email Contact',
                'email' => 'not-an-email',
            ]],
        ]))->assertSessionHasErrors([
            'contacts.0.email',
        ]);
    }

    public function test_customer_request_validation_accepts_duplicate_contact_emails(): void
    {
        CustomerContact::factory()->create(['email' => 'shared@example.test']);

        $request = CustomerRequest::create(route('customers.store'), 'POST', $this->payload([
            'primary_email' => 'shared@example.test',
            'contacts' => [[
                'name' => 'Duplicate Email Extra',
                'email' => 'shared@example.test',
            ]],
        ]));

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        );
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Test Customer',
            'email' => null,
            'sales_person_id' => $this->salesPerson->id,
            'primary_name' => 'Primary Contact',
            'primary_email' => 'primary@example.test',
            'primary_mobile' => null,
            'primary_whatsapp' => null,
            'primary_landline' => null,
            'primary_designation' => 'Director',
            'contacts' => [],
        ], $overrides);
    }

    private function updatePayload(Customer $customer, array $overrides = []): array
    {
        return array_replace([
            'name' => $customer->name,
            'email' => $customer->email,
            'sales_person_id' => $customer->sales_person_id,
            'primary_name' => $customer->primaryContact->name,
            'primary_email' => $customer->primaryContact->email,
            'primary_mobile' => $customer->primaryContact->mobile,
            'primary_whatsapp' => $customer->primaryContact->whatsapp,
            'primary_landline' => $customer->primaryContact->landline,
            'primary_designation' => $customer->primaryContact->designation,
            'contacts' => [],
        ], $overrides);
    }
}
