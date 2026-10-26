<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectNotificationDefault;
use App\Models\ProjectNotificationPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateProjectNotificationPreferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:migrate-notification-preferences {--force : Overwrite existing preference records}';

    // run the command: php artisan project:migrate-notification-preferences
    // run to change existing : php artisan project:migrate-notification-preferences --force
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate project notification preferences for existing projects based on defaults.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting project notification preferences migration...');

        // 1. Load notification types from config/project_constants.php
        $notificationTypes = config('project_constants.project_notification_types', []);

        if (empty($notificationTypes)) {
            $this->error('No project notification types configured in config/project_constants.php.');
            return self::FAILURE;
        }

        // 2. Load all default records grouped by setting_key
        $allDefaults = ProjectNotificationDefault::all();

        $this->line(sprintf('Loaded %d configured notification types and %d default rules.', count($notificationTypes), $allDefaults->count()));

        // 3. Process all projects
        $projects = Project::all();
        $totalProjects = $projects->count();
        $this->line(sprintf('Processing %d active projects...', $totalProjects));

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $force = (bool) $this->option('force');

        $bar = $this->output->createProgressBar($totalProjects);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($projects as $project) {
                foreach (array_keys($notificationTypes) as $typeKey) {
                    $isEnabled = $this->resolveDefaultIsEnabled($project, $typeKey, $allDefaults);

                    $preference = ProjectNotificationPreference::where('project_id', $project->id)
                        ->where('notification_type', $typeKey)
                        ->first();

                    if (!$preference) {
                        ProjectNotificationPreference::create([
                            'project_id' => $project->id,
                            'notification_type' => $typeKey,
                            'is_enabled' => $isEnabled,
                        ]);
                        $createdCount++;
                    } elseif ($force) {
                        $preference->update([
                            'is_enabled' => $isEnabled,
                        ]);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $this->info('Project notification preferences migration completed successfully.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Projects Processed', $totalProjects],
                    ['Preferences Created', $createdCount],
                    ['Preferences Updated (--force)', $updatedCount],
                    ['Preferences Kept Unchanged', $skippedCount],
                    ['Total Preference Records', ProjectNotificationPreference::count()],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Failed to migrate notification preferences: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Resolve default is_enabled value for a project and notification type.
     */
    protected function resolveDefaultIsEnabled(Project $project, string $typeKey, $allDefaults): bool
    {
        $matchingDefaults = $allDefaults->where('setting_key', $typeKey);

        // 1. Try to match specific condition (e.g. project_flow)
        foreach ($matchingDefaults as $defaultRecord) {
            $conditions = $defaultRecord->conditions ?? [];

            if (!empty($conditions) && is_array($conditions)) {
                $matches = true;

                foreach ($conditions as $attr => $expectedVal) {
                    $actualVal = $project->{$attr} ?? null;

                    if ($actualVal !== $expectedVal) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    return (bool) ($defaultRecord->setting_value['is_enabled'] ?? false);
                }
            }
        }

        // 2. If no condition matched or conditions were empty, check empty conditions (fallback/general)
        $fallback = $matchingDefaults->first(function ($defaultRecord) {
            $conditions = $defaultRecord->conditions ?? [];
            return empty($conditions);
        });

        if ($fallback) {
            return (bool) ($fallback->setting_value['is_enabled'] ?? false);
        }

        // Default to true if no rule found
        return true;
    }
}
