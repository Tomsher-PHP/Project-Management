<?php

namespace App\Providers;

use App\Models\Configuration;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\User;
use App\Models\UserGeneralSetting;
use App\Models\TaskTimeLog;
use App\Observers\ProjectSprintObserver;
use App\Observers\TaskObserver;
use App\Observers\TaskTimeLogObserver;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\View\Composers\SidebarComposer;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $dateFormat = config('constants.date_format');
        $timeFormat = config('constants.time_format');
        $timezone = config('app.timezone');
        $companyWebsite = null;
        $emailSuffix = '@gmail.com';

        if (Schema::hasTable('configurations')) {
            $selectColumns = ['date_format', 'time_format', 'timezone'];

            if (Schema::hasColumn('configurations', 'website')) {
                $selectColumns[] = 'website';
            }

            if (Schema::hasColumn('configurations', 'email_suffix')) {
                $selectColumns[] = 'email_suffix';
            }

            $configuration = Configuration::query()->select($selectColumns)->first();

            if (! empty($configuration?->date_format)) {
                $dateFormat = $configuration->date_format;
            }

            if (! empty($configuration?->time_format)) {
                $timeFormat = $configuration->time_format;
            }

            if (! empty($configuration?->timezone)) {
                $timezone = $configuration->timezone;
            }

            if (! empty($configuration?->website)) {
                $companyWebsite = $configuration->website;
            }

            if ($configuration && in_array('email_suffix', $selectColumns, true)) {
                $emailSuffix = ! empty($configuration->email_suffix)
                    ? $configuration->email_suffix
                    : $emailSuffix;
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
            'globalCompanyWebsite' => $companyWebsite,
            'globalEmailSuffix' => $emailSuffix,
        ]);

        View::composer('*', function ($view) {
            $userTheme = '';
            if (auth()->check()) {
                $userTheme = UserGeneralSetting::where('user_id', auth()->id())->value('theme') ?? '';
            }
            $view->with('userTheme', $userTheme);
        });

        View::composer('layouts.sidebar', SidebarComposer::class);

        Blade::directive('appDate', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppDate({$expression}); ?>";
        });

        Blade::directive('appTime', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppTime({$expression}); ?>";
        });

        Blade::directive('appDateTime', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::formatAppDateTime({$expression}); ?>";
        });

        Task::observe(TaskObserver::class);
        TaskTimeLog::observe(TaskTimeLogObserver::class);
        ProjectSprint::observe(ProjectSprintObserver::class);

        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });

        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
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

            $stringValue = trim((string) $value);

            if ($stringValue === '') {
                return null;
            }

            if (! self::hasTimeComponent($stringValue)) {
                return Carbon::parse($stringValue, $timezone)->timezone($timezone);
            }

            if (self::hasExplicitTimezone($stringValue)) {
                return Carbon::parse($stringValue)->timezone($timezone);
            }

            return Carbon::parse($stringValue, 'UTC')->timezone($timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function hasTimeComponent(string $value): bool
    {
        return preg_match('/\d{1,2}:\d{2}/', $value) === 1 || str_contains($value, 'T');
    }

    private static function hasExplicitTimezone(string $value): bool
    {
        return preg_match('/(?:[zZ]|[+\-]\d{2}:?\d{2})$/', $value) === 1;
    }
}
