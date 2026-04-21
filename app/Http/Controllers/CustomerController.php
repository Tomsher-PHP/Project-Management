<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Industry;
use App\Services\CustomerServices;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends Controller
{

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Customer Management';
        $this->subTitle = 'Manage customer information and details';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $customers = Customer::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $industries = Industry::active()->get();

        return view('customers.index', compact('customers', 'perPage', 'industries'));
    }

    public function create()
    {
        $industries = Industry::active()->orderBy('sort_order', 'asc')->get();
        $parentIndustries = Industry::active()->whereNull('parent_id')->orderBy('sort_order', 'asc')->get();
        $nextIndustrySortOrder = ((int) Industry::max('sort_order')) + 1;
        $emirates = config('constants.emirates');

        // Generate customer code
        $customerCode = Customer::generateCustomerCode();

        return view('customers.create', compact('industries', 'parentIndustries', 'nextIndustrySortOrder', 'customerCode', 'emirates'));
    }

    public function store(CustomerRequest $request, CustomerServices $service)
    {
        $service->create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        $industries = Industry::active()->orderBy('sort_order', 'asc')->get();
        $parentIndustries = Industry::active()->whereNull('parent_id')->orderBy('sort_order', 'asc')->get();
        $nextIndustrySortOrder = ((int) Industry::max('sort_order')) + 1;
        $emirates = config('constants.emirates');

        // Generate customer code
        $customerCode = $customer->customer_code;

        return view('customers.edit', compact('customer', 'industries', 'parentIndustries', 'nextIndustrySortOrder', 'emirates', 'customerCode'));
    }

    public function update(CustomerRequest $request, Customer $customer, CustomerServices $service)
    {
        $service->update($customer, $request->validated());

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->contacts()->delete();
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $customer = Customer::findOrFail($request->id);
        $customer->is_active = !$customer->is_active;
        $customer->save();

        return response()->json([
            'success' => true,
            'is_active' => $customer->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
