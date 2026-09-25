<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChequeStoreRequest;
use App\Http\Requests\ChequeUpdateRequest;
use App\Models\Cheque;
use App\Services\ChequeServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChequeController extends Controller
{
    protected ChequeServices $chequeService;
    protected string $pageTitle;

    public function __construct(ChequeServices $chequeService)
    {
        $this->chequeService = $chequeService;
        $this->pageTitle = 'Cheque Expenses';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $cheques = $this->chequeService->getPaginatedCheques($request->all());
        $options = $this->chequeService->getFormOptions();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'data' => $cheques,
                'options' => $options,
            ]);
        }

        return view('cheques.index', array_merge([
            'cheques' => $cheques,
            'perPage' => $cheques->perPage(),
        ], $options));
    }

    public function show(Cheque $cheque, Request $request): View|JsonResponse
    {
        $cheque = $this->chequeService->getChequeDetails($cheque);

        if ($request->wantsJson() || $request->ajax()) {
            $html = view('cheques.show-modal-content', compact('cheque'))->render();

            return response()->json([
                'status' => true,
                'data' => $cheque,
                'html' => $html,
            ]);
        }

        return view('cheques.show-modal-content', compact('cheque'));
    }

    public function store(ChequeStoreRequest $request): JsonResponse|RedirectResponse
    {
        $cheque = $this->chequeService->createCheque($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Cheque expense recorded successfully.',
                'data' => $cheque,
            ]);
        }

        return redirect()
            ->route('cheques.index')
            ->with('success', 'Cheque expense recorded successfully.');
    }

    public function edit(Cheque $cheque): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'amount' => $cheque->amount,
                'cheque_date' => $cheque->cheque_date ? $cheque->cheque_date->format('Y-m-d') : null,
                'cheque_given' => $cheque->cheque_given ? $cheque->cheque_given->format('Y-m-d') : null,
                'cheque_to' => $cheque->cheque_to,
                'purpose' => $cheque->purpose,
                'cheque_status' => $cheque->cheque_status,
                'debited_date' => $cheque->debited_date ? $cheque->debited_date->format('Y-m-d') : null,
            ],
        ]);
    }

    public function update(ChequeUpdateRequest $request, Cheque $cheque): JsonResponse|RedirectResponse
    {
        $updatedCheque = $this->chequeService->updateCheque($cheque, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Cheque expense updated successfully.',
                'data' => $updatedCheque,
            ]);
        }

        return redirect()
            ->route('cheques.index')
            ->with('success', 'Cheque expense updated successfully.');
    }

    public function destroy(Request $request, Cheque $cheque): JsonResponse|RedirectResponse
    {
        $this->chequeService->deleteCheque($cheque);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Cheque expense deleted successfully.',
            ]);
        }

        return redirect()
            ->route('cheques.index')
            ->with('success', 'Cheque expense deleted successfully.');
    }
}
