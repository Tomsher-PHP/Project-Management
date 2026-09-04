<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * Get the leave balance record applicable for
     * the given user, leave type and date.
     */
    public function getApplicableBalance(
        User $user,
        int $leaveTypeId,
        Carbon|string $fromDate,
        Carbon|string $toDate
    ): ?UserLeaveBalance {
        $fromDate = $fromDate instanceof Carbon
            ? $fromDate->copy()
            : Carbon::parse($fromDate);

        $toDate = $toDate instanceof Carbon
            ? $toDate->copy()
            : Carbon::parse($toDate);

        return UserLeaveBalance::query()
            ->with('leaveType')
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', true)
            ->whereDate('valid_from', '<=', $fromDate->toDateString())
            ->whereDate('valid_to', '>=', $toDate->toDateString())
            ->first();
    }

    /**
     * Calculate leave duration.
     *
     * Full day:
     * 01 Sep -> 03 Sep = 3.00
     *
     * Half day:
     * 01 Sep -> 01 Sep = 0.50
     * 01 Sep -> 02 Sep = 1.00
     * 01 Sep -> 03 Sep = 1.50
     *
     * The selected half_day_type (first_half / second_half)
     * applies to every date in the selected range.
     */
    public function calculateDuration(
        string $type,
        Carbon|string $fromDate,
        Carbon|string $toDate
    ): float {
        $fromDate = $fromDate instanceof Carbon
            ? $fromDate->copy()->startOfDay()
            : Carbon::parse($fromDate)->startOfDay();

        $toDate = $toDate instanceof Carbon
            ? $toDate->copy()->startOfDay()
            : Carbon::parse($toDate)->startOfDay();

        /*
         * Inclusive number of calendar days.
         *
         * Example:
         * 04 Sep -> 09 Sep
         * = 6 days
         */
        $days = $fromDate->diffInDays($toDate) + 1;

        /*
         * Half day means 0.5 day for EACH date.
         */
        if ($type === 'half_day') {
            return round($days * 0.5, 2);
        }

        /*
         * Full day means 1 day for EACH date.
         */
        return (float) $days;
    }

    /**
     * Check request eligibility.
     *
     * IMPORTANT:
     * This method does not block submission.
     * It returns warnings that can be displayed to the user.
     */
    public function checkEligibility(
        User $user,
        int $leaveTypeId,
        string $type,
        Carbon|string $fromDate,
        Carbon|string $toDate
    ): array {
        $fromDate = $fromDate instanceof Carbon
            ? $fromDate->copy()
            : Carbon::parse($fromDate);

        $toDate = $toDate instanceof Carbon
            ? $toDate->copy()
            : Carbon::parse($toDate);

        $duration = $this->calculateDuration(
            $type,
            $fromDate,
            $toDate
        );

        $balance = $this->getApplicableBalance(
            $user,
            $leaveTypeId,
            $fromDate,
            $toDate
        );

        /*
         * No entitlement period covers these dates.
         */
        if (!$balance) {
            return [
                'eligible' => false,
                'has_balance_record' => false,
                'duration' => $duration,
                'current_balance' => 0,
                'yearly_entitlement' => 0,
                'monthly_entitlement' => 0,
                'message' => 'No active leave entitlement was found for the selected leave type and dates.',
            ];
        }

        $currentBalance = (float) $balance->current_balance;
        $yearlyEntitlement = (float) $balance->yearly_entitlement;
        $monthlyEntitlement = (float) $balance->monthly_entitlement;

        $warnings = [];

        /*
         * Check total remaining balance.
         */
        if ($duration > $currentBalance) {
            $warnings[] =
                'You have only ' .
                number_format($currentBalance, 2) .
                ' days remaining, but you are requesting ' .
                number_format($duration, 2) .
                ' days.';
        }

        /*
         * Check monthly entitlement.
         */
        $monthlyUsage = $this->getMonthlyUsage(
            $user,
            $balance,
            $fromDate,
            $toDate,
            $type
        );

        $monthlyExceeded = false;

        foreach ($monthlyUsage as $monthData) {
            if (
                $monthlyEntitlement > 0 &&
                (
                    $monthData['existing_days'] +
                    $monthData['requested_days']
                ) > $monthlyEntitlement
            ) {
                $monthlyExceeded = true;
                break;
            }
        }

        if ($monthlyExceeded) {
            $warnings[] =
                'The requested leave exceeds the monthly entitlement of ' .
                number_format($monthlyEntitlement, 2) .
                ' days for this leave type.';
        }

        return [
            'eligible' => empty($warnings),
            'has_balance_record' => true,

            'duration' => $duration,

            'current_balance' => $currentBalance,
            'yearly_entitlement' => $yearlyEntitlement,
            'monthly_entitlement' => $monthlyEntitlement,

            'leave_type_name' => $balance->leaveType->name ?? '',

            'valid_from' => $balance->valid_from?->format('Y-m-d'),
            'valid_to' => $balance->valid_to?->format('Y-m-d'),

            'warnings' => $warnings,

            'message' => empty($warnings)
                ? null
                : implode(' ', $warnings),
        ];
    }

    /**
     * Get existing leave usage for each month covered
     * by the request.
     *
     * Half-day leave is calculated as:
     *
     * Number of dates × 0.5
     *
     * Example:
     * 04 Sep -> 09 Sep = 3.00 days.
     */
    protected function getMonthlyUsage(
        User $user,
        UserLeaveBalance $balance,
        Carbon $fromDate,
        Carbon $toDate,
        string $requestType
    ): Collection {
        $months = collect();

        $cursor = $fromDate->copy()->startOfMonth();
        $lastMonth = $toDate->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {

            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            /*
             * ----------------------------------------------------------
             * Existing approved leave
             * ----------------------------------------------------------
             */
            $existingDays = \App\Models\LeaveRequest::query()
                ->where('user_id', $user->id)
                ->where('leave_type_id', $balance->leave_type_id)
                ->where('status', 'approved')
                ->whereDate(
                    'approved_from_date',
                    '<=',
                    $monthEnd->toDateString()
                )
                ->whereDate(
                    'approved_to_date',
                    '>=',
                    $monthStart->toDateString()
                )
                ->get()
                ->sum(function ($leaveRequest) use (
                    $monthStart,
                    $monthEnd
                ) {
                    if (
                        !$leaveRequest->approved_from_date ||
                        !$leaveRequest->approved_to_date
                    ) {
                        return 0;
                    }

                    $leaveFrom = Carbon::parse(
                        $leaveRequest->approved_from_date
                    )->startOfDay();

                    $leaveTo = Carbon::parse(
                        $leaveRequest->approved_to_date
                    )->startOfDay();

                    $overlapFrom = $leaveFrom->greaterThan($monthStart)
                        ? $leaveFrom
                        : $monthStart;

                    $overlapTo = $leaveTo->lessThan($monthEnd)
                        ? $leaveTo
                        : $monthEnd;

                    if ($overlapFrom->gt($overlapTo)) {
                        return 0;
                    }

                    /*
                     * Number of dates within this month.
                     */
                    $days = $overlapFrom->diffInDays($overlapTo) + 1;

                    /*
                     * Half day = 0.5 for every date.
                     */
                    if ($leaveRequest->type === 'half_day') {
                        return $days * 0.5;
                    }

                    /*
                     * Full day = 1 for every date.
                     */
                    return $days;
                });

            /*
             * ----------------------------------------------------------
             * Requested duration for the current month
             * ----------------------------------------------------------
             */
            $requestFrom = $fromDate->greaterThan($monthStart)
                ? $fromDate->copy()
                : $monthStart->copy();

            $requestTo = $toDate->lessThan($monthEnd)
                ? $toDate->copy()
                : $monthEnd->copy();

            $requestedDays = 0;

            if (!$requestFrom->gt($requestTo)) {

                /*
                 * Inclusive number of requested dates
                 * within this month.
                 */
                $days = $requestFrom->diffInDays($requestTo) + 1;

                /*
                 * Half day = 0.5 for EACH date.
                 */
                if ($requestType === 'half_day') {
                    $requestedDays = $days * 0.5;
                } else {
                    /*
                     * Full day = 1 for EACH date.
                     */
                    $requestedDays = $days;
                }
            }

            $months->push([
                'month' => $cursor->format('Y-m'),

                'existing_days' => round(
                    (float) $existingDays,
                    2
                ),

                'requested_days' => round(
                    (float) $requestedDays,
                    2
                ),
            ]);

            $cursor->addMonth();
        }

        return $months;
    }
}
