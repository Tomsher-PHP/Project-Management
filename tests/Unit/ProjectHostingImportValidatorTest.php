<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProjectHostingImportValidator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProjectHostingImportValidatorTest extends TestCase
{
    protected ProjectHostingImportValidator $validator;
    protected User $admin;
    protected User $regularUser;
    protected array $masterData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ProjectHostingImportValidator();

        $this->admin = new User(['name' => 'Admin User']);
        $this->admin->id = 1;
        $this->admin->is_super_admin = true;

        $this->regularUser = new User(['name' => 'Regular User']);
        $this->regularUser->id = 2;
        $this->regularUser->is_super_admin = false;

        $accessibleProject = (object) [
            'id' => 10,
            'project_code' => 'PRJTEST01',
            'name' => 'Accessible Project',
            'is_system' => false,
            'added_by' => 2,
        ];

        $provider = (object) [
            'id' => 20,
            'name' => 'AWS Cloud',
            'code' => '00100001',
            'is_active' => true,
        ];

        $serviceType = (object) [
            'id' => 30,
            'name' => 'Dedicated Server',
            'code' => '00100004',
            'is_active' => true,
        ];

        $activeStatus = (object) [
            'id' => 40,
            'name' => 'Active',
            'code' => 'active',
            'is_active' => true,
            'is_default' => true,
        ];

        $suspendedStatus = (object) [
            'id' => 41,
            'name' => 'Suspended',
            'code' => 'suspended',
            'is_active' => true,
            'is_default' => false,
        ];

        $environment = (object) [
            'id' => 50,
            'name' => 'Production Live',
            'code' => 'production_live',
            'is_active' => true,
        ];

        $billingCycle = (object) [
            'id' => 60,
            'name' => 'Monthly Plan',
            'code' => 'monthly_plan',
            'is_active' => true,
        ];

        $this->masterData = $this->validator->buildMasterData(
            accessibleProjects: [$accessibleProject],
            providers: [$provider],
            services: [$serviceType],
            statuses: [$activeStatus, $suspendedStatus],
            environments: [$environment],
            billingCycles: [$billingCycle],
            defaultStatus: $activeStatus
        );
    }

    /**
     * Helper to create a test Excel spreadsheet file.
     */
    protected function createSpreadsheet(array $rows, string $sheetName = 'Hosting Upload', ?array $headers = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $headers = $headers ?? ProjectHostingImportValidator::EXPECTED_HEADERS;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }

        foreach ($rows as $rowIndex => $rowData) {
            $rowNum = $rowIndex + 2;
            foreach ($rowData as $col => $val) {
                if (in_array($col, ['C', 'D'], true) && $val !== null) {
                    $sheet->setCellValueExplicit($col . $rowNum, (string) $val, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($col . $rowNum, $val);
                }
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'test_val_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    public function test_valid_workbook_structure_and_row_passes_validation(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Primary Web Server',
                'C' => '00100001',
                'D' => '00100004',
                'E' => 'Active',
                'F' => 'company',
                'G' => 'example.com',
                'H' => 'Production Live',
                'I' => '2026-01-01',
                'J' => '2027-01-01',
                'K' => '2026-12-15',
                'L' => '2026-01-01',
                'M' => 'Monthly Plan',
                'N' => '250.00',
                'O' => 'USD',
                'P' => 'INV-1234',
                'Q' => 'Primary production hosting',
                'R' => 'Managed by DevOps team',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertTrue($result['is_valid_structure']);
        $this->assertNull($result['structure_error']);
        $this->assertEquals(1, $result['total_rows']);
        $this->assertEquals(1, $result['valid_rows_count']);
        $this->assertEquals(0, $result['invalid_rows_count']);
        $this->assertTrue($result['is_all_valid']);

        $row = $result['rows'][0];
        $this->assertEquals(2, $row['row_number']);
        $this->assertEquals('Valid', $row['status']);
        $this->assertEmpty($row['errors']);
        $this->assertEquals(10, $row['data']['project_id']);
        $this->assertEquals(20, $row['data']['hosting_provider_id']);
        $this->assertEquals(30, $row['data']['hosting_service_id']);
        $this->assertSame('00100001', $row['raw_values']['Provider']);
        $this->assertSame('00100004', $row['raw_values']['Service Type']);
        $this->assertEquals(40, $row['data']['hosting_service_status_id']);
        $this->assertEquals('2026-01-01', $row['data']['start_date']);
        $this->assertEquals('2027-01-01', $row['data']['expiry_date']);
        $this->assertEquals('250.00', $row['data']['billing_amount']);
        $this->assertEquals('USD', $row['data']['currency_code']);
    }

    public function test_missing_hosting_upload_worksheet_fails_structure(): void
    {
        $filePath = $this->createSpreadsheet([], 'Wrong Sheet Name');
        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertFalse($result['is_valid_structure']);
        $this->assertStringContainsString("The workbook must contain a 'Hosting Upload' worksheet.", $result['structure_error']);
    }

    public function test_invalid_header_fails_structure(): void
    {
        $badHeaders = ProjectHostingImportValidator::EXPECTED_HEADERS;
        $badHeaders['B'] = 'Wrong Column Name';

        $filePath = $this->createSpreadsheet([], 'Hosting Upload', $badHeaders);
        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertFalse($result['is_valid_structure']);
        $this->assertStringContainsString("Invalid column header at column B", $result['structure_error']);
    }

    public function test_blank_data_rows_are_ignored(): void
    {
        $filePath = $this->createSpreadsheet([
            ['A' => '   ', 'B' => null, 'C' => ''], // Blank row 2
            [
                'A' => 'PRJTEST01',
                'B' => 'Valid Server',
                'C' => '00100001',
                'D' => '00100004',
            ], // Row 3
            ['A' => null, 'B' => null], // Blank row 4
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertTrue($result['is_valid_structure']);
        $this->assertEquals(1, $result['total_rows']);
        $this->assertEquals(3, $result['rows'][0]['row_number']);
    }

    public function test_unknown_project_code_is_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJNONEXISTENT',
                'B' => 'Test Service',
                'C' => '00100001',
                'D' => '00100004',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(1, $result['total_rows']);
        $this->assertEquals('Invalid', $result['rows'][0]['status']);
        $this->assertContains('Project Code: No matching project was found.', $result['rows'][0]['errors']);
    }

    public function test_missing_required_service_name_is_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => '   ',
                'C' => '00100001',
                'D' => '00100004',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(1, $result['total_rows']);
        $this->assertContains('Service Name: Service Name is required.', $result['rows'][0]['errors']);
    }

    public function test_unknown_provider_and_service_type_are_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Test Server',
                'C' => '999998',
                'D' => '999999',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(1, $result['total_rows']);
        $this->assertContains('Provider code "999998" was not found.', $result['rows'][0]['errors']);
        $this->assertContains('Service Type code "999999" was not found.', $result['rows'][0]['errors']);
    }

    public function test_empty_provider_and_service_type_codes_keep_required_field_errors(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Missing Codes Server',
                'C' => '   ',
                'D' => null,
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertContains('Provider: Provider is required.', $result['rows'][0]['errors']);
        $this->assertContains('Service Type: Service Type is required.', $result['rows'][0]['errors']);
    }

    public function test_provider_and_service_type_names_are_not_resolved_as_codes(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Name Lookup Server',
                'C' => 'AWS Cloud',
                'D' => 'Dedicated Server',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertContains('Provider code "AWS Cloud" was not found.', $result['rows'][0]['errors']);
        $this->assertContains('Service Type code "Dedicated Server" was not found.', $result['rows'][0]['errors']);
    }

    public function test_provider_and_service_type_codes_trim_surrounding_whitespace(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Trimmed Codes Server',
                'C' => ' 00100001 ',
                'D' => "\t00100004\n",
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertSame('Valid', $result['rows'][0]['status']);
        $this->assertSame(20, $result['rows'][0]['data']['hosting_provider_id']);
        $this->assertSame(30, $result['rows'][0]['data']['hosting_service_id']);
    }

    public function test_blank_status_defaults_to_active_and_blank_ownership_defaults_to_company(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Defaulted Fields Server',
                'C' => '00100001',
                'D' => '00100004',
                'E' => null, // Blank status -> defaults to Active
                'F' => null, // Blank ownership -> defaults to company
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(1, $result['total_rows']);
        $this->assertEquals('Valid', $result['rows'][0]['status']);
        $this->assertEquals(40, $result['rows'][0]['data']['hosting_service_status_id']);
        $this->assertEquals('company', $result['rows'][0]['data']['ownership_type']);
    }

    public function test_invalid_ownership_type_is_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Test Server',
                'C' => '00100001',
                'D' => '00100004',
                'F' => 'third_party_vendor',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertContains("Ownership Type: Must be either 'company' or 'customer'.", $result['rows'][0]['errors']);
    }

    public function test_invalid_date_and_expiry_before_start_date_are_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Date Test Server',
                'C' => '00100001',
                'D' => '00100004',
                'I' => '2026-06-01',
                'J' => '2026-01-01', // Expiry before start date
                'K' => 'not-a-valid-date', // Invalid date string
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertContains('Expiry Date: Expiry Date must be on or after Start Date.', $result['rows'][0]['errors']);
        $this->assertContains('Next Renewal Date: The supplied date is invalid.', $result['rows'][0]['errors']);
    }

    public function test_non_numeric_and_negative_billing_amount_are_rejected(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Billing Test Server',
                'C' => '00100001',
                'D' => '00100004',
                'N' => 'FreeOfCost',
            ],
            [
                'A' => 'PRJTEST01',
                'B' => 'Negative Amount Server',
                'C' => '00100001',
                'D' => '00100004',
                'N' => '-50.00',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(2, $result['total_rows']);
        $this->assertContains('Billing Amount: The supplied billing amount must be a valid number.', $result['rows'][0]['errors']);
        $this->assertContains('Billing Amount: Billing Amount must be between 0 and 9,999,999,999.99.', $result['rows'][1]['errors']);
    }

    public function test_currency_code_must_be_three_letters(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Currency Test Server',
                'C' => '00100001',
                'D' => '00100004',
                'O' => 'US_DOLLAR',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertContains('Currency: Currency must be a 3-letter currency code (e.g. USD, AED).', $result['rows'][0]['errors']);
    }

    public function test_empty_sheet_reports_no_data_rows(): void
    {
        $filePath = $this->createSpreadsheet([]);
        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertTrue($result['is_valid_structure']);
        $this->assertEquals(0, $result['total_rows']);
        $this->assertFalse($result['is_all_valid']);
        $this->assertEmpty($result['rows']);
    }

    public function test_mixed_valid_and_invalid_rows_reported_accurately(): void
    {
        $filePath = $this->createSpreadsheet([
            [
                'A' => 'PRJTEST01',
                'B' => 'Valid Server',
                'C' => '00100001',
                'D' => '00100004',
            ],
            [
                'A' => 'PRJTEST01',
                'B' => 'Invalid Provider Server',
                'C' => '999999',
                'D' => '00100004',
            ],
        ]);

        $result = $this->validator->validate($filePath, $this->admin, $this->masterData);
        unlink($filePath);

        $this->assertEquals(2, $result['total_rows']);
        $this->assertEquals(1, $result['valid_rows_count']);
        $this->assertEquals(1, $result['invalid_rows_count']);
        $this->assertFalse($result['is_all_valid']);
        $this->assertEquals('Valid', $result['rows'][0]['status']);
        $this->assertEquals('Invalid', $result['rows'][1]['status']);
    }
}
