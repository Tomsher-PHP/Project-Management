<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserEmployeeInformationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $testRole;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'company_name' => 'Test Company',
            'email' => 'admin@example.com',
        ]);

        $this->testRole = Role::create([
            'name' => 'Employee',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'is_super_admin' => true,
        ]);
        $this->actingAs($this->adminUser);
    }

    public function test_can_create_user_without_new_employee_information(): void
    {
        $userData = [
            'name' => 'Standard User',
            'email' => 'standard@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'gender' => 'male',
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'standard@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->details);
        $this->assertNull($user->details->emergency_contacts);
        $this->assertNull($user->details->native_contacts);
        $this->assertNull($user->details->native_address);
        $this->assertNull($user->details->marital_status);
        $this->assertNull($user->details->children_count);
        $this->assertNull($user->details->favorite_color);
        $this->assertNull($user->details->shirt_sizes);
        $this->assertNull($user->details->shoe_sizes);
    }

    public function test_can_create_user_with_multiple_emergency_contacts(): void
    {
        $userData = [
            'name' => 'Emergency User',
            'email' => 'emergency@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                [
                    'name' => 'John Doe',
                    'number' => '+971501234567',
                    'relation' => 'Father',
                ],
                [
                    'name' => 'Jane Doe',
                    'number' => '0509876543',
                    'relation' => 'Mother',
                ],
            ],
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'emergency@example.com')->first();
        $this->assertNotNull($user);
        $this->assertIsArray($user->details->emergency_contacts);
        $this->assertCount(2, $user->details->emergency_contacts);
        $this->assertEquals('John Doe', $user->details->emergency_contacts[0]['name']);
        $this->assertEquals('+971501234567', $user->details->emergency_contacts[0]['number']);
        $this->assertEquals('Father', $user->details->emergency_contacts[0]['relation']);
        $this->assertEquals('Jane Doe', $user->details->emergency_contacts[1]['name']);

        // Backward compatibility verification
        $this->assertEquals('John Doe', $user->details->contact_person);
        $this->assertEquals('+971501234567', $user->details->contact_person_number);
    }

    public function test_can_create_user_with_multiple_native_contacts(): void
    {
        $userData = [
            'name' => 'Native User',
            'email' => 'native@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'native_contacts' => [
                [
                    'name' => 'David',
                    'number' => '+919876543210',
                    'relation' => 'Brother',
                ],
                [
                    'name' => 'Kumar',
                    'number' => '9847123456',
                    'relation' => 'Uncle',
                ],
            ],
            'native_address' => '123 Village Road, Kerala, India',
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'native@example.com')->first();
        $this->assertNotNull($user);
        $this->assertIsArray($user->details->native_contacts);
        $this->assertCount(2, $user->details->native_contacts);
        $this->assertEquals('David', $user->details->native_contacts[0]['name']);
        $this->assertEquals('+919876543210', $user->details->native_contacts[0]['number']);
        $this->assertEquals('123 Village Road, Kerala, India', $user->details->native_address);
    }

    public function test_can_edit_add_and_remove_contacts(): void
    {
        $userService = app(UserService::class);
        $user = $userService->createUser([
            'name' => 'Edit Contact User',
            'email' => 'editcontact@example.com',
            'password' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                ['name' => 'Contact 1', 'number' => '+11111111', 'relation' => 'Friend'],
                ['name' => 'Contact 2', 'number' => '+22222222', 'relation' => 'Colleague'],
            ],
            'native_contacts' => [
                ['name' => 'Native 1', 'number' => '+33333333', 'relation' => 'Cousin'],
            ],
        ]);

        // Update: remove Contact 2, update Contact 1, add Contact 3
        $updateData = [
            'name' => 'Edit Contact User Updated',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                ['name' => 'Contact 1 Updated', 'number' => '+11119999', 'relation' => 'Best Friend'],
                ['name' => 'Contact 3 Added', 'number' => '+33334444', 'relation' => 'Spouse'],
            ],
            'native_contacts' => [], // Removed all native contacts
        ];

        $response = $this->put(route('users.update', $user->id), $updateData);
        $response->assertRedirect();

        $user->refresh();
        $this->assertCount(2, $user->details->emergency_contacts);
        $this->assertEquals('Contact 1 Updated', $user->details->emergency_contacts[0]['name']);
        $this->assertEquals('+11119999', $user->details->emergency_contacts[0]['number']);
        $this->assertEquals('Contact 3 Added', $user->details->emergency_contacts[1]['name']);
        $this->assertNull($user->details->native_contacts);
    }

    public function test_save_and_retrieve_shirt_and_shoe_sizes(): void
    {
        $userData = [
            'name' => 'Sizes User',
            'email' => 'sizes@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'shirt_sizes' => [
                'casual' => 'L',
                'formal' => 'M',
            ],
            'shoe_sizes' => [
                'casual' => '42',
                'formal' => '43',
            ],
            'favorite_color' => 'Blue',
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'sizes@example.com')->first();
        $this->assertEquals('L', $user->details->shirt_sizes['casual']);
        $this->assertEquals('M', $user->details->shirt_sizes['formal']);
        $this->assertEquals('42', $user->details->shoe_sizes['casual']);
        $this->assertEquals('43', $user->details->shoe_sizes['formal']);
        $this->assertEquals('Blue', $user->details->favorite_color);
    }

    public function test_validate_contact_numbers(): void
    {
        // Invalid emergency contact number (contains letters)
        $userData = [
            'name' => 'Invalid Phone User',
            'email' => 'invalidphone@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                [
                    'name' => 'Bad Contact',
                    'number' => 'invalid-1234',
                    'relation' => 'Father',
                ],
            ],
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertSessionHasErrors(['emergency_contacts.0.number']);

        // Valid contact numbers (digits and optional leading +)
        $validData = [
            'name' => 'Valid Phone User',
            'email' => 'validphone@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                [
                    'name' => 'Good Contact',
                    'number' => '+971501234567',
                    'relation' => 'Father',
                ],
            ],
        ];

        $response = $this->post(route('users.store'), $validData);
        $response->assertSessionHasNoErrors();
    }

    public function test_rejects_unexpected_nested_fields(): void
    {
        $userData = [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                [
                    'name' => 'John',
                    'number' => '1234567',
                    'relation' => 'Friend',
                    'is_admin' => true, // Unexpected field
                ],
            ],
            'shirt_sizes' => [
                'casual' => 'L',
                'extra_size' => 'XXXL', // Unexpected field
            ],
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertSessionHasErrors(['emergency_contacts.0', 'shirt_sizes']);
    }

    public function test_validate_dropdown_values_against_constants(): void
    {
        $userData = [
            'name' => 'Invalid Dropdown User',
            'email' => 'invaliddropdown@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'marital_status' => 'NotAStatus',
            'favorite_color' => 'Transparent',
            'shirt_sizes' => [
                'casual' => 'SuperLarge',
                'formal' => 'UltraSmall',
            ],
            'shoe_sizes' => [
                'casual' => '99',
                'formal' => '12',
            ],
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertSessionHasErrors([
            'marital_status',
            'favorite_color',
            'shirt_sizes.casual',
            'shirt_sizes.formal',
            'shoe_sizes.casual',
            'shoe_sizes.formal',
        ]);
    }

    public function test_completely_empty_contact_rows_are_not_saved(): void
    {
        $userData = [
            'name' => 'Empty Rows User',
            'email' => 'emptyrows@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'emergency_contacts' => [
                ['name' => '', 'number' => '', 'relation' => ''],
                ['name' => '   ', 'number' => '', 'relation' => null],
            ],
            'native_contacts' => [
                ['name' => '', 'number' => '', 'relation' => ''],
            ],
        ];

        $response = $this->post(route('users.store'), $userData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'emptyrows@example.com')->first();
        $this->assertNull($user->details->emergency_contacts);
        $this->assertNull($user->details->native_contacts);
    }

    public function test_children_count_conditional_behavior_backend_enforcement(): void
    {
        // When marital status is Married, children count is saved
        $marriedData = [
            'name' => 'Married User',
            'email' => 'married@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'marital_status' => 'Married',
            'children_count' => 3,
        ];
        $this->post(route('users.store'), $marriedData);
        $user = User::where('email', 'married@example.com')->first();
        $this->assertEquals('Married', $user->details->marital_status);
        $this->assertEquals(3, $user->details->children_count);

        // When marital status is Single, children count is forced to null on backend even if submitted
        $singleData = [
            'name' => 'Single User',
            'email' => 'single@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$this->testRole->id],
            'marital_status' => 'Single',
            'children_count' => 2,
        ];
        $this->post(route('users.store'), $singleData);
        $singleUser = User::where('email', 'single@example.com')->first();
        $this->assertEquals('Single', $singleUser->details->marital_status);
        $this->assertNull($singleUser->details->children_count);

        // When updating a Married user to Divorced, children count is cleared
        $this->put(route('users.update', $user->id), [
            'name' => 'Married User Now Divorced',
            'roles' => [$this->testRole->id],
            'marital_status' => 'Divorced',
            'children_count' => 3,
        ]);
        $user->refresh();
        $this->assertEquals('Divorced', $user->details->marital_status);
        $this->assertNull($user->details->children_count);
    }

    public function test_old_emergency_contact_data_migration(): void
    {
        // Add back old columns temporarily to test the migration logic
        \Illuminate\Support\Facades\Schema::table('user_details', function (\Illuminate\Database\Schema\Blueprint $table) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('user_details', 'contact_person')) {
                $table->string('contact_person')->nullable();
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('user_details', 'contact_person_number')) {
                $table->string('contact_person_number')->nullable();
            }
        });

        // Create an existing user detail with legacy columns
        $legacyUser = User::factory()->create();
        $detailId = DB::table('user_details')->insertGetId([
            'user_id' => $legacyUser->id,
            'contact_person' => 'Old Contact Person',
            'contact_person_number' => '+971501112233',
            'gender' => 'female',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the idempotent migration logic: migrate data and delete old column data
        DB::table('user_details')
            ->where('id', $detailId)
            ->where(function ($query) {
                $query->whereNotNull('contact_person')
                    ->where('contact_person', '!=', '')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('contact_person_number')
                            ->where('contact_person_number', '!=', '');
                    });
            })
            ->orderBy('id')
            ->each(function ($row) {
                $name = trim($row->contact_person ?? '');
                $number = trim($row->contact_person_number ?? '');

                $updateData = [
                    'contact_person' => null,
                    'contact_person_number' => null,
                ];

                if (empty($row->emergency_contacts) || $row->emergency_contacts === '[]' || $row->emergency_contacts === 'null') {
                    if ($name !== '' || $number !== '') {
                        $contacts = [
                            [
                                'name' => $name !== '' ? $name : null,
                                'number' => $number !== '' ? $number : null,
                                'relation' => null,
                            ],
                        ];
                        $updateData['emergency_contacts'] = json_encode($contacts);
                    }
                }

                DB::table('user_details')
                    ->where('id', $row->id)
                    ->update($updateData);
            });

        // Drop old columns as done in migration
        \Illuminate\Support\Facades\Schema::table('user_details', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn(['contact_person', 'contact_person_number']);
        });

        $detail = UserDetail::find($detailId);
        $this->assertNotNull($detail->emergency_contacts);
        $this->assertCount(1, $detail->emergency_contacts);
        $this->assertEquals('Old Contact Person', $detail->emergency_contacts[0]['name']);
        $this->assertEquals('+971501112233', $detail->emergency_contacts[0]['number']);
        $this->assertNull($detail->emergency_contacts[0]['relation']);

        // Verify old columns were dropped
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('user_details', 'contact_person'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('user_details', 'contact_person_number'));

        // Verify model accessors still return the values seamlessly
        $this->assertEquals('Old Contact Person', $detail->contact_person);
        $this->assertEquals('+971501112233', $detail->contact_person_number);
    }

    public function test_create_and_edit_forms_render_new_sections_and_fields(): void
    {
        // Test Create form
        $createResponse = $this->get(route('users.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Address Information');
        $createResponse->assertSee('Residence Address');
        $createResponse->assertSee('Native Address');
        $createResponse->assertSee('Emergency Contact Information');
        $createResponse->assertSee('+ Add Emergency Contact');
        $createResponse->assertSee('Native Contact Information');
        $createResponse->assertSee('+ Add Native Contact');
        $createResponse->assertSee('Personal Information & Preferences', false);
        $createResponse->assertSee('Marital Status');
        $createResponse->assertSee('Children Count');
        $createResponse->assertSee('Favorite Color');
        $createResponse->assertSee('Casual Shirt Size');
        $createResponse->assertSee('Formal Shirt Size');
        $createResponse->assertSee('Casual Shoe Size');
        $createResponse->assertSee('Formal Shoe Size');

        // Test Edit form
        $userService = app(UserService::class);
        $user = $userService->createUser([
            'name' => 'Form Test User',
            'email' => 'formtest@example.com',
            'password' => 'Password123!',
            'roles' => [$this->testRole->id],
            'marital_status' => 'Married',
            'children_count' => 2,
            'favorite_color' => 'Green',
            'shirt_sizes' => ['casual' => 'XL', 'formal' => 'L'],
            'shoe_sizes' => ['casual' => '44', 'formal' => '45'],
            'emergency_contacts' => [
                ['name' => 'Alice Emergency', 'number' => '+971509998877', 'relation' => 'Sister'],
            ],
            'native_contacts' => [
                ['name' => 'Bob Native', 'number' => '+919998887776', 'relation' => 'Brother'],
            ],
        ]);

        $editResponse = $this->get(route('users.edit', $user->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Alice Emergency');
        $editResponse->assertSee('+971509998877');
        $editResponse->assertSee('Bob Native');
        $editResponse->assertSee('+919998887776');
        $editResponse->assertSee('Married');
        $editResponse->assertSee('Green');
    }
}

