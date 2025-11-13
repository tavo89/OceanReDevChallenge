<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceServiceInterface $invoiceService
    ) {}

    /**
     * Create a new invoice
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|max:191|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'customer_id' => 'required|integer|exists:customers,id',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'period_id' => 'required|integer|exists:accounting_periods,id',
            'exchange_rate' => 'required|numeric|min:0',
            'base_currency_amount' => 'required|numeric|min:0',
        ]);

        $result = $this->invoiceService->createInvoice($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => null
        ], 422);
    }
}
