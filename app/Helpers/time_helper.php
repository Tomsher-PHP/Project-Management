<?php

if (!function_exists('formatSecondsToHoursMinutes')) {

    function formatSecondsToHoursMinutes(int $seconds)
    {
        $seconds = (int) $seconds;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }
}

if (!function_exists('formatSecondsToHMS')) {

    function formatSecondsToHMS(int $seconds)
    {
        $totalSeconds = max(0, (int) ($seconds ?? 0));
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $remainingSeconds = $totalSeconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remainingSeconds);
        }

        return sprintf('%ds', $remainingSeconds);
    }
}

if (!function_exists('formatMinutesToHoursMinutes')) {
    function formatMinutesToHoursMinutes(int $minutes)
    {
        $minutes = (int) $minutes;

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%02dh : %02dm', $hours, $mins);
    }
}
