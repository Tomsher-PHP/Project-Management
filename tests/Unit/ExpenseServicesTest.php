<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Services\ExpenseServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseServicesTest extends TestCase
{
    use RefreshDatabase;

    protected ExpenseServices $expenseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expenseService = new ExpenseServices();
    }

    public function test_create_expense_auto_calculates_vat_percentage(): void
    {
        $data = [
            'paid_date' => '2026-09-19',
            'payment_amount' => 100.00,
            'vat_amount' => 5.00,
            'vat_payment' => Expense::VAT_PAYMENT_VAT,
            'local_intl_payment' => Expense::LOCAL_INTL_LOCAL,
        ];

        $expense = $this->expenseService->createExpense($data);

        $this->assertEquals(5.00, (float) $expense->vat_percentage);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'vat_percentage' => 5.00,
        ]);
    }

    public function test_create_expense_with_zero_vat_amount_calculates_zero_percentage(): void
    {
        $data = [
            'paid_date' => '2026-09-19',
            'payment_amount' => 100.00,
            'vat_amount' => 0.00,
            'vat_payment' => Expense::VAT_PAYMENT_NO_VAT,
            'local_intl_payment' => Expense::LOCAL_INTL_LOCAL,
        ];

        $expense = $this->expenseService->createExpense($data);

        $this->assertEquals(0.00, (float) $expense->vat_percentage);
    }

    public function test_update_expense_recalculates_vat_percentage(): void
    {
        $expense = $this->expenseService->createExpense([
            'paid_date' => '2026-09-19',
            'payment_amount' => 100.00,
            'vat_amount' => 5.00,
            'vat_payment' => Expense::VAT_PAYMENT_VAT,
            'local_intl_payment' => Expense::LOCAL_INTL_LOCAL,
        ]);

        $updatedExpense = $this->expenseService->updateExpense($expense, [
            'paid_date' => '2026-09-19',
            'payment_amount' => 200.00,
            'vat_amount' => 10.00,
            'vat_payment' => Expense::VAT_PAYMENT_VAT,
            'local_intl_payment' => Expense::LOCAL_INTL_LOCAL,
        ]);

        $this->assertEquals(5.00, (float) $updatedExpense->vat_percentage);

        $updatedExpense2 = $this->expenseService->updateExpense($updatedExpense, [
            'paid_date' => '2026-09-19',
            'payment_amount' => 200.00,
            'vat_amount' => 14.00,
            'vat_payment' => Expense::VAT_PAYMENT_VAT,
            'local_intl_payment' => Expense::LOCAL_INTL_LOCAL,
        ]);

        $this->assertEquals(7.00, (float) $updatedExpense2->vat_percentage);
    }
}
