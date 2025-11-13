<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Domain\Sales\Contracts\ReceiptServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    public function __construct(
        private ReceiptServiceInterface $receiptService
    ) {}

    /**
     * Create a new receipt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt_number' => 'required|string|max:191|unique:receipts,receipt_number',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'period_id' => 'required|integer|exists:accounting_periods,id',
            'exchange_rate' => 'required|numeric|min:0',
            'base_currency_amount' => 'required|numeric|min:0',
        ]);

        $result = $this->receiptService->createReceipt($validated);

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
