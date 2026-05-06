<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPayment;
use Illuminate\Support\Facades\DB;

class ProjectPaymentServices
{
    public function createPayment(Project $project, array $data): ProjectPayment
    {
        return DB::transaction(function () use ($project, $data) {
            return $project->projectPayments()->create([
                'amount' => $data['amount'] ?? null,
                'paid_date' => $data['paid_date'] ?? null,
                'coverage_start_date' => $data['coverage_start_date'],
                'coverage_end_date' => $data['coverage_end_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updatePayment(ProjectPayment $payment, array $data): ProjectPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'amount' => $data['amount'] ?? null,
                'paid_date' => $data['paid_date'] ?? null,
                'coverage_start_date' => $data['coverage_start_date'],
                'coverage_end_date' => $data['coverage_end_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            return $payment;
        });
    }

    public function getPaymentSummary(Project $project): array
    {
        $timezone = config('constants.timezone');
        $latestPayment = $project->projectPayments()
            ->orderByDesc('id')
            ->first();

        if (! $latestPayment) {
            return [
                'label' => 'Unpaid',
                'color' => '#EF4444',
                'description' => 'No payment recorded yet.',
                'coverage_start_date' => null,
                'coverage_end_date' => null,
                'amount' => null,
                'paid_date' => null,
            ];
        }

        $today = now(config('constants.timezone'))->startOfDay();
        $coverageStartDate = $latestPayment->coverage_start_date?->copy()->timezone($timezone)->startOfDay();
        $coverageEndDate = $latestPayment->coverage_end_date?->copy()->timezone($timezone)->startOfDay();

        if ($coverageStartDate && $coverageStartDate->gt($today)) {
            $label = 'Upcoming';
            $color = '#3B82F6';
            $description = 'Coverage is scheduled.';
        } elseif ($coverageEndDate && $coverageEndDate->lt($today)) {
            $label = 'Expired';
            $color = '#F59E0B';
            $description = 'Coverage has ended.';
        } else {
            $label = 'Paid';
            $color = '#22C55E';
            $description = 'Coverage is active.';
        }

        return [
            'label' => $label,
            'color' => $color,
            'description' => $description,
            'coverage_start_date' => $latestPayment->coverage_start_date,
            'coverage_end_date' => $latestPayment->coverage_end_date,
            'amount' => $latestPayment->amount !== null ? (float) $latestPayment->amount : null,
            'paid_date' => $latestPayment->paid_date,
        ];
    }
}
