<?php

namespace App\Services\Reports;

use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
        $setting->enabled = isset($data['enabled']) ? (bool) $data['enabled'] : true;
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
     * Retrieve recipients for a Project Report configuration.
     */
    public function getRecipients(DailyWorkReportSetting|int $setting): Collection
    {
        $settingId = $setting instanceof DailyWorkReportSetting ? $setting->id : (int) $setting;

        return DailyWorkReportRecipient::query()
            ->where('report_key', self::REPORT_KEY)
            ->where('report_setting_id', $settingId)
            ->with('user:id,name,email')
            ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->orderBy('email')
            ->get();
    }

    /**
     * Atomically sync internal and external recipients for a Project Report configuration.
     * Preserves unchanged recipient IDs and created_at timestamps.
     */
    public function syncRecipients(
        DailyWorkReportSetting|int $setting,
        array $userIds = [],
        array $externalRecipients = [],
        ?int $authUserId = null
    ): Collection {
        $settingModel = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        abort_if($settingModel->report_key !== self::REPORT_KEY, 404, 'Configuration not found.');
        $settingId = $settingModel->id;

        return DB::transaction(function () use ($settingId, $userIds, $externalRecipients, $authUserId) {
            // Existing recipients for this specific report configuration
            $existingRecipients = DailyWorkReportRecipient::query()
                ->where('report_key', self::REPORT_KEY)
                ->where('report_setting_id', $settingId)
                ->get();

            $existingByUserId = $existingRecipients->whereNotNull('user_id')->keyBy('user_id');
            $existingByEmail = $existingRecipients->whereNull('user_id')->keyBy(function ($r) {
                return mb_strtolower(trim($r->email));
            });

            $keptRecipientIds = [];

            // 1. Process Internal PMS Users
            $cleanUserIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
            $internalUsers = !empty($cleanUserIds)
                ? User::query()->whereIn('id', $cleanUserIds)->where('is_active', true)->whereNotNull('email')->get()
                : collect();

            $internalNormalizedEmails = [];

            foreach ($internalUsers as $user) {
                $normalizedEmail = mb_strtolower(trim($user->email));
                $internalNormalizedEmails[] = $normalizedEmail;

                if ($existing = $existingByUserId->get($user->id)) {
                    // Update existing record to preserve ID and timestamps
                    $existing->update([
                        'name' => $user->name,
                        'email' => $normalizedEmail,
                        'scope' => null,
                        'scope_id' => null,
                        'updated_by' => $authUserId,
                    ]);
                    $keptRecipientIds[] = $existing->id;
                } else {
                    $newRecipient = DailyWorkReportRecipient::create([
                        'report_key' => self::REPORT_KEY,
                        'report_setting_id' => $settingId,
                        'user_id' => $user->id,
                        'scope' => null,
                        'scope_id' => null,
                        'name' => $user->name,
                        'email' => $normalizedEmail,
                        'is_active' => true,
                        'created_by' => $authUserId,
                        'updated_by' => $authUserId,
                    ]);
                    $keptRecipientIds[] = $newRecipient->id;
                }
            }

            // 2. Process External Recipients
            $seenExternalEmails = [];

            foreach ($externalRecipients as $item) {
                $email = mb_strtolower(trim($item['email'] ?? ''));
                if ($email === '' || in_array($email, $internalNormalizedEmails, true) || in_array($email, $seenExternalEmails, true)) {
                    continue; // Skip invalid, collision with internal, or duplicate external
                }
                $seenExternalEmails[] = $email;

                $name = trim((string) ($item['name'] ?? '')) ?: null;

                if ($existing = $existingByEmail->get($email)) {
                    $existing->update([
                        'name' => $name,
                        'scope' => null,
                        'scope_id' => null,
                        'updated_by' => $authUserId,
                    ]);
                    $keptRecipientIds[] = $existing->id;
                } else {
                    $newRecipient = DailyWorkReportRecipient::create([
                        'report_key' => self::REPORT_KEY,
                        'report_setting_id' => $settingId,
                        'user_id' => null,
                        'scope' => null,
                        'scope_id' => null,
                        'name' => $name,
                        'email' => $email,
                        'is_active' => true,
                        'created_by' => $authUserId,
                        'updated_by' => $authUserId,
                    ]);
                    $keptRecipientIds[] = $newRecipient->id;
                }
            }

            // 3. Remove recipients no longer in the list for this setting
            DailyWorkReportRecipient::query()
                ->where('report_key', self::REPORT_KEY)
                ->where('report_setting_id', $settingId)
                ->whereNotIn('id', $keptRecipientIds)
                ->delete();

            return $this->getRecipients($settingId);
        });
    }

    /**
     * Delete a single recipient strictly scoped to a Project Report configuration.
     */
    public function deleteRecipient(DailyWorkReportSetting|int $setting, int $recipientId): bool
    {
        $settingModel = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        $recipient = DailyWorkReportRecipient::query()
            ->where('report_key', self::REPORT_KEY)
            ->where('report_setting_id', $settingModel->id)
            ->where('id', $recipientId)
            ->firstOrFail();

        return (bool) $recipient->delete();
    }

    /**
     * Toggle the active status of a recipient strictly scoped to a Project Report configuration.
     */
    public function toggleRecipient(DailyWorkReportSetting|int $setting, int $recipientId, ?int $authUserId = null): DailyWorkReportRecipient
    {
        $settingModel = $setting instanceof DailyWorkReportSetting
            ? $setting
            : $this->getConfiguration($setting);

        $recipient = DailyWorkReportRecipient::query()
            ->where('report_key', self::REPORT_KEY)
            ->where('report_setting_id', $settingModel->id)
            ->where('id', $recipientId)
            ->firstOrFail();

        $recipient->is_active = ! $recipient->is_active;
        $recipient->updated_by = $authUserId;
        $recipient->save();

        return $recipient;
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
            ?? ($existingSettings['filters']['date_filter'] ?? 'status_changed_date');

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

