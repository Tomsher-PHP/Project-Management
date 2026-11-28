<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerPasswordReset;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPasswordResetCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_dry_run_and_deletion_respect_retention_and_active_challenges(): void
    {
        $customer = Customer::factory()->create([
            'is_active' => true,
            'login_enabled' => true,
            'username' => 'cleanup_cust',
            'email' => 'cleanup@example.com',
            'password' => Hash::make('SecretPass123!'),
        ]);

        // 1. Obsolete invalidated record older than 7 days -> SHOULD be deleted
        $oldInvalidated = $this->createResetRecord($customer, [
            'created_at' => now()->subDays(8),
            'expires_at' => now()->subDays(8)->addMinutes(5),
            'invalidated_at' => now()->subDays(8),
        ]);

        // 2. Obsolete used record older than 7 days -> SHOULD be deleted
        $oldUsed = $this->createResetRecord($customer, [
            'created_at' => now()->subDays(10),
            'expires_at' => now()->subDays(10)->addMinutes(5),
            'verified_at' => now()->subDays(10)->addMinute(),
            'reset_expires_at' => now()->subDays(10)->addMinutes(11),
            'used_at' => now()->subDays(10)->addMinutes(2),
        ]);

        // 3. Obsolete expired record older than 7 days -> SHOULD be deleted
        $oldExpired = $this->createResetRecord($customer, [
            'created_at' => now()->subDays(9),
            'expires_at' => now()->subDays(9)->addMinutes(5),
        ]);

        // 4. Record within 7-day retention (e.g. 1 hour ago for rate limiting) -> MUST be preserved
        $recentRecord = $this->createResetRecord($customer, [
            'created_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(25),
        ]);

        // 5. Active OTP challenge (even if created_at artificially old, expires_at is future) -> MUST be preserved
        $activeOtp = $this->createResetRecord($customer, [
            'created_at' => now()->subDays(8),
            'expires_at' => now()->addMinutes(5),
        ]);

        // 6. Active reset authorization (reset_expires_at is future) -> MUST be preserved
        $activeAuth = $this->createResetRecord($customer, [
            'created_at' => now()->subDays(8),
            'expires_at' => now()->subDays(8)->addMinutes(5),
            'verified_at' => now()->subMinute(),
            'reset_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'reset_expires_at' => now()->addMinutes(10),
        ]);

        // Dry run must preview 3 eligible records without deleting anything.
        $this->artisan('customer-portal:prune-password-resets --dry-run')
            ->expectsOutputToContain('Dry run: 3 obsolete customer password reset record(s) eligible for deletion.')
            ->assertExitCode(0);

        $this->assertSame(6, CustomerPasswordReset::query()->count());

        // Actual run deletes exactly the 3 obsolete records.
        $this->artisan('customer-portal:prune-password-resets')
            ->expectsOutputToContain('Deleted 3 obsolete customer password reset record(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('customer_password_resets', ['id' => $oldInvalidated->id]);
        $this->assertDatabaseMissing('customer_password_resets', ['id' => $oldUsed->id]);
        $this->assertDatabaseMissing('customer_password_resets', ['id' => $oldExpired->id]);

        $this->assertDatabaseHas('customer_password_resets', ['id' => $recentRecord->id]);
        $this->assertDatabaseHas('customer_password_resets', ['id' => $activeOtp->id]);
        $this->assertDatabaseHas('customer_password_resets', ['id' => $activeAuth->id]);
    }

    public function test_cleanup_command_is_registered_in_scheduler(): void
    {
        $events = collect(app(Schedule::class)->events());

        $matching = $events->first(function ($event) {
            return str_contains((string) $event->command, 'customer-portal:prune-password-resets');
        });

        $this->assertNotNull($matching);
        $this->assertSame('0 3 * * *', $matching->expression);
        $this->assertTrue($matching->withoutOverlapping);
    }

    private function createResetRecord(Customer $customer, array $overrides = []): CustomerPasswordReset
    {
        $record = new CustomerPasswordReset(array_merge([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'challenge_token_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'otp_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'attempt_count' => 0,
            'expires_at' => now()->addMinutes(5),
        ], $overrides));

        $record->timestamps = false;
        $record->created_at = $overrides['created_at'] ?? now();
        $record->updated_at = $overrides['created_at'] ?? now();
        $record->save();

        return $record;
    }
}
