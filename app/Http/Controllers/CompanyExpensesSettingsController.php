<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\ExpensePaymentMode;
use App\Models\ExpenseServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CompanyExpensesSettingsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Company Expense Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $createPermission = 'company_expenses.create';
        $editPermission = 'company_expenses.edit';
        $deletePermission = 'company_expenses.delete';
        $togglePermission = 'company_expenses.edit';

        if ($request->routeIs('settings.expense-payment-modes.index')) {
            $records = ExpensePaymentMode::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) ExpensePaymentMode::max('sort_order')) + 1;
            $currentTab = 'payment-modes';
            $entityLabel = 'Payment Mode';
            $entityPluralLabel = 'Payment Modes';
            $storeRoute = route('settings.expense-payment-modes.store');
            $updateRouteName = 'settings.expense-payment-modes.update';
            $destroyRouteName = 'settings.expense-payment-modes.destroy';
            $toggleRoute = 'settings.company_expenses_payment_mode.toggleStatus';
        } elseif ($request->routeIs('settings.expense-service-providers.index')) {
            $records = ExpenseServiceProvider::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) ExpenseServiceProvider::max('sort_order')) + 1;
            $currentTab = 'service-providers';
            $entityLabel = 'Service Provider';
            $entityPluralLabel = 'Service Providers';
            $storeRoute = route('settings.expense-service-providers.store');
            $updateRouteName = 'settings.expense-service-providers.update';
            $destroyRouteName = 'settings.expense-service-providers.destroy';
            $toggleRoute = 'settings.company_expenses_service_provider.toggleStatus';
        } elseif ($request->routeIs('settings.expense-categories.index')) {
            $records = ExpenseCategory::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) ExpenseCategory::max('sort_order')) + 1;
            $currentTab = 'categories';
            $entityLabel = 'Expense Category';
            $entityPluralLabel = 'Expense Categories';
            $storeRoute = route('settings.expense-categories.store');
            $updateRouteName = 'settings.expense-categories.update';
            $destroyRouteName = 'settings.expense-categories.destroy';
            $toggleRoute = 'settings.company_expenses_category.toggleStatus';
        } else {
            abort(403);
        }

        return view('settings.company-expenses-settings.index', [
            'records' => $records,
            'perPage' => $perPage,
            'nextSortOrder' => $nextSortOrder,
            'currentTab' => $currentTab,
            'entityLabel' => $entityLabel,
            'entityPluralLabel' => $entityPluralLabel,
            'createPermission' => $createPermission,
            'editPermission' => $editPermission,
            'deletePermission' => $deletePermission,
            'togglePermission' => $togglePermission,
            'storeRoute' => $storeRoute,
            'updateRouteName' => $updateRouteName,
            'destroyRouteName' => $destroyRouteName,
            'toggleRoute' => $toggleRoute,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->routeIs('settings.expense-payment-modes.store')) {
            $data = app(\App\Http\Requests\ExpensePaymentModeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['sort_order'] = $data['sort_order'] ?? 1;
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpensePaymentMode::class);
                }

                return ExpensePaymentMode::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Payment mode created successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.expense-service-providers.store')) {
            $data = app(\App\Http\Requests\ExpenseServiceProviderRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['sort_order'] = $data['sort_order'] ?? 1;
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpenseServiceProvider::class);
                }

                return ExpenseServiceProvider::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Service provider created successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.expense-categories.store')) {
            $data = app(\App\Http\Requests\ExpenseCategoryRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['sort_order'] = $data['sort_order'] ?? 1;
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpenseCategory::class);
                }

                return ExpenseCategory::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Expense category created successfully.',
                'data' => $record,
            ]);
        }

        abort(403);
    }

    public function update(Request $request, $id)
    {
        if ($request->routeIs('settings.expense-payment-modes.update')) {
            $data = app(\App\Http\Requests\ExpensePaymentModeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = ExpensePaymentMode::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpensePaymentMode::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Payment mode updated successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.expense-service-providers.update')) {
            $data = app(\App\Http\Requests\ExpenseServiceProviderRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = ExpenseServiceProvider::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpenseServiceProvider::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Service provider updated successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.expense-categories.update')) {
            $data = app(\App\Http\Requests\ExpenseCategoryRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = ExpenseCategory::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(ExpenseCategory::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Expense category updated successfully.',
                'data' => $record,
            ]);
        }

        abort(403);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->routeIs('settings.expense-payment-modes.destroy')) {
            $record = ExpensePaymentMode::findOrFail($id);
            $routeName = 'settings.expense-payment-modes.index';
            $entityName = 'Payment mode';
        } elseif ($request->routeIs('settings.expense-service-providers.destroy')) {
            $record = ExpenseServiceProvider::findOrFail($id);
            $routeName = 'settings.expense-service-providers.index';
            $entityName = 'Service provider';
        } elseif ($request->routeIs('settings.expense-categories.destroy')) {
            $record = ExpenseCategory::findOrFail($id);
            $routeName = 'settings.expense-categories.index';
            $entityName = 'Expense category';
        } else {
            abort(403);
        }

        if ($record->is_system) {
            return redirect()
                ->route($routeName)
                ->with('error', "System {$entityName} cannot be deleted.");
        }

        $record->delete();

        return redirect()
            ->route($routeName)
            ->with('success', "{$entityName} deleted successfully.");
    }

    public function toggleStatusPaymentMode(Request $request)
    {
        $record = ExpensePaymentMode::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusServiceProvider(Request $request)
    {
        $record = ExpenseServiceProvider::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusCategory(Request $request)
    {
        $record = ExpenseCategory::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(string $modelClass, ?int $exceptId = null): void
    {
        $query = $modelClass::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
