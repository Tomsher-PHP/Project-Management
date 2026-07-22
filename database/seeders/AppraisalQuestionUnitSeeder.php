<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppraisalQuestionUnit;

class AppraisalQuestionUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'Customers',
            'Leads',
            'Deals',
            'Meetings',
            'Calls',
            'Tickets',
            'Projects',
            'Campaigns',
            'Orders',
            'Hours',
            'Days',
            'Tasks',
            'Issues',
            'Bugs',
            'Features',
            'Modules',
            'Documents',
            'Invoices',
            'Payments',
            'Collections',
            'Visits',
            'Interviews',
            'Candidates',
            'Trainings',
            'Audits',
            'Branches',
            'Stores',
            'Shipments',
            'Deliveries',
            'Products',
            'Accounts',
            'Clients',
            'Vendors',
            'Partners',
            'Users',
            'Employees',
            'Complaints',
            'Incidents',
            'Resolutions',
            'AED',
            'USD',
            'EUR',
            '%',
        ];

        foreach ($units as $index => $unit) {
            AppraisalQuestionUnit::updateOrCreate(
                ['name' => $unit],
                [
                    'sort_order' => $index + 1,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
