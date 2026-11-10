<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeCodeSeeder extends Seeder
{
    /**
     * Backfill or normalize unique prefixed codes for existing service types.
     */
    public function run(): void
    {
        $pattern = '/^' . preg_quote(ServiceType::CODE_PREFIX, '/') . '-\d{6}$/';

        ServiceType::withTrashed()
            ->orderBy('id')
            ->each(function (ServiceType $serviceType) use ($pattern) {
                if (preg_match($pattern, (string) $serviceType->code)) {
                    return;
                }

                $serviceType->code = ServiceType::generateUniqueCode();
                $serviceType->save();
            });
    }
}

