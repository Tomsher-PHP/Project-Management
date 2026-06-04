<?php

namespace App\Http\Controllers;

use App\Services\CustomerRestoreService;
use Illuminate\Http\Request;

class CustomerRestoreController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Restore Customers';
        $this->subTitle = 'Review deleted customers and restore them when there is no active email conflict';

        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function restoreIndex(Request $request, CustomerRestoreService $customerRestoreService)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $customers = $customerRestoreService->getDeletedCustomers($perPage);

        return view('customers.restore.index', compact('customers', 'perPage'));
    }

    public function restore($id, CustomerRestoreService $customerRestoreService)
    {
        $customer = $customerRestoreService->findDeletedCustomerOrFail($id);

        if (! $customerRestoreService->restoreDeletedCustomer($customer)) {
            return redirect()
                ->back()
                ->with('error', 'This customer cannot be restored because the email is already used by an active customer.');
        }

        return redirect()
            ->back()
            ->with('success', 'Customer restored successfully.');
    }

    public function bulkRestore(Request $request, CustomerRestoreService $customerRestoreService)
    {
        $validated = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer'],
        ]);

        $result = $customerRestoreService->bulkRestoreCustomers($validated['customer_ids']);

        if ($result['selected_count'] === 0) {
            return redirect()
                ->back()
                ->with('error', 'No deleted customers were selected for restore.');
        }

        if ($result['restored_count'] === 0) {
            return redirect()
                ->back()
                ->with('error', 'Selected customers could not be restored because their emails are already used by active customers.');
        }

        $response = redirect()
            ->back()
            ->with('success', $result['restored_count'] . ' customer(s) restored successfully.');

        if ($result['conflicting_count'] > 0) {
            $response->with('warning', $result['conflicting_count'] . ' customer(s) were skipped because their emails are already used by active customers.');
        }

        return $response;
    }
}
