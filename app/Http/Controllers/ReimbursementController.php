<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReimbursementStoreRequest;
use App\Http\Requests\ReimbursementUpdateRequest;
use App\Models\Reimbursement;
use App\Services\ReimbursementServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReimbursementController extends Controller
{
    protected ReimbursementServices $reimbursementService;
    protected string $pageTitle;

    public function __construct(ReimbursementServices $reimbursementService)
    {
        $this->reimbursementService = $reimbursementService;
        $this->pageTitle = 'Reimbursements';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $reimbursements = $this->reimbursementService->getPaginatedReimbursements($request->all());
        $options = $this->reimbursementService->getFormOptions();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'data' => $reimbursements,
                'options' => $options,
            ]);
        }

        return view('reimbursements.index', array_merge([
            'reimbursements' => $reimbursements,
            'perPage' => $reimbursements->perPage(),
        ], $options));
    }

    public function show(Reimbursement $reimbursement, Request $request): View|JsonResponse
    {
        $reimbursement = $this->reimbursementService->getReimbursementDetails($reimbursement);

        if ($request->wantsJson() || $request->ajax()) {
            $html = view()->exists('reimbursements.show-modal-content')
                ? view('reimbursements.show-modal-content', compact('reimbursement'))->render()
                : '';

            return response()->json([
                'status' => true,
                'data' => $reimbursement,
                'html' => $html,
            ]);
        }

        return view('reimbursements.show-modal-content', compact('reimbursement'));
    }

    public function store(ReimbursementStoreRequest $request): JsonResponse|RedirectResponse
    {
        $reimbursement = $this->reimbursementService->createReimbursement($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Reimbursement recorded successfully.',
                'data' => $reimbursement,
            ]);
        }

        return redirect()
            ->route('reimbursements.index')
            ->with('success', 'Reimbursement recorded successfully.');
    }

    public function edit(Reimbursement $reimbursement): JsonResponse
    {
        $reimbursement->load(['user', 'paymentMode', 'serviceProvider', 'category', 'vendor', 'customer']);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'user_name' => $reimbursement->user?->name,
                'payment_mode_id' => $reimbursement->payment_mode_id,
                'payment_mode_name' => $reimbursement->paymentMode?->name,
                'vat_payment' => $reimbursement->vat_payment,
                'local_intl_payment' => $reimbursement->local_intl_payment,
                'paid_date' => $reimbursement->paid_date ? $reimbursement->paid_date->format('Y-m-d') : null,
                'invoice_date' => $reimbursement->invoice_date ? $reimbursement->invoice_date->format('Y-m-d') : null,
                'other_currency' => $reimbursement->other_currency,
                'other_amount' => $reimbursement->other_amount,
                'payment_amount' => $reimbursement->payment_amount,
                'vat_percentage' => $reimbursement->vat_percentage,
                'vat_amount' => $reimbursement->vat_amount,
                'bank_charges' => $reimbursement->bank_charges,
                'invoice_number' => $reimbursement->invoice_number,
                'service_provider_id' => $reimbursement->service_provider_id,
                'service_provider_name' => $reimbursement->serviceProvider?->name,
                'category_id' => $reimbursement->category_id,
                'category_name' => $reimbursement->category?->name,
                'vendor_id' => $reimbursement->vendor_id,
                'vendor_name' => $reimbursement->vendor?->name,
                'service_product' => $reimbursement->service_product,
                'customer_id' => $reimbursement->customer_id,
                'customer_name' => $reimbursement->customer?->name,
                'comment' => $reimbursement->comment,
                'approval_status' => $reimbursement->approval_status,
                'amount_reimbursed' => $reimbursement->amount_reimbursed,
                'reimbursement_mode' => $reimbursement->reimbursement_mode,
                'employee_confirmation' => $reimbursement->employee_confirmation,
            ],
        ]);
    }

    public function update(ReimbursementUpdateRequest $request, Reimbursement $reimbursement): JsonResponse|RedirectResponse
    {
        $updatedReimbursement = $this->reimbursementService->updateReimbursement($reimbursement, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Reimbursement updated successfully.',
                'data' => $updatedReimbursement,
            ]);
        }

        return redirect()
            ->route('reimbursements.index')
            ->with('success', 'Reimbursement updated successfully.');
    }

    public function destroy(Request $request, Reimbursement $reimbursement): JsonResponse|RedirectResponse
    {
        $this->reimbursementService->deleteReimbursement($reimbursement);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Reimbursement deleted successfully.',
            ]);
        }

        return redirect()
            ->route('reimbursements.index')
            ->with('success', 'Reimbursement deleted successfully.');
    }
}
