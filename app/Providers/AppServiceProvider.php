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
        $dateFormat = config('constants.date_format');
        $timeFormat = config('constants.time_format');
        $timezone = config('app.timezone');

        if (Schema::hasTable('configurations')) {
            $configuration = Configuration::query()->select('date_format', 'time_format', 'timezone')->first();

            if (! empty($configuration?->date_format)) {
                $dateFormat = $configuration->date_format;
            }

            if (! empty($configuration?->time_format)) {
                $timeFormat = $configuration->time_format;
            }

            if (! empty($configuration?->timezone)) {
                $timezone = $configuration->timezone;
            }
        }

        config([
            'constants.date_format' => $dateFormat,
            'constants.time_format' => $timeFormat,
            'constants.timezone' => $timezone,
        ]);

        view()->share([
            'globalDateFormat' => $dateFormat,
            'globalTimeFormat' => $timeFormat,
            'globalTimezone' => $timezone,
        ]);

        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });
    }
}
