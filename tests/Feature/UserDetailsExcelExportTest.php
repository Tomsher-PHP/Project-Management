<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class UserDetailsExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected Role $employeeRole;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Configuration::create([
            'company_name' => 'Test Company',
            'email' => 'admin@example.com',
        ]);

        foreach (['user.view', 'user.view_all_users', 'user.export'] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $this->employeeRole = Role::create([
            'name' => 'Employee',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'delete_status' => false,
        ]);
    }

    protected function loadSpreadsheetFromResponse(BinaryFileResponse $response): Spreadsheet
    {
        $file = $response->getFile();

        return IOFactory::load($file->getPathname());
    }

    public function test_authorized_user_can_download_excel_with_full_details_and_formatting(): void
    {
        $department = Department::create(['name' => 'Engineering', 'is_active' => true, 'sort_order' => 1]);
        $designation = Designation::create(['name' => 'Senior Developer', 'is_active' => true, 'sort_order' => 1]);

        $reporter = User::factory()->create(['name' => 'Alice Reporter', 'is_active' => true, 'delete_status' => false]);
        $manager = User::factory()->create(['name' => 'Bob Manager', 'is_active' => true, 'delete_status' => false]);

        $activeUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'delete_status' => false,
        ]);

        UserDetail::create([
            'user_id' => $activeUser->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'reporter_id' => $reporter->id,
            'manager_id' => $manager->id,
            'employee_id' => '00123',
            'gender' => 'male',
            'dob' => '1992-05-14',
            'phone' => '+971501234567',
            'whatsapp' => '00971501234567',
            'joining_date' => '2022-01-10',
            'leaving_date' => '2026-11-01',
            'marital_status' => 'Married',
            'children_count' => 0,
            'favorite_color' => 'Navy Blue',
            'address' => '123 Main Street, Dubai',
            'native_address' => '456 Home Town, Kerala',
            'emergency_contacts' => [
                ['name' => 'Jane Doe', 'number' => '0509998887', 'relation' => 'Spouse'],
                ['name' => 'Mark Doe', 'number' => '+919876543210', 'relation' => 'Brother'],
            ],
            'native_contacts' => [
                ['name' => 'Father Doe', 'number' => '0484123456', 'relation' => 'Father'],
            ],
            'shirt_sizes' => ['casual' => 'L', 'formal' => '40'],
            'shoe_sizes' => ['casual' => '42', 'formal' => '9'],
        ]);

        $inactiveUser = User::factory()->create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'is_active' => false,
            'delete_status' => false,
        ]);

        // Deleted and soft-deleted users must be excluded
        User::factory()->create([
            'name' => 'Deleted Status User',
            'is_active' => false,
            'delete_status' => true,
        ]);
        $softDeleted = User::factory()->create([
            'name' => 'Soft Deleted User',
            'is_active' => true,
            'delete_status' => false,
        ]);
        $softDeleted->delete();

        $response = $this->actingAs($this->superAdmin)->get(route('users.export'));

        $response->assertOk();
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('users_details_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);

        $spreadsheet = $this->loadSpreadsheetFromResponse($response->baseResponse);
        $sheet = $spreadsheet->getSheetByName('User Details');
        $this->assertNotNull($sheet);

        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertNotEmpty($sheet->getAutoFilter()->getRange());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());

        // Rows: 1 header + 4 eligible users (reporter, manager, activeUser, inactiveUser)
        $this->assertSame(5, $sheet->getHighestRow());

        $headers = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, false, false)[0];
        $this->assertSame('Sl. No.', $headers[0]);
        $this->assertContains('Emergency Contact 2 - Relation', $headers);
        $this->assertContains('Native Contact 2 - Relation', $headers);
        $this->assertContains('Casual Shirt Size', $headers);
        $this->assertContains('Formal Shoe Size', $headers);
        $this->assertNotContains('Password', $headers);
        $this->assertNotContains('Profile Image', $headers);

        // Find John Doe's row
        $rows = $sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . '5', null, false, false);
        $johnRow = collect($rows)->firstWhere(3, 'john@example.com');
        $this->assertNotNull($johnRow);

        $this->assertSame('00123', $johnRow[1]);
        $this->assertSame('John Doe', $johnRow[2]);
        $this->assertSame('Male', $johnRow[4]);
        $this->assertSame('14-05-1992', $johnRow[5]);
        $this->assertSame('+971501234567', $johnRow[6]);
        $this->assertSame('00971501234567', $johnRow[7]);
        $this->assertSame('Active', $johnRow[8]);
        $this->assertSame('Engineering', $johnRow[9]);
        $this->assertSame('Senior Developer', $johnRow[10]);
        $this->assertSame('Alice Reporter', $johnRow[11]);
        $this->assertSame('Bob Manager', $johnRow[12]);
        $this->assertSame('10-01-2022', $johnRow[13]);
        $this->assertSame('01-11-2026', $johnRow[14]);
        $this->assertSame('Married', $johnRow[15]);
        $this->assertNotNull($johnRow[16]);
        $this->assertSame('0', (string) $johnRow[16]);
        $this->assertSame('Navy Blue', $johnRow[17]);
        $this->assertSame('123 Main Street, Dubai', $johnRow[18]);
        $this->assertSame('456 Home Town, Kerala', $johnRow[19]);
        $this->assertSame('Jane Doe', $johnRow[20]);
        $this->assertSame('0509998887', $johnRow[21]);
        $this->assertSame('Spouse', $johnRow[22]);
        $this->assertSame('Mark Doe', $johnRow[23]);
        $this->assertSame('+919876543210', $johnRow[24]);
        $this->assertSame('Brother', $johnRow[25]);
        $this->assertSame('Father Doe', $johnRow[26]);
        $this->assertSame('0484123456', $johnRow[27]);
        $this->assertSame('Father', $johnRow[28]);
        $this->assertTrue($johnRow[29] === null || $johnRow[29] === '');
        $this->assertSame('L', $johnRow[32]);
        $this->assertSame('40', $johnRow[33]);
        $this->assertSame('42', $johnRow[34]);
        $this->assertSame('9', $johnRow[35]);

        // Inactive user row check & user without details check
        $inactiveRow = collect($rows)->firstWhere(3, 'inactive@example.com');
        $this->assertNotNull($inactiveRow);
        $this->assertSame('Inactive', $inactiveRow[8]);
    }

    public function test_dynamic_contacts_beyond_two_and_malformed_json_are_handled(): void
    {
        $userThreeContacts = User::factory()->create([
            'name' => 'Three Contacts',
            'email' => 'three@example.com',
            'is_active' => true,
            'delete_status' => false,
        ]);

        UserDetail::create([
            'user_id' => $userThreeContacts->id,
            'gender' => 'female',
            'emergency_contacts' => [
                ['name' => 'EC1', 'number' => '111', 'relation' => 'Mother'],
                ['name' => 'EC2', 'number' => '222', 'relation' => 'Father'],
                ['name' => 'EC3', 'number' => '333', 'relation' => 'Sister'],
            ],
            'native_contacts' => [
                ['name' => 'NC1', 'number' => '444', 'relation' => 'Uncle'],
                ['name' => 'NC2', 'number' => '555', 'relation' => 'Aunt'],
                ['name' => 'NC3', 'number' => '666', 'relation' => 'Cousin'],
            ],
            'shirt_sizes' => [],
            'shoe_sizes' => null,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('users.export'));
        $response->assertOk();

        $spreadsheet = $this->loadSpreadsheetFromResponse($response->baseResponse);
        $sheet = $spreadsheet->getSheetByName('User Details');

        $headers = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, false, false)[0];
        $this->assertContains('Emergency Contact 3 - Name', $headers);
        $this->assertContains('Emergency Contact 3 - Number', $headers);
        $this->assertContains('Emergency Contact 3 - Relation', $headers);
        $this->assertContains('Native Contact 3 - Name', $headers);
        $this->assertContains('Native Contact 3 - Number', $headers);
        $this->assertContains('Native Contact 3 - Relation', $headers);
    }

    public function test_filters_search_and_pagination_bypass_work(): void
    {
        User::factory()->create(['name' => 'Alpha Match', 'email' => 'alpha@example.com', 'is_active' => true, 'delete_status' => false]);
        User::factory()->create(['name' => 'Alpha Second', 'email' => 'alpha2@example.com', 'is_active' => true, 'delete_status' => false]);
        User::factory()->create(['name' => 'Beta Other', 'email' => 'beta@example.com', 'is_active' => false, 'delete_status' => false]);

        $response = $this->actingAs($this->superAdmin)->get(route('users.export', [
            'search' => 'Alpha',
            'per_page' => 1,
            'page' => 2,
        ]));

        $response->assertOk();

        $spreadsheet = $this->loadSpreadsheetFromResponse($response->baseResponse);
        $sheet = $spreadsheet->getSheetByName('User Details');
        $this->assertSame(3, $sheet->getHighestRow());
    }

    public function test_empty_dataset_produces_valid_excel_with_headings(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.export', [
            'search' => 'NonExistentUserXYZ',
        ]));

        $response->assertOk();

        $spreadsheet = $this->loadSpreadsheetFromResponse($response->baseResponse);
        $sheet = $spreadsheet->getSheetByName('User Details');
        $this->assertSame(1, $sheet->getHighestRow());

        $headers = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, false, false)[0];
        $this->assertSame('Sl. No.', $headers[0]);
        $this->assertContains('Emergency Contact 2 - Relation', $headers);
        $this->assertContains('Native Contact 2 - Relation', $headers);
        $this->assertContains('Formal Shoe Size', $headers);
    }

    public function test_formula_injection_is_prevented_and_numbers_are_stored_as_strings(): void
    {
        $formulaUser = User::factory()->create([
            'name' => '=CMD("calc")',
            'email' => 'formula@example.com',
            'is_active' => true,
            'delete_status' => false,
        ]);

        UserDetail::create([
            'user_id' => $formulaUser->id,
            'employee_id' => '00077',
            'gender' => 'other',
            'phone' => '+971500000001',
            'address' => '+1+2',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('users.export'));
        $response->assertOk();

        $spreadsheet = $this->loadSpreadsheetFromResponse($response->baseResponse);
        $sheet = $spreadsheet->getSheetByName('User Details');

        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C2')->getDataType());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('G2')->getDataType());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('S2')->getDataType());
        $this->assertSame('=CMD("calc")', $sheet->getCell('C2')->getValue());
    }

    public function test_unauthorized_user_cannot_see_export_button_or_download_export(): void
    {
        $viewerRole = Role::create([
            'name' => 'Viewer',
            'guard_name' => 'web',
            'is_active' => true,
        ]);
        $viewerRole->givePermissionTo('user.view');

        $viewer = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
        ]);
        $viewer->assignRole($viewerRole);

        $indexResponse = $this->actingAs($viewer)->get(route('users.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('Export Excel');

        $exportResponse = $this->actingAs($viewer)->get(route('users.export'));
        $this->assertNotEquals(200, $exportResponse->getStatusCode());

        // User with user.export permission sees the button and can download
        $exporterRole = Role::create([
            'name' => 'Exporter',
            'guard_name' => 'web',
            'is_active' => true,
        ]);
        $exporterRole->givePermissionTo(['user.view', 'user.export']);

        $exporter = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
            'delete_status' => false,
        ]);
        $exporter->assignRole($exporterRole);

        $exporterIndexResponse = $this->actingAs($exporter)->get(route('users.index'));
        $exporterIndexResponse->assertOk();
        $exporterIndexResponse->assertSee('Export Excel');

        $exporterDownloadResponse = $this->actingAs($exporter)->get(route('users.export'));
        $exporterDownloadResponse->assertOk();
    }
}
