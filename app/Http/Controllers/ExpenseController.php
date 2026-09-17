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
