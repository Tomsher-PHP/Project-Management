<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeCodeSeeder extends Seeder
{
    /**
     * Backfill or normalize unique 6-digit numeric codes for existing service types.
     */
    public function run(): void
    {
        ServiceType::withTrashed()
            ->orderBy('id')
            ->each(function (ServiceType $serviceType) {
                if (preg_match('/^\d{6}$/', (string) $serviceType->code)) {
                    return;
                }

                $serviceType->code = ServiceType::generateUniqueCode();
                $serviceType->save();
            });
    }
}

