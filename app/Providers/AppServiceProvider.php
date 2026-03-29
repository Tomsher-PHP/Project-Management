<?php

namespace App\Providers;

use App\Models\Configuration;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('configurations')) {
            $configuration = Configuration::query()->select('date_format', 'time_format')->first();

            if (! empty($configuration?->date_format)) {
                config(['constants.date_format' => $configuration->date_format]);
            }

            if (! empty($configuration?->time_format)) {
                config(['constants.time_format' => $configuration->time_format]);
            }
        }

        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });
    }
}
