<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Requests\ExpenseUpdateRequest;
use App\Models\Expense;
use App\Services\ExpenseServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    protected ExpenseServices $expenseService;
    protected string $pageTitle;

    public function __construct(ExpenseServices $expenseService)
    {
        $this->expenseService = $expenseService;
        $this->pageTitle = 'Expenses';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $expenses = $this->expenseService->getPaginatedExpenses($request->all());
        $options = $this->expenseService->getFormOptions();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'data' => $expenses,
                'options' => $options,
            ]);
        }

        return view('expenses.index', array_merge([
            'expenses' => $expenses,
            'perPage' => $expenses->perPage(),
        ], $options));
    }

    public function store(ExpenseStoreRequest $request): JsonResponse|RedirectResponse
    {
        $expense = $this->expenseService->createExpense($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Expense recorded successfully.',
                'data' => $expense,
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): JsonResponse
    {
        $expense->load(['paymentMode', 'serviceProvider', 'category', 'vendor', 'customer']);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $expense->id,
                'payment_mode_id' => $expense->payment_mode_id,
                'payment_mode_name' => $expense->paymentMode?->name,
                'vat_payment' => $expense->vat_payment,
                'local_intl_payment' => $expense->local_intl_payment,
                'paid_date' => $expense->paid_date ? $expense->paid_date->format('Y-m-d') : null,
                'invoice_date' => $expense->invoice_date ? $expense->invoice_date->format('Y-m-d') : null,
                'other_currency' => $expense->other_currency,
                'other_amount' => $expense->other_amount,
                'payment_amount' => $expense->payment_amount,
                'vat_percentage' => $expense->vat_percentage,
                'vat_amount' => $expense->vat_amount,
                'bank_charges' => $expense->bank_charges,
                'invoice_number' => $expense->invoice_number,
                'service_provider_id' => $expense->service_provider_id,
                'service_provider_name' => $expense->serviceProvider?->name,
                'category_id' => $expense->category_id,
                'category_name' => $expense->category?->name,
                'vendor_id' => $expense->vendor_id,
                'vendor_name' => $expense->vendor?->name,
                'service_product' => $expense->service_product,
                'customer_id' => $expense->customer_id,
                'customer_name' => $expense->customer?->name,
                'comment' => $expense->comment,
            ],
        ]);
    }

    public function update(ExpenseUpdateRequest $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $updatedExpense = $this->expenseService->updateExpense($expense, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Expense updated successfully.',
                'data' => $updatedExpense,
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $this->expenseService->deleteExpense($expense);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Expense deleted successfully.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
