<?php

namespace Database\Seeders;

use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusStageCodeSeeder extends Seeder
{
    /**
     * Convert existing alphabet or missing codes to unique 6-digit numeric codes
     * for project statuses and project stages.
     */
    public function run(): void
    {
        foreach ([ProjectStatus::class, ProjectStage::class] as $modelClass) {
            $modelClass::withTrashed()
                ->orderBy('id')
                ->each(function ($record) use ($modelClass) {
                    if (preg_match('/^\d{6}$/', (string) $record->code)) {
                        return;
                    }

                    $record->code = $modelClass::generateUniqueCode();
                    $record->save();
                });
        }
    }
}

