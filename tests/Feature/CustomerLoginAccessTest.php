<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveLoginSession;
use App\Http\Middleware\PermissionByType;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerLoginAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $salesPerson;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'company_name' => 'Test Company',
            'email' => 'admin@example.com',
        ]);

        $this->salesPerson = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);

        $this->actingAs($this->salesPerson);
        $this->withoutMiddleware([
            EnsureActiveLoginSession::class,
            PermissionByType::class,
        ]);
    }

    public function test_customer_model_supports_authentication_and_hides_password(): void
    {
        $customer = new Customer();

        $this->assertInstanceOf(Authenticatable::class, $customer);
        $this->assertContains('password', $customer->getHidden());
        $this->assertSame('boolean', $customer->getCasts()['login_enabled']);
        $this->assertSame('hashed', $customer->getCasts()['password']);
        $this->assertSame('datetime', $customer->getCasts()['password_set_at']);
        $this->assertSame('datetime', $customer->getCasts()['last_login_at']);
    }

    public function test_create_customer_without_login_access_stores_null_credentials(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'name' => 'No Login Corp',
            'email' => 'shared@company.test',
            'login_enabled' => '0',
            'username' => 'should-be-ignored',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]))->assertRedirect(route('customers.index'));

        $customer = Customer::where('name', 'No Login Corp')->sole();

        $this->assertFalse($customer->login_enabled);
        $this->assertNull($customer->username);
        $this->assertNull($customer->getRawOriginal('password'));
        $this->assertNull($customer->password_set_at);
    }

    public function test_create_customer_with_login_access_requires_username_and_matching_password(): void
    {
        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'login_enabled' => '1',
                'username' => '',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors(['username', 'password', 'password_confirmation']);

        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'login_enabled' => '1',
                'username' => 'portal_user',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors(['password']);

        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertFalse(session()->hasOldInput('password_confirmation'));
    }

    public function test_create_customer_with_login_access_normalizes_username_and_hashes_password(): void
    {
        $this->post(route('customers.store'), $this->payload([
            'name' => 'Portal Enabled Corp',
            'email' => 'info@portal.test',
            'login_enabled' => '1',
            'username' => '  Portal.User@Example.TEST  ',
            'password' => 'PortalPass123',
            'password_confirmation' => 'PortalPass123',
        ]))->assertRedirect(route('customers.index'));

        $customer = Customer::where('name', 'Portal Enabled Corp')->sole();

        $this->assertTrue($customer->login_enabled);
        $this->assertSame('portal.user@example.test', $customer->username);
        $this->assertNotSame('PortalPass123', $customer->getRawOriginal('password'));
        $this->assertTrue(Hash::check('PortalPass123', $customer->getRawOriginal('password')));
        $this->assertNotNull($customer->password_set_at);
        $this->assertArrayNotHasKey('password', $customer->toArray());
    }

    public function test_duplicate_company_email_is_accepted_while_duplicate_username_is_rejected_case_insensitively(): void
    {
        Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'First Corp',
            'email' => 'shared@example.test',
            'sales_person_id' => $this->salesPerson->id,
            'login_enabled' => true,
            'username' => 'unique.user',
            'password' => 'Secret123',
            'password_set_at' => now(),
        ]);

        // Same company email with different username succeeds
        $this->post(route('customers.store'), $this->payload([
            'name' => 'Second Corp',
            'email' => 'shared@example.test',
            'login_enabled' => '1',
            'username' => 'second.user',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]))->assertRedirect(route('customers.index'));

        // Case-insensitive duplicate username is rejected
        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->payload([
                'name' => 'Third Corp',
                'email' => 'shared@example.test',
                'login_enabled' => '1',
                'username' => '  UNIQUE.USER  ',
                'password' => 'Secret123',
                'password_confirmation' => 'Secret123',
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('username');
    }

    public function test_edit_customer_preserves_password_when_blank_and_updates_when_changed(): void
    {
        $customer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Editable Corp',
            'email' => 'edit@example.test',
            'sales_person_id' => $this->salesPerson->id,
            'login_enabled' => true,
            'username' => 'editable.user',
            'password' => 'Original123',
            'password_set_at' => now()->subDay(),
        ]);

        $originalHash = $customer->getRawOriginal('password');
        $originalSetAt = $customer->password_set_at->toISOString();

        // Update without password keeps existing hash and timestamp
        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'login_enabled' => '1',
            'username' => 'editable.user',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertRedirect();

        $customer->refresh();
        $this->assertSame($originalHash, $customer->getRawOriginal('password'));
        $this->assertSame($originalSetAt, $customer->password_set_at->toISOString());

        // Changing password requires confirmation
        $this->from(route('customers.edit', $customer))
            ->put(route('customers.update', $customer), $this->updatePayload($customer, [
                'login_enabled' => '1',
                'username' => 'editable.user',
                'password' => 'NewSecret456',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('customers.edit', $customer))
            ->assertSessionHasErrors('password');

        $this->travel(1)->minute();

        // Valid password change updates hash and timestamp
        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'login_enabled' => '1',
            'username' => 'editable.user',
            'password' => 'NewSecret456',
            'password_confirmation' => 'NewSecret456',
        ]))->assertRedirect();

        $customer->refresh();
        $this->assertNotSame($originalHash, $customer->getRawOriginal('password'));
        $this->assertTrue(Hash::check('NewSecret456', $customer->getRawOriginal('password')));
        $this->assertNotSame($originalSetAt, $customer->password_set_at->toISOString());
    }

    public function test_disabling_and_reenabling_login_access_preserves_existing_credentials(): void
    {
        $customer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Toggle Corp',
            'sales_person_id' => $this->salesPerson->id,
            'login_enabled' => true,
            'username' => 'toggle.user',
            'password' => 'Toggle123',
            'password_set_at' => now(),
        ]);

        $originalHash = $customer->getRawOriginal('password');

        // Disable login access
        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'login_enabled' => '0',
            'username' => '',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertRedirect();

        $customer->refresh();
        $this->assertFalse($customer->login_enabled);
        $this->assertSame('toggle.user', $customer->username);
        $this->assertSame($originalHash, $customer->getRawOriginal('password'));

        // Re-enable without entering a new password
        $this->put(route('customers.update', $customer), $this->updatePayload($customer, [
            'login_enabled' => '1',
            'username' => 'toggle.user',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertRedirect();

        $customer->refresh();
        $this->assertTrue($customer->login_enabled);
        $this->assertSame($originalHash, $customer->getRawOriginal('password'));
    }

    public function test_customer_list_and_details_display_login_status_without_exposing_passwords(): void
    {
        $enabledCustomer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'name' => 'Visible Portal Corp',
            'sales_person_id' => $this->salesPerson->id,
            'login_enabled' => true,
            'username' => 'visible.portal.user',
            'password' => 'Secret123',
            'password_set_at' => now(),
        ]);

        $indexResponse = $this->get(route('customers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Login Access');
        $indexResponse->assertSee('Yes');
        $indexResponse->assertDontSee('visible.portal.user');
        $indexResponse->assertDontSee($enabledCustomer->getRawOriginal('password'));

        $showResponse = $this->get(route('customers.show', $enabledCustomer));
        $showResponse->assertOk();
        $showResponse->assertSee('Login Access');
        $showResponse->assertSee('Yes');
        $showResponse->assertSee('visible.portal.user');
        $showResponse->assertDontSee($enabledCustomer->getRawOriginal('password'));
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Test Customer',
            'email' => null,
            'sales_person_id' => $this->salesPerson->id,
            'login_enabled' => '0',
            'username' => null,
            'password' => null,
            'password_confirmation' => null,
            'primary_name' => 'Primary Contact',
            'primary_email' => 'primary@example.test',
            'contacts' => [],
        ], $overrides);
    }

    private function updatePayload(Customer $customer, array $overrides = []): array
    {
        return array_replace([
            'name' => $customer->name,
            'email' => $customer->email,
            'sales_person_id' => $customer->sales_person_id,
            'login_enabled' => $customer->login_enabled ? '1' : '0',
            'username' => $customer->username,
            'password' => null,
            'password_confirmation' => null,
            'primary_name' => 'Primary Contact',
            'primary_email' => 'primary@example.test',
            'contacts' => [],
        ], $overrides);
    }
}
