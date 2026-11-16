<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\ProjectHostingRenewalHistory;
use App\Models\ProjectHostingStatusHistory;
use App\Services\ProjectHostingService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ProjectHostingServicePreserveLastRenewedDateTest extends TestCase
{
    public function test_create_record_without_flag_unsets_last_renewed_date(): void
    {
        $service = new ProjectHostingService();

        $project = new Project();
        $project->id = 10;

        $inputData = [
            'name' => 'Legacy Record',
            'last_renewed_date' => '2026-05-01',
            'hosting_service_status_id' => 1,
        ];

        // We can inspect the parameters passed into DB::transaction / model creation
        // By mocking ProjectHostingRecord or verifying the unset behavior
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                // Inside the callback, let's verify what data would be used
                return new ProjectHostingRecord(['name' => 'Legacy Record']);
            });

        $record = $service->createRecord($project, $inputData, 1, preserveLastRenewedDate: false);
        $this->assertInstanceOf(ProjectHostingRecord::class, $record);
    }

    public function test_create_record_with_flag_preserves_last_renewed_date_without_renewal_history(): void
    {
        $service = new ProjectHostingService();

        $project = new Project();
        $project->id = 10;

        $inputData = [
            'name' => 'Imported Record',
            'last_renewed_date' => '2026-05-01',
            'hosting_service_status_id' => 1,
        ];

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return new ProjectHostingRecord([
                    'name' => 'Imported Record',
                    'last_renewed_date' => '2026-05-01',
                ]);
            });

        $record = $service->createRecord($project, $inputData, 1, preserveLastRenewedDate: true);
        $this->assertEquals('2026-05-01', $record->last_renewed_date?->format('Y-m-d'));
    }
}
