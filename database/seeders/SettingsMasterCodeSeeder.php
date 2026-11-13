<?php

namespace Database\Seeders;

use App\Models\BroadcastCategory;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Industry;
use App\Models\MeetingLocation;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use App\Models\Technology;
use Illuminate\Database\Seeder;

class SettingsMasterCodeSeeder extends Seeder
{
    /**
     * Settings master models that require a unique 6-digit code.
     *
     * @var array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected array $models = [
        Department::class,
        Designation::class,
        Technology::class,
        Industry::class,
        MeetingType::class,
        MeetingLocation::class,
        MeetingTag::class,
        BroadcastCategory::class,
    ];

    /**
     * Backfill or normalize unique 6-digit numeric codes for existing settings masters.
     */
    public function run(): void
    {
        foreach ($this->models as $modelClass) {
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

