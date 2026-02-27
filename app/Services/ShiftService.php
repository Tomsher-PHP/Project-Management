<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createShifts(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {

            foreach ($data['start_time'] as $index => $startTime) {

                $user->shifts()->create(
                    $this->prepareShiftData($data, $index, $user->id)
                );
            }
        });
    }

    public function updateShifts(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {

            // Delete old shifts (clean + safe)
            $user->shifts()->delete();
            $user->workingDays()->delete();

            foreach ($data['start_time'] as $index => $startTime) {

                $user->shifts()->create(
                    $this->prepareShiftData($data, $index, $user->id)
                );
            }

            // Convert working_days → boolean columns
            $days = $this->mapWorkingDays($data['working_days']);
            $user->workingDays()->create($days);
        });
    }

    private function prepareShiftData(array $data, int $index, int $userId): array
    {
        // Convert break_duration (HH:MM) → seconds
        [$hour, $minute] = explode(':', $data['break_duration'][$index]);
        $breakSeconds = ($hour * 3600) + ($minute * 60);

        return [
            'user_id'        => $userId,
            'start_time'     => $data['start_time'][$index],
            'end_time'       => $data['end_time'][$index],
            'break_duration' => $breakSeconds,
        ];
    }

    private function mapWorkingDays(array $selectedDays): array
    {
        $allDays = [
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday'
        ];

        $mapped = [];

        foreach ($allDays as $day) {
            $mapped[$day] = in_array($day, $selectedDays) ? 1 : 0;
        }

        return $mapped;
    }
}
