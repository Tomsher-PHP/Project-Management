<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Country;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'company_name' => 'Test Company',
            'email' => 'admin@example.com',
        ]);

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);
    }

    public function test_website_allows_www_without_http_and_rejects_invalid_urls(): void
    {
        $salesPerson = User::factory()->create(['is_active' => true, 'delete_status' => false]);

        $response = $this->actingAs($this->superAdmin)->post(route('customers.store'), [
            'name' => 'Acme Corp',
            'website' => 'www.acme-example.com',
            'sales_person_id' => $salesPerson->id,
            'primary_name' => 'John Contact',
            'primary_email' => 'john@acme-example.com',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Acme Corp',
            'website' => 'www.acme-example.com',
        ]);

        $invalidResponse = $this->actingAs($this->superAdmin)->post(route('customers.store'), [
            'name' => 'Bad URL Corp',
            'website' => 'not-a-valid-url',
            'sales_person_id' => $salesPerson->id,
            'primary_name' => 'Jane Contact',
            'primary_email' => 'jane@badurl.com',
        ]);

        $invalidResponse->assertSessionHasErrors('website');
    }

    public function test_registered_country_retains_selected_option_after_validation_error_on_create_and_edit(): void
    {
        $uae = Country::create(['name' => 'United Arab Emirates', 'code' => 'AE']);
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $salesPerson = User::factory()->create(['is_active' => true, 'delete_status' => false]);

        // Create: submit invalid form (missing name) with registered_country_id selected
        $createPost = $this->actingAs($this->superAdmin)
            ->from(route('customers.create'))
            ->followingRedirects()
            ->post(route('customers.store'), [
                'name' => '',
                'registered_country_id' => $india->id,
                'sales_person_id' => $salesPerson->id,
                'primary_name' => 'Contact Name',
                'primary_email' => 'contact@example.com',
            ]);

        $createPost->assertOk();
        $createPost->assertSee('<option value="' . $india->id . '" selected>', false);
        $createPost->assertSee('India');

        // Edit: create customer with UAE, then fail validation while changing country to India
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Existing Customer',
            'registered_country_id' => $uae->id,
            'sales_person_id' => $salesPerson->id,
            'is_active' => true,
        ]);

        $editPut = $this->actingAs($this->superAdmin)
            ->from(route('customers.edit', $customer->id))
            ->followingRedirects()
            ->put(route('customers.update', $customer->id), [
                'name' => '',
                'registered_country_id' => $india->id,
                'sales_person_id' => $salesPerson->id,
                'primary_name' => 'Contact Name',
                'primary_email' => 'contact2@example.com',
            ]);

        $editPut->assertOk();
        $editPut->assertSee('<option value="' . $india->id . '" selected>', false);
        $editPut->assertSee('India');
    }
}
