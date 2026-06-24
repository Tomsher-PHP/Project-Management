<?php

use App\Services\Reports\DailyTimeReportService;
use Illuminate\Http\Request;

$request = Request::create('/reports/daily-time-report', 'GET', [
    'from_date' => now()->toDateString(),
    'to_date' => now()->toDateString(),
]);

$request->setUserResolver(function () {
    return App\Models\User::first();
});

$service = app(DailyTimeReportService::class);
$rows = \Closure::bind(function() use ($service, $request) {
    return $service->buildRows($request);
}, null, DailyTimeReportService::class)();

echo json_encode($rows, JSON_PRETTY_PRINT);
