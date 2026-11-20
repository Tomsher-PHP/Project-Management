<?php

namespace App\Services\Reports;

use App\Models\DailyWorkReportSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProjectReportSettingService
{
    public const REPORT_KEY = 'project_report';

    /**
     * List all Project Report configurations.
     */
    public function listConfigurations(): Collection
    {
        return DailyWorkReportSetting::query()
            ->where('report_key', self::REPORT_KEY)
            ->withCount('recipients')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Retrieve a single Project Report configuration.
     */
    public function getConfiguration(int|string $id): DailyWorkReportSetting
    {
        return DailyWorkReportSetting::query()
            ->where('report_key', self::REPORT_KEY)
            ->with('recipients')
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Create a new Project Report configuration.
     */
    public function createConfiguration(array $data, ?int $userId = null): DailyWorkReportSetting
    {
        $settingKey = $this->generateUniqueSettingKey($data['report_name'] ?? null);

        $frequency = strtolower((string) ($data['frequency'] ?? 'daily'));
        $weeklyDay = $frequency === 'weekly'
            ? (int) ($data['weekly_day'] ?? 1)
            : ($data['weekly_day'] ?? 1);

        $setting = new DailyWorkReportSetting();
        $setting->setting_key = $settingKey;
        $setting->report_key = self::REPORT_KEY;
        $setting->report_name = $data['report_name'];
        $setting->enabled = (bool) ($data['enabled'] ?? false);
        $setting->frequency = $frequency;
        $setting->delivery_time = Carbon::parse($data['delivery_time'])->format('H:i:s');
        $setting->timezone = $data['timezone'];
        $setting->reporting_period = $data['reporting_period'];
        $setting->weekly_day = $weeklyDay;
        $setting->settings = $this->buildSettingsPayload($data);
        $setting->updated_by = $userId;
        $setting->save();

        return $setting;
    }

    /**
     * Update an existing Project Report configuration.
     */
    public function updateConfiguration(DailyWorkReportSetting|int $setting, array $data, ?int $userId = null): DailyWorkReportSetting
    {
        $model = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        abort_if($model->report_key !== self::REPORT_KEY, 404, 'Configuration not found.');

        $frequency = strtolower((string) ($data['frequency'] ?? $model->frequency));
        $weeklyDay = $frequency === 'weekly'
            ? (int) ($data['weekly_day'] ?? $model->weekly_day ?? 1)
            : ($model->weekly_day ?? 1);

        $model->report_name = $data['report_name'] ?? $model->report_name;
        $model->enabled = isset($data['enabled']) ? (bool) $data['enabled'] : $model->enabled;
        $model->frequency = $frequency;

        if (!empty($data['delivery_time'])) {
            $model->delivery_time = Carbon::parse($data['delivery_time'])->format('H:i:s');
        }

        $model->timezone = $data['timezone'] ?? $model->timezone;
        $model->reporting_period = $data['reporting_period'] ?? $model->reporting_period;
        $model->weekly_day = $weeklyDay;
        $model->settings = $this->buildSettingsPayload($data, $model->settings ?? []);
        $model->updated_by = $userId;
        $model->save();

        return $model;
    }

    /**
     * Delete a Project Report configuration.
     */
    public function deleteConfiguration(DailyWorkReportSetting|int $setting): bool
    {
        $model = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        abort_if($model->report_key !== self::REPORT_KEY, 404, 'Configuration not found.');

        return (bool) $model->delete();
    }

    /**
     * Enable or disable a Project Report configuration.
     */
    public function toggleEnabled(DailyWorkReportSetting|int $setting, ?bool $enabled = null, ?int $userId = null): DailyWorkReportSetting
    {
        $model = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        abort_if($model->report_key !== self::REPORT_KEY, 404, 'Configuration not found.');

        $model->enabled = $enabled !== null ? $enabled : ! $model->enabled;
        $model->updated_by = $userId;
        $model->save();

        return $model;
    }

    /**
     * Generate a unique setting_key for each new configuration.
     */
    public function generateUniqueSettingKey(?string $reportName = null): string
    {
        $slug = $reportName ? Str::slug($reportName, '_') : '';
        $baseKey = $slug ? "project_report_{$slug}" : 'project_report';
        $baseKey = Str::limit($baseKey, 240, '');

        $key = $baseKey;
        $counter = 1;

        while (DailyWorkReportSetting::where('setting_key', $key)->exists()) {
            $key = "{$baseKey}_{$counter}";
            $counter++;
        }

        return $key;
    }

    /**
     * Build the structured settings JSON payload.
     */
    public function buildSettingsPayload(array $data, array $existingSettings = []): array
    {
        $frequency = strtolower((string) ($data['frequency'] ?? 'daily'));

        $monthlyDay = null;
        if ($frequency === 'monthly') {
            $rawMonthlyDay = $data['settings']['schedule']['monthly_day']
                ?? $data['monthly_day']
                ?? ($existingSettings['schedule']['monthly_day'] ?? null);

            $monthlyDay = $rawMonthlyDay === 'last' ? 'last' : (int) $rawMonthlyDay;
        }

        $statusIds = $data['settings']['filters']['project_status_ids']
            ?? $data['project_status_ids']
            ?? ($existingSettings['filters']['project_status_ids'] ?? []);

        $dateFilter = $data['settings']['filters']['date_filter']
            ?? $data['date_filter']
            ?? ($existingSettings['filters']['date_filter'] ?? 'none');

        $columns = $data['settings']['columns']
            ?? $data['columns']
            ?? ($existingSettings['columns'] ?? []);

        return [
            'schedule' => [
                'monthly_day' => $monthlyDay,
            ],
            'filters' => [
                'project_status_ids' => array_values(array_map('intval', (array) $statusIds)),
                'date_filter' => (string) $dateFilter,
            ],
            'columns' => array_values((array) $columns),
        ];
    }
}

