<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\UserLeaveBalanceImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserLeaveBalanceSampleExport;

class UserLeaveBalanceImportController extends Controller
{
    /**
     * Show the leave balance import page.
     */
    public function create(): View
    {
        return view('user_leave_balances.import', [
            'pageTitle' => 'Import Leave Balances',
            'subTitle' => 'Bulk import user leave balance details.',
        ]);
    }

    /**
     * Import leave balances from Excel/CSV.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ], [
            'file.required' => 'Please select an Excel or CSV file.',
            'file.mimes' => 'The file must be an XLSX, XLS or CSV file.',
            'file.max' => 'The file size cannot exceed 10 MB.',
        ]);

        $import = new UserLeaveBalanceImport();

        Excel::import(
            $import,
            $request->file('file')
        );

        /*
         * Store import result in session.
         */
        session()->flash(
            'leave_balance_import_result',
            [
                'created' => $import->created,
                'updated' => $import->updated,
                'failed' => $import->failed,
                'errors' => $import->errors,
            ]
        );

        if ($import->failed > 0) {
            return redirect()
                ->route('user-leave-balances.import')
                ->with(
                    'warning',
                    'Leave balance import completed with some errors.'
                );
        }

        return redirect()
            ->route('user-leave-balances.import')
            ->with(
                'success',
                'Leave balances imported successfully.'
            );
    }

    public function sample()
    {
        return Excel::download(
            new UserLeaveBalanceSampleExport(),
            'user_leave_balance_sample.xlsx'
        );
    }
}
