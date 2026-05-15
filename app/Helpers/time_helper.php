<?php

if (!function_exists('formatSecondsToHoursMinutes')) {

    function formatSecondsToHoursMinutes($seconds)
    {
        $seconds = (int) $seconds;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    }
}

if (!function_exists('formatMinutesToHoursMinutes')) {
    function formatMinutesToHoursMinutes($minutes)
    {
        $minutes = (int) $minutes;

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%02dh : %02dm', $hours, $mins);
    }
}
