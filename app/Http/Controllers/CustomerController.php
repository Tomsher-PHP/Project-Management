<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Industry;
use App\Services\CustomerServices;
use Illuminate\Http\Request;

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
        $industries = Industry::active()->orderBy('order', 'asc')->get();
        $emirates = config('constants.emirates');

        // Generate customer code
        $customerCode = Customer::generateCustomerCode();

        return view('customers.create', compact('industries', 'customerCode', 'emirates'));
    }

    public function store(CustomerRequest $request, CustomerServices $service)
    {
        dd($request->all());
        $service->createUser($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer, CustomerServices $service)
    {
        $service->updateUser($customer, $request->validated());

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
