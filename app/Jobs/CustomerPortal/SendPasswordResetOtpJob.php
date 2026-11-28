<?php

namespace App\Jobs\CustomerPortal;

use App\Mail\CustomerPortal\PasswordResetOtpMail;
use App\Models\CustomerPasswordReset;
use App\Services\CustomerPortal\Auth\CustomerOtpService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetOtpJob implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $passwordResetId,
        private readonly string $otp,
    ) {}

    public function handle(CustomerOtpService $otpService): void
    {
        $reset = CustomerPasswordReset::query()
            ->with('customer')
            ->find($this->passwordResetId);

        if (! $reset) {
            return;
        }

        if (
            $reset->invalidated_at !== null ||
            $reset->used_at !== null ||
            $reset->verified_at !== null ||
            $reset->expires_at->isPast()
        ) {
            return;
        }

        $customer = $reset->customer;

        if (! $otpService->isEligibleForPasswordReset($customer)) {
            return;
        }

        // Ensure the customer's registered company email has not changed since the challenge was created.
        if (strtolower(trim((string) $customer->email)) !== strtolower(trim((string) $reset->email))) {
            return;
        }

        $expiryMinutes = (int) config('customer_portal.password_reset.otp_expiry_minutes', 5);

        Mail::to(trim((string) $customer->email))->send(
            new PasswordResetOtpMail(
                otp: $this->otp,
                companyName: (string) $customer->name,
                expiryMinutes: $expiryMinutes,
            )
        );
    }
}
