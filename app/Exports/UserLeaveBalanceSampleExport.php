<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserLeaveBalanceSampleExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UserLeaveBalanceUploadSheet(),
            new UserLeaveBalanceExamplesSheet(),
            new UserLeaveBalanceInstructionsSheet(),
            new UserLeaveBalanceColumnGuideSheet(),
        ];
    }
}
