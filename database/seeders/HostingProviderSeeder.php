<?php

namespace Database\Seeders;

use App\Models\HostingProvider;
use Illuminate\Database\Seeder;

class HostingProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'code' => '100001',
                'name' => 'Amazon Web Services (AWS)',
                'website' => 'https://aws.amazon.com',
            ],
            [
                'code' => '100002',
                'name' => 'Microsoft Azure',
                'website' => 'https://azure.microsoft.com',
            ],
            [
                'code' => '100003',
                'name' => 'Google Cloud',
                'website' => 'https://cloud.google.com',
            ],
            [
                'code' => '100004',
                'name' => 'DigitalOcean',
                'website' => 'https://www.digitalocean.com',
            ],
            [
                'code' => '100005',
                'name' => 'Cloudways',
                'website' => 'https://www.cloudways.com',
            ],
            [
                'code' => '100006',
                'name' => 'GoDaddy',
                'website' => 'https://www.godaddy.com',
            ],
            [
                'code' => '100007',
                'name' => 'Namecheap',
                'website' => 'https://www.namecheap.com',
            ],
            [
                'code' => '100008',
                'name' => 'Hostinger',
                'website' => 'https://www.hostinger.com',
            ],
            [
                'code' => '100009',
                'name' => 'SiteGround',
                'website' => 'https://www.siteground.com',
            ],
        ];

        foreach ($providers as $index => $provider) {
            HostingProvider::firstOrCreate(
                ['code' => $provider['code']],
                [
                    'name' => $provider['name'],
                    'website' => $provider['website'],
                    'description' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'is_default' => false,
                ]
            );
        }
    }
}
