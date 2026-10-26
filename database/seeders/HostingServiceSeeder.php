<?php

namespace Database\Seeders;

use App\Models\HostingService;
use Illuminate\Database\Seeder;

class HostingServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'code' => '100001',
                'name' => 'Shared Hosting',
                'description' => 'Website hosting on a shared server.',
            ],
            [
                'code' => '100002',
                'name' => 'VPS Hosting',
                'description' => 'Virtual private server hosting.',
            ],
            [
                'code' => '100003',
                'name' => 'Dedicated Server',
                'description' => 'Hosting on a dedicated physical server.',
            ],
            [
                'code' => '100004',
                'name' => 'Cloud Hosting',
                'description' => 'Hosting using cloud infrastructure.',
            ],
            [
                'code' => '100005',
                'name' => 'Managed Hosting',
                'description' => 'Hosting with server administration and maintenance.',
            ],
            [
                'code' => '100006',
                'name' => 'WordPress Hosting',
                'description' => 'Hosting optimized for WordPress websites.',
            ],
            [
                'code' => '100007',
                'name' => 'Domain Registration',
                'description' => 'Domain registration and renewal services.',
            ],
            [
                'code' => '100008',
                'name' => 'DNS Management',
                'description' => 'Domain name system configuration and management.',
            ],
            [
                'code' => '100009',
                'name' => 'SSL Certificate',
                'description' => 'SSL/TLS certificate provisioning and renewal.',
            ],
            [
                'code' => '100010',
                'name' => 'Email Hosting',
                'description' => 'Business email hosting and mailbox management.',
            ],
            [
                'code' => '100011',
                'name' => 'Server Management',
                'description' => 'Server configuration, monitoring, updates, and maintenance.',
            ],
            [
                'code' => '100012',
                'name' => 'Database Hosting',
                'description' => 'Hosting and managing database servers.',
            ],
            [
                'code' => '100013',
                'name' => 'Backup & Recovery',
                'description' => 'Backup, restoration, and disaster recovery services.',
            ],
            [
                'code' => '100014',
                'name' => 'CDN',
                'description' => 'Content delivery network services.',
            ],
        ];

        foreach ($services as $index => $service) {
            HostingService::firstOrCreate(
                ['code' => $service['code']],
                [
                    'name' => $service['name'],
                    'description' => $service['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'is_default' => false,
                ]
            );
        }
    }
}
