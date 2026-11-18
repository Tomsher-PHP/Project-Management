<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveLoginSession;
use App\Http\Middleware\PermissionByType;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();
    }

    public function test_primary_contact_without_login_accepts_each_contact_method_individually(): void
    {
        foreach (['primary_mobile', 'primary_whatsapp', 'primary_landline'] as $index => $method) {
            $this->post(route('customers.store'), $this->payload([
                'name' => "Contact Method Customer {$index}",
                'primary_name' => "Contact Method Person {$index}",
                'primary_email' => null,
                'primary_mobile' => null,
                'primary_whatsapp' => null,
                'primary_landline' => null,
                $method => '+971501234567',
                'primary_login_enabled' => false,
            ]))->assertRedirect(route('customers.index'));

            $contact = CustomerContact::where('name', "Contact Method Person {$index}")->sole();

            $this->assertFalse($contact->login_enabled);
            $this->assertNull($contact->password);
            $this->assertNull($contact->password_set_at);
        }

        Mail::assertNothingSent();
    }

    public function test_primary_contact_requires_at_least_one_contact_method(): void
    {
        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'primary_email' => null,
                'primary_mobile' => null,
                'primary_whatsapp' => null,
                'primary_landline' => null,
                'primary_login_enabled' => false,
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors([
                'primary_email' => 'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
            ]);
    }

    public function test_login_access_requires_email_but_does_not_require_password(): void
    {
        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'primary_email' => null,
                'primary_mobile' => '+971501234567',
                'primary_login_enabled' => true,
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors([
                'primary_email' => 'Email is required when login access is enabled.',
            ]);

        $this->post(route('customers.store'), $this->payload([
            'name' => 'No Password Customer',
            'primary_email' => 'portal@example.test',
            'primary_login_enabled' => true,
            'primary_password' => null,
        ]))
            ->assertRedirect(route('customers.index'));

        $contact = CustomerContact::where('email', 'portal@example.test')->sole();

        $this->assertTrue($contact->login_enabled);
        $this->assertNull($contact->password);
        $this->assertNull($contact->password_set_at);
    }

    public function test_password_is_hashed_by_the_model_and_timestamped(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'primary_email' => 'secure@example.test',
            'primary_login_enabled' => true,
            'primary_password' => 'Secure123',
        ]))
            ->assertRedirect(route('customers.index'));

        $contact = CustomerContact::where('email', 'secure@example.test')->sole();

        $this->assertNotSame('Secure123', $contact->password);
        $this->assertTrue(Hash::check('Secure123', $contact->password));
        $this->assertNotNull($contact->password_set_at);
        $this->assertArrayNotHasKey('password', $contact->toArray());
        Mail::assertNothingSent();
    }

    public function test_duplicate_contact_email_is_rejected_and_own_email_is_allowed_on_update(): void
    {
        CustomerContact::factory()->create(['email' => 'taken@example.test']);

        $this->post(route('customers.store'), $this->payload([
            'primary_email' => 'taken@example.test',
            'primary_login_enabled' => true,
        ]))
            ->assertSessionHasErrors('primary_email');

        $customer = $this->createCustomerWithPrimaryContact();

        $this->put(route('customers.update', $customer), $this->updatePayload($customer))
            ->assertRedirect();
    }

    public function test_blank_password_preserves_existing_password_when_access_is_disabled_and_reenabled(): void
    {
        $customer = $this->createCustomerWithPrimaryContact('Original123');
        $contact = $customer->primaryContact;
        $originalHash = $contact->password;
        $originalSetAt = $contact->password_set_at?->toISOString();

        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'primary_login_enabled' => false,
            'primary_password' => null,
        ]))
            ->assertRedirect();

        $contact->refresh();
        $this->assertFalse($contact->login_enabled);
        $this->assertSame($originalHash, $contact->password);
        $this->assertSame($originalSetAt, $contact->password_set_at?->toISOString());

        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'primary_login_enabled' => true,
            'primary_password' => '',
        ]))
            ->assertRedirect();

        $contact->refresh();
        $this->assertTrue($contact->login_enabled);
        $this->assertSame($originalHash, $contact->password);
        $this->assertSame($originalSetAt, $contact->password_set_at?->toISOString());
    }

    public function test_new_password_replaces_hash_and_password_timestamp(): void
    {
        $customer = $this->createCustomerWithPrimaryContact('Original123');
        $contact = $customer->primaryContact;
        $oldHash = $contact->password;
        $oldTimestamp = $contact->password_set_at;

        $this->travel(1)->minute();

        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'primary_password' => 'Replacement456',
        ]))
            ->assertRedirect();

        $contact->refresh();
        $this->assertNotSame($oldHash, $contact->password);
        $this->assertTrue(Hash::check('Replacement456', $contact->password));
        $this->assertTrue($contact->password_set_at->greaterThan($oldTimestamp));
    }

    public function test_extra_contact_login_access_and_password_transitions_are_persisted(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'contacts' => [[
                'name' => 'Extra Contact',
                'email' => 'extra@example.test',
                'mobile' => null,
                'landline' => null,
                'whatsapp' => null,
                'designation' => 'Manager',
                'login_enabled' => true,
                'password' => 'Extra1234',
            ], [
                'name' => 'Passwordless Extra Contact',
                'email' => 'passwordless-extra@example.test',
                'login_enabled' => true,
            ], [
                'name' => 'No Login Extra Contact',
                'email' => null,
                'landline' => '+97141234567',
                'login_enabled' => false,
            ]],
        ]))
            ->assertRedirect(route('customers.index'));

        $customer = Customer::where('name', 'Test Customer')->sole();
        $extra = $customer->extraContacts()->where('email', 'extra@example.test')->sole();
        $passwordless = $customer->extraContacts()->where('email', 'passwordless-extra@example.test')->sole();
        $noLogin = $customer->extraContacts()->where('name', 'No Login Extra Contact')->sole();
        $originalHash = $extra->password;
        $originalTimestamp = $extra->password_set_at?->toISOString();

        $this->assertTrue($extra->login_enabled);
        $this->assertTrue(Hash::check('Extra1234', $originalHash));
        $this->assertTrue($passwordless->login_enabled);
        $this->assertNull($passwordless->password);
        $this->assertFalse($noLogin->login_enabled);
        $this->assertNull($noLogin->password);

        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'contacts' => [[
                'id' => $extra->id,
                'name' => $extra->name,
                'email' => $extra->email,
                'designation' => $extra->designation,
                'login_enabled' => false,
                'password' => '',
            ], [
                'id' => $passwordless->id,
                'name' => $passwordless->name,
                'email' => $passwordless->email,
                'login_enabled' => true,
            ], [
                'id' => $noLogin->id,
                'name' => $noLogin->name,
                'landline' => $noLogin->landline,
                'login_enabled' => false,
            ]],
        ]))
            ->assertRedirect();

        $extra->refresh();
        $this->assertFalse($extra->login_enabled);
        $this->assertSame($originalHash, $extra->password);
        $this->assertSame($originalTimestamp, $extra->password_set_at?->toISOString());

        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'contacts' => [[
                'id' => $extra->id,
                'name' => $extra->name,
                'email' => $extra->email,
                'designation' => $extra->designation,
                'login_enabled' => true,
            ], [
                'id' => $passwordless->id,
                'name' => $passwordless->name,
                'email' => $passwordless->email,
                'login_enabled' => true,
            ], [
                'id' => $noLogin->id,
                'name' => $noLogin->name,
                'landline' => $noLogin->landline,
                'login_enabled' => false,
            ]],
        ]))
            ->assertRedirect();

        $extra->refresh();
        $this->assertTrue($extra->login_enabled);
        $this->assertSame($originalHash, $extra->password);
    }

    public function test_extra_contact_without_login_requires_one_contact_method(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'contacts' => [[
                'name' => 'Unreachable Contact',
                'email' => null,
                'mobile' => null,
                'landline' => null,
                'whatsapp' => null,
                'login_enabled' => false,
            ]],
        ]))
            ->assertSessionHasErrors([
                'contacts.0.email' => 'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
            ]);
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
            'primary_login_enabled' => false,
            'primary_password' => null,
            'contacts' => [],
        ], $overrides);
    }

    private function createCustomerWithPrimaryContact(?string $password = null): Customer
    {
        $customer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Existing Customer',
            'sales_person_id' => $this->salesPerson->id,
        ]);

        $customer->contacts()->create([
            'name' => 'Existing Primary',
            'email' => 'existing@example.test',
            'is_primary' => true,
            'login_enabled' => true,
            'password' => $password,
            'password_set_at' => $password ? now() : null,
        ]);

        return $customer->fresh('primaryContact');
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
            'primary_login_enabled' => $customer->primaryContact->login_enabled,
            'primary_password' => null,
            'contacts' => [],
        ], $overrides);
    }
}
