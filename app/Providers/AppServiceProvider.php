<?php

namespace App\Providers;

use App\Models\Configuration;
use App\Models\Task;
use App\Policies\TaskPolicy;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
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
            'app.timezone' => $timezone,
        ]);

        date_default_timezone_set($timezone);

        view()->share([
            'globalDateFormat' => $dateFormat,
            'globalTimeFormat' => $timeFormat,
            'globalTimezone' => $timezone,
        ]);

        Blade::directive('appDate', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppDate({$expression}); ?>";
        });

        Blade::directive('appTime', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppTime({$expression}); ?>";
        });

        Blade::directive('appDateTime', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppDateTime({$expression}); ?>";
        });

        Gate::policy(Task::class, TaskPolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });
    }

    public static function formatAppDate($value, string $fallback = '--'): string
    {
        if (empty($value)) {
            return $fallback;
        }

        return self::normalizeForAppTimezone($value)
            ?->format((string) config('constants.date_format'))
            ?? $fallback;
    }

    public static function formatAppTime($value, string $fallback = '--'): string
    {
        if (empty($value)) {
            return $fallback;
        }

        return self::normalizeForAppTimezone($value)
            ?->format((string) config('constants.time_format'))
            ?? $fallback;
    }

    public static function formatAppDateTime($value, string $fallback = '--'): string
    {
        if (empty($value)) {
            return $fallback;
        }

        $date = self::normalizeForAppTimezone($value);

        if (! $date) {
            return $fallback;
        }

        return $date->format(
            trim((string) config('constants.date_format') . ' ' . (string) config('constants.time_format'))
        );
    }

    private static function normalizeForAppTimezone($value): ?CarbonInterface
    {
        try {
            $timezone = (string) config('constants.timezone', config('app.timezone'));

            if ($value instanceof CarbonInterface) {
                return $value->copy()->timezone($timezone);
            }

            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->timezone($timezone);
            }

            return Carbon::parse($value)->timezone($timezone);
        } catch (\Throwable) {
            return null;
        }
    }
}
