<?php

namespace App\Http\Controllers;

use App\Mail\DailyWorkReportMail;
use App\Models\DailyWorkReportLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DailyWorkReportPreviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        // Retrieve the consolidated report belonging to the
        // currently authenticated user.
        $reportLog = DailyWorkReportLog::query()
            ->where('user_id', 4)
            ->whereDate('report_date', $validated['date'])
            ->latest('id')
            ->firstOrFail();

        // Render the actual email template using the same Mailable.
        $html = (new DailyWorkReportMail($reportLog))->render();

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
