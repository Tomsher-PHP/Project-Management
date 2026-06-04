<?php

namespace App\Services;

use App\Models\Configuration;

class CompanyService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function timezone(): string
    {
        return cache()->remember('company_timezone', 3600, function () {
            return Configuration::find(1)?->timezone
                ?? config('app.timezone');
        });
    }
}
