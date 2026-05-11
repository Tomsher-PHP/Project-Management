<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Configuration::create([
            'company_name' => 'Company Name',
            'company_email' => 'hr@company.com',
            'company_phone' => '+971 000 0000',
            'company_address' => 'Company Address',
            'website' => 'https://company.com',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);
    }
}
