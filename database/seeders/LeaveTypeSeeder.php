<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'description' => 'Annual vacation leave.',
                'is_file_upload_required' => false,
                'is_paid' => true,
                'status' => true,
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'Leave taken due to illness or medical reasons.',
                'is_file_upload_required' => true,
                'is_paid' => true,
                'status' => true,
                'color' => '#EF4444',
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'UNPAID',
                'description' => 'Leave taken without pay.',
                'is_file_upload_required' => false,
                'is_paid' => false,
                'status' => true,
                'color' => '#6B7280',
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EMERGENCY',
                'description' => 'Leave taken due to an unexpected emergency.',
                'is_file_upload_required' => false,
                'is_paid' => true,
                'status' => true,
                'color' => '#F97316',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['code' => $leaveType['code']],
                $leaveType
            );
        }
    }
}
